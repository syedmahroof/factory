<?php

namespace App\Actions\Finance;

use App\Models\Journal;
use App\Models\JournalLine;
use App\Services\AuditService;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostJournalEntry
{
    /**
     * Post a balanced double-entry journal.
     * Total debits MUST equal total credits.
     */
    public function execute(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            // Validate double-entry: total debits == total credits
            $totalDebit = collect($lines)->sum(fn ($l) => $l['debit'] ?? 0);
            $totalCredit = collect($lines)->sum(fn ($l) => $l['credit'] ?? 0);

            if (round($totalDebit, 2) != round($totalCredit, 2)) {
                throw ValidationException::withMessages([
                    'lines' => "Debits ({$totalDebit}) must equal credits ({$totalCredit}). Journal is unbalanced.",
                ]);
            }

            if ($totalDebit == 0 && $totalCredit == 0) {
                throw ValidationException::withMessages([
                    'lines' => 'Journal entry must have at least one debit and one credit.',
                ]);
            }

            // Validate every line has either debit or credit, not both
            foreach ($lines as $index => $line) {
                $dr = $line['debit'] ?? 0;
                $cr = $line['credit'] ?? 0;
                if (($dr > 0 && $cr > 0) || ($dr == 0 && $cr == 0)) {
                    throw ValidationException::withMessages([
                        "lines.{$index}" => 'Each line must have either a debit or a credit (not both, not neither).',
                    ]);
                }
            }

            $data['number'] = NumberGenerator::next('journal', 'JE');
            $data['status'] = 'posted';
            $data['posted_at'] = now();
            $data['total_debit'] = $totalDebit;
            $data['total_credit'] = $totalCredit;

            $journal = Journal::create($data);

            foreach ($lines as $line) {
                $line['journal_id'] = $journal->id;
                JournalLine::create($line);
            }

            AuditService::log('posted', 'finance', $journal, null, array_merge($data, ['lines' => $lines]));

            return $journal;
        });
    }
}
