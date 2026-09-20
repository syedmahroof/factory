<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class JournalLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('journal_lines')) {
            $this->command?->warn('Skipping JournalLineSeeder: table "journal_lines" not found.');

            return;
        }

        $journal = Journal::where('number', 'J-0001')->first();
        $inventory = Account::where('code', '1200')->first();
        $revenue = Account::where('code', '4010')->first();
        if (! $journal || ! $inventory || ! $revenue) {
            $this->command?->warn('Skipping JournalLineSeeder: journal J-0001 or accounts not found.');

            return;
        }

        foreach ([
            ['line_number' => 10, 'account' => $inventory, 'debit' => 100, 'credit' => 0],
            ['line_number' => 20, 'account' => $revenue, 'debit' => 0, 'credit' => 100],
        ] as $line) {
            JournalLine::firstOrCreate([
                'journal_id' => $journal->id,
                'line_number' => $line['line_number'],
            ], [
                'account_id' => $line['account']->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'currency' => 'USD',
                'exchange_rate' => 1,
                'debit_local' => $line['debit'],
                'credit_local' => $line['credit'],
                'description' => 'Sample entry line.',
            ]);
        }
    }
}
