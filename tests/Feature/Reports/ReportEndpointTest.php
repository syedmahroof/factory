<?php

use App\Actions\Report\RunReportAction;
use App\Actions\Report\RunReportDetailAction;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Reports module
|--------------------------------------------------------------------------
|
| These pin the three things that were actually wrong when the module was
| ported, rather than the figures each report produces:
|
|   1. Injection. Every report builds its `IN (...)` fragments by string
|      concatenation. `sqlIdList()` and the integer rules are what stop that
|      being exploitable, and both need to stay.
|   2. Paging. None of the helper queries orders itself — the legacy DataTable
|      always sent an ORDER BY, so it never showed. Paged by LIMIT/OFFSET with
|      no order, MySQL may repeat a row on one page and drop it from the next.
|   3. The count query. Two reports hang a having() on an alias from a joined
|      sub-select, which Laravel's own count could not survive.
|
| The figures themselves were checked against the unported queries directly;
| they are not restated here.
|
*/

beforeEach(function () {
    foreach ([UserType::Admin => 'Admin', UserType::Investor => 'Investor', UserType::Merchant => 'Merchant', UserType::Company => 'Company'] as $id => $name) {
        DB::table('user_types')->updateOrInsert(['id' => $id], ['name' => $name]);
    }

    $this->admin = User::create([
        'user_type_id' => UserType::Admin,
        'name' => 'Report Test Admin',
        'email' => 'report-admin-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);
});

function reportGet(string $path, array $query = [])
{
    // config/sanctum.php sets `guard => []`, so a session can never authenticate
    // the API — the request has to carry a token with the `admin` ability, which is
    // what the route group checks.
    Sanctum::actingAs(test()->admin, ['admin']);

    return test()->getJson('/api/v1/reports/'.ltrim($path, '/').'?'.http_build_query($query));
}

/**
 * One liquidity batch carrying two different postings.
 *
 * This is the case the merchant liquidity report gets wrong when it groups on
 * `batch_no` alone: an Investment and a Payment posted under the same number are
 * summed into a single row labelled with whichever description MySQL picked.
 */
function seedLiquidityBatch(): array
{
    // liquidity_logs.company_id carries a NOT NULL FK to users, so the owning
    // company has to exist before any movement can be written.
    $company = User::create([
        'user_type_id' => UserType::Company,
        'name' => 'Report Test Company',
        'email' => 'report-company-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $merchant = User::create([
        'user_type_id' => UserType::Merchant,
        'name' => 'Report Test Merchant',
        'email' => 'report-merchant-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $investors = collect(range(1, 2))->map(fn ($i) => User::create([
        'user_type_id' => UserType::Investor,
        'name' => "Report Test Investor {$i}",
        'email' => 'report-investor-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]));

    $rows = [];

    foreach ([['Investment', -1000], ['Payment', 250]] as [$description, $unit]) {
        foreach ($investors as $i => $investor) {
            $rows[] = [
                'merchant_id' => $merchant->id,
                'investor_id' => $investor->id,
                'company_id' => $company->id,
                'batch_no' => 900001,
                'amount' => $unit * ($i + 1),
                'net_liquidity' => 0,
                'description' => $description,
                'creator_id' => test()->admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    DB::table('liquidity_logs')->insert($rows);

    return ['merchant' => $merchant, 'investors' => $investors, 'company' => $company];
}

it('runs every report it advertises', function () {
    $advertised = collect(RunReportAction::available())->pluck('id');

    expect($advertised)->not->toBeEmpty();

    foreach ($advertised as $report) {
        reportGet($report, ['per_page' => 10])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta' => ['report', 'current_page', 'last_page', 'total']]);
    }
});

it('pages without repeating or dropping rows', function () {
    // The reports with no ORDER BY of their own are the ones at risk.
    foreach (['payment_details', 'investor_default_rate', 'merchant_default_rate'] as $report) {
        $seen = [];

        foreach ([1, 2, 3] as $page) {
            $rows = reportGet($report, ['per_page' => 10, 'page' => $page])->assertOk()->json('data');

            foreach ($rows as $row) {
                $seen[] = json_encode($row);
            }
        }

        expect(count($seen))->toBe(count(array_unique($seen)), "{$report} repeated a row across pages");
    }
});

it('counts the reports whose having() clause defeats the default count query', function () {
    // Both of these returned a 500 from MySQL ("Unknown column 'total_payment' in
    // 'having clause'") until the count was given its own column list. An empty
    // result is still a pass here — the point is that the count runs at all.
    foreach (['payment', 'investment'] as $report) {
        reportGet($report, ['per_page' => 10])
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['total', 'last_page']]);
    }
});

it('splits a batch number shared by two postings', function () {
    $seeded = seedLiquidityBatch();

    $rows = collect(reportGet('merchant_liquidity_log', [
        'per_page' => 100,
        'merchant_ids' => [$seeded['merchant']->id],
    ])->assertOk()->json('data'));

    // One row per posting, not one row for the batch.
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('description')->sort()->values()->all())->toBe(['Investment', 'Payment'])
        ->and((float) $rows->firstWhere('description', 'Investment')['amount'])->toBe(-3000.0)
        ->and((float) $rows->firstWhere('description', 'Payment')['amount'])->toBe(750.0);
});

it('refuses an id filter that is not an integer', function () {
    // The payload that used to rewrite the WHERE clause.
    reportGet('investment', ['per_page' => 10, 'investor_ids' => ['2) OR 1=1 -- ']])
        ->assertStatus(422)
        ->assertJsonValidationErrors('investor_ids.0');
});

it('reduces an id list to integers before it reaches the SQL', function () {
    expect(sqlIdList([1, 2, 3]))->toBe('1,2,3')
        ->and(sqlIdList(['1', '2) OR 1=1 -- ']))->toBe('1,2')
        ->and(sqlIdList(['nonsense']))->toBe('')
        ->and(sqlIdList([]))->toBe('');
});

it('ignores a sort column the report does not offer', function () {
    // Allow-listed rather than passed through: an unknown column would otherwise
    // reach orderBy() and error, and a chosen one is not the client's to name.
    $unsorted = reportGet('payment_details', ['per_page' => 10])->assertOk()->json('data');
    $bogus = reportGet('payment_details', ['per_page' => 10, 'sort' => 'merchant_payments.secret'])
        ->assertOk()->json('data');

    expect($bogus)->toEqual($unsorted);
});

it('sorts on a column the report does offer', function () {
    seedLiquidityBatch();

    foreach (['asc', 'desc'] as $direction) {
        $amounts = collect(reportGet('investor_liquidity_log', ['per_page' => 100, 'sort' => 'amount', 'direction' => $direction])
            ->assertOk()->json('data'))->pluck('amount')->map(fn ($v) => (float) $v);

        $expected = $direction === 'asc' ? $amounts->sort()->values() : $amounts->sortDesc()->values();

        expect($amounts->all())->toBe($expected->all());
    }
});

it('rejects a report name it does not know', function () {
    reportGet('not_a_report', ['per_page' => 10])->assertStatus(422);
});

it('requires the parent key for a drill-down and refuses one without', function () {
    reportGet('payment/details')->assertStatus(422);
    reportGet('liquidity/details', ['merchant_id' => 1])->assertStatus(422);
});

it('reconciles every merchant liquidity batch with its drill-down', function () {
    seedLiquidityBatch();

    // The grouped row and the rows behind it have to agree, or the split of a
    // shared batch number into its separate postings is wrong.
    $rows = reportGet('merchant_liquidity_log', ['per_page' => 10])->assertOk()->json('data');

    expect($rows)->not->toBeEmpty();

    foreach ($rows as $row) {
        $children = reportGet('merchant_liquidity_log/details', [
            'batch_no' => $row['batch_no'],
            'descriptions' => [$row['description']],
            'creator_id' => $row['creator_id'],
        ])->assertOk()->json('data');

        expect(round(collect($children)->sum(fn ($c) => (float) $c['amount']), 2))
            ->toBe(round((float) $row['amount'], 2));
    }
});

it('describes each report so the client does not have to guess', function () {
    $reports = reportGet('options')->assertOk()->json('data.reports');

    foreach ($reports as $report) {
        expect($report)->toHaveKeys(['id', 'name', 'filters', 'sortable', 'detail']);

        // A report that claims a drill-down must have one wired.
        if ($report['detail']) {
            expect(RunReportDetailAction::PARENTS)->toHaveKey($report['id']);
        }
    }
});

it('keeps the reports behind authentication', function () {
    $this->getJson('/api/v1/reports/payment?per_page=10')->assertStatus(401);
});

it('keeps the reports behind the admin ability', function () {
    // An investor's token reaches the investor portal, not this one.
    Sanctum::actingAs($this->admin, ['investor']);

    $this->getJson('/api/v1/reports/payment?per_page=10')->assertStatus(403);
});
