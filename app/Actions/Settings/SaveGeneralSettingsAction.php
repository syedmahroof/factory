<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Settings;
use Illuminate\Support\Facades\DB;

/**
 * The handful of values that govern how the platform behaves.
 *
 * `settings` is a key/value table, so each field is its own row. Written in one
 * transaction: a half-applied set of caps is worse than none — a maximum assignment
 * saved without its matching minimum would let the allocation screens propose
 * something the other rule forbids.
 */
class SaveGeneralSettingsAction
{
    /** The keys this screen owns. Anything else in the table is left alone. */
    public const KEYS = [
        'admin_email',
        'system_admin',
        'max_assign_percentage',
        'minimum_investment_value',
        'max_investment_percentage',
        // One rate for every advance: the split reads this, not a merchant column.
        Settings::AgentFeePercentage,
    ];

    public function handle(array $values): array
    {
        return DB::transaction(function () use ($values) {
            foreach (self::KEYS as $key) {
                if (array_key_exists($key, $values)) {
                    Settings::updateOrCreate(['key' => $key], ['values' => (string) $values[$key]]);
                }
            }

            return $this->current();
        });
    }

    /**
     * @return array<string, string>
     */
    public function current(): array
    {
        $stored = Settings::whereIn('key', self::KEYS)->pluck('values', 'key');

        return collect(self::KEYS)->mapWithKeys(fn ($key) => [$key => $stored[$key] ?? ''])->all();
    }
}
