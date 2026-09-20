<?php

namespace Tests\Feature\Dashboard;

use App\Actions\Report\PeriodActivityAction;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase {
        refreshTestDatabase as baseRefreshTestDatabase;
    }

    /**
     * RefreshDatabase runs migrate:fresh against whatever connection the app
     * resolves — and a cached config (bootstrap/cache/config.php) silently
     * overrides phpunit.xml's DB_DATABASE=testing with the live database.
     * Refuse to wipe anything that isn't the testing database.
     */
    protected function refreshTestDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'testing') {
            $this->markTestSkipped(
                'Refusing RefreshDatabase outside the `testing` database — run `php artisan config:clear` and retry.'
            );
        }

        $this->baseRefreshTestDatabase();
    }

    private function admin(): User
    {
        // user_types is a seeded lookup table, empty after RefreshDatabase.
        DB::table('user_types')->insertOrIgnore([
            'id' => UserType::Admin,
            'name' => 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::factory()->create(['user_type_id' => UserType::Admin]);
    }

    public function test_the_activity_endpoint_answers_an_authenticated_admin(): void
    {
        Sanctum::actingAs($this->admin(), ['admin']);

        $this->getJson('/api/v1/dashboard/activity')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_guests_are_refused(): void
    {
        $this->getJson('/api/v1/dashboard/activity')->assertUnauthorized();
    }

    /**
     * Every preset the options endpoint offers has to be one the activity
     * endpoint accepts, or the dashboard's own period picker would 422 itself.
     */
    public function test_every_offered_preset_is_accepted(): void
    {
        Sanctum::actingAs($this->admin(), ['admin']);

        $presets = $this->getJson('/api/v1/dashboard/options')->assertOk()->json('data.periods');

        $this->assertNotEmpty($presets);

        foreach ($presets as $preset) {
            $this->getJson('/api/v1/dashboard/activity?period='.$preset['id'])
                ->assertOk()
                ->assertJsonPath('data.period', $preset['id']);
        }
    }

    public function test_an_unknown_preset_is_refused_rather_than_guessed(): void
    {
        Sanctum::actingAs($this->admin(), ['admin']);

        $this->getJson('/api/v1/dashboard/activity?period=bogus')
            ->assertStatus(422);
    }

    /**
     * A range typed the wrong way round is read as the range it obviously means,
     * not as an empty window that silently reports nothing.
     */
    public function test_a_reversed_custom_range_is_swapped_rather_than_empty(): void
    {
        Sanctum::actingAs($this->admin(), ['admin']);

        $body = $this->getJson(
            '/api/v1/dashboard/activity?period=custom&from_date=2026-08-01&to_date=2026-07-01'
        )->assertOk()->json('data');

        $this->assertSame('2026-07-01', $body['from']);
        $this->assertSame('2026-08-01', $body['to']);
    }

    public function test_action_returns_every_key_on_an_empty_database(): void
    {
        $result = app(PeriodActivityAction::class)->handle(
            Carbon::parse('2026-07-01')->startOfDay(),
            Carbon::parse('2026-07-31')->endOfDay(),
        );

        $this->assertTrue($result->success);

        foreach ([
            'payments', 'payments_prev', 'series', 'by_tone', 'fees', 'fees_prev_total',
            'investor_flow', 'new_merchants', 'status_moves', 'ach', 'top_payers',
            'accounts', 'liquidity_now', 'prev_from', 'prev_to',
        ] as $key) {
            $this->assertArrayHasKey($key, $result->data);
        }

        $this->assertSame(0, (int) $result->data['payments']->count);
        // July is 31 days, so the prior 31-day window is 31 May – 30 June.
        $this->assertSame('2026-05-31', $result->data['prev_from']);
        $this->assertSame('2026-06-30', $result->data['prev_to']);
    }
}
