<?php

use App\Actions\Merchant\GeneratePaymentDatesAction;

/*
| ACH schedule characterization.
|
| Terms::GenerateDates() skips weekends and holidays with a goto and a pair of
| running drift counters. These pin the resulting dates so the port cannot quietly
| shift somebody's debit onto a Saturday.
*/

function dates(string $cadence, string $start, int $count, array $holidays = [], array $existing = []): array
{
    return app(GeneratePaymentDatesAction::class)
        ->handle($cadence, $start, $count, $existing, array_combine($holidays, $holidays))['dates'];
}

it('steps daily and never lands on a weekend', function () {
    // 2026-01-01 is a Thursday.
    $out = dates('daily_ach', '2026-01-01', 6);

    expect($out)->toBe([
        '2026-01-01', // Thu
        '2026-01-02', // Fri
        '2026-01-05', // Mon — Sat/Sun skipped
        '2026-01-06',
        '2026-01-07',
        '2026-01-08',
    ]);

    foreach ($out as $d) {
        expect(date('l', strtotime($d)))->not->toBeIn(['Saturday', 'Sunday']);
    }
});

it('pushes a daily schedule out when a holiday falls mid-week', function () {
    // Make Monday the 5th a holiday: the run shifts one further day.
    $out = dates('daily_ach', '2026-01-01', 4, ['2026-01-05']);

    expect($out)->toBe(['2026-01-01', '2026-01-02', '2026-01-06', '2026-01-07']);
});

it('steps weekly on the same weekday', function () {
    $out = dates('weekly_ach', '2026-01-05', 4); // Monday

    expect($out)->toBe(['2026-01-05', '2026-01-12', '2026-01-19', '2026-01-26'])
        ->and(array_unique(array_map(fn ($d) => date('l', strtotime($d)), $out)))->toBe(['Monday']);
});

it('steps biweekly', function () {
    expect(dates('biweekly_ach', '2026-01-05', 3))->toBe(['2026-01-05', '2026-01-19', '2026-02-02']);
});

it('steps monthly and nudges a date that lands on a weekend', function () {
    // 2026-03-01 is a Sunday, so the March instalment moves to the Monday —
    // and the April one keeps the original anchor, unlike the daily cadence.
    $out = dates('monthly_ach', '2026-01-01', 4);

    expect($out)->toBe(['2026-01-01', '2026-02-02', '2026-03-02', '2026-04-01']);
});

it('skips dates the advance has already scheduled', function () {
    $out = dates('daily_ach', '2026-01-01', 4, [], ['2026-01-02']);

    expect($out)->not->toContain('2026-01-02')
        ->and($out)->toBe(['2026-01-01', '2026-01-05', '2026-01-06']);
});

it('returns nothing for an unknown cadence', function () {
    expect(dates('quarterly_ach', '2026-01-01', 5))->toBe([]);
});

it('reproduces the legacy double-counted drift when a holiday pushes onto a weekend', function () {
    // 2026-07-03 is a Friday AND a holiday. Moving off it lands on Sat, then Sun,
    // arriving at Monday 07-06. The original restarted its inner day counter on
    // each pass while the drift counters kept climbing, so those skipped days are
    // counted more than once and every later instalment slides an extra day:
    // the instalment after 07-06 is 07-08, not 07-07.
    //
    // Arithmetically wrong, but it is what every live ACH schedule was built with.
    // A diff of 360 generated schedules against a verbatim copy of the original
    // matches on all of them; this case is the one that proves the quirk is kept.
    $out = dates('daily_ach', '2026-06-30', 6, ['2026-07-03']);

    expect($out)->toBe([
        '2026-06-30', // Tue
        '2026-07-01', // Wed
        '2026-07-02', // Thu
        '2026-07-06', // Fri is the holiday -> Sat -> Sun -> Mon
        '2026-07-08', // <- not 07-07: the drift was counted twice
        '2026-07-09',
    ]);
});

it('never schedules a debit on a weekend, whatever the cadence', function () {
    foreach (['daily_ach', 'weekly_ach', 'biweekly_ach', 'monthly_ach'] as $cadence) {
        foreach (['2026-01-01', '2026-06-30', '2026-11-25'] as $start) {
            foreach (dates($cadence, $start, 24, ['2026-07-03', '2026-12-25']) as $d) {
                expect(date('l', strtotime($d)))
                    ->not->toBeIn(['Saturday', 'Sunday'], "{$cadence} from {$start} produced {$d}");
            }
        }
    }
});
