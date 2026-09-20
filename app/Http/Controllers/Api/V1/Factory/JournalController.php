<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends BaseController
{
    public function index(Request $request)
    {
        $query = Journal::query();
        $this->applyScopes($query, $request);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'reference' => 'nullable|string|max:100',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
        ]);

        // Validate double-entry: debits must equal credits
        $totalDebit = collect($validated['lines'])->sum('debit');
        $totalCredit = collect($validated['lines'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.001) {
            return $this->error('Journal is unbalanced. Debits ('.number_format($totalDebit, 2).') must equal Credits ('.number_format($totalCredit, 2).')', 422);
        }

        // Validate at least one debit and one credit
        $hasDebit = collect($validated['lines'])->contains(fn ($l) => $l['debit'] > 0);
        $hasCredit = collect($validated['lines'])->contains(fn ($l) => $l['credit'] > 0);

        if (! $hasDebit || ! $hasCredit) {
            return $this->error('Journal must have at least one debit line and one credit line', 422);
        }

        // Validate each line has either debit OR credit, not both
        foreach ($validated['lines'] as $idx => $line) {
            if ($line['debit'] > 0 && $line['credit'] > 0) {
                return $this->error('Line '.($idx + 1).' cannot have both debit and credit values', 422);
            }
            if ($line['debit'] == 0 && $line['credit'] == 0) {
                return $this->error('Line '.($idx + 1).' must have either a debit or credit amount', 422);
            }
        }

        // Auto-generate journal number
        $number = 'JE-'.str_pad(Journal::max('id') + 1, 6, '0', STR_PAD_LEFT);

        $journal = DB::transaction(function () use ($validated, $number, $totalDebit) {
            $journal = Journal::create([
                'number' => $number,
                'date' => $validated['date'],
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?? null,
                'status' => 'posted',
                'total_debit' => $totalDebit,
                'total_credit' => $totalDebit,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'company_id' => auth()->user()->company_id,
            ]);

            foreach ($validated['lines'] as $line) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $journal;
        });

        return $this->success($journal->load('lines'), 'Journal posted', 201);
    }

    public function show(Journal $journal)
    {
        return $this->success($journal->load('lines.account'));
    }

    /**
     * Immutable: Posted journals cannot be edited.
     */
    public function update(Request $request, Journal $journal)
    {
        if ($journal->status === 'posted' || $journal->status === 'closed') {
            return $this->error('Posted/closed journals cannot be edited. Create a reversing entry instead.', 403);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:500',
            'reference' => 'nullable|string|max:100',
        ]);

        $journal->update($validated);

        return $this->success($journal, 'Journal updated');
    }

    /**
     * Immutable: Posted journals cannot be deleted.
     * Use reversing entry for corrections.
     */
    public function destroy(Journal $journal)
    {
        if ($journal->status === 'posted' || $journal->status === 'closed') {
            return $this->error('Posted/closed journals cannot be deleted. Create a reversing entry instead.', 403);
        }

        DB::transaction(function () use ($journal) {
            $journal->lines()->delete();
            $journal->delete();
        });

        return $this->success(null, 'Journal deleted');
    }

    /**
     * Create a reversing entry for a posted journal.
     */
    public function reverse(Request $request, Journal $journal)
    {
        if ($journal->status !== 'posted') {
            return $this->error('Only posted journals can be reversed', 422);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $reversal = DB::transaction(function () use ($journal, $request) {
            $number = 'JE-'.str_pad(Journal::max('id') + 1, 6, '0', STR_PAD_LEFT);

            $reversal = Journal::create([
                'number' => $number,
                'date' => now()->toDateString(),
                'description' => "Reversal of {$journal->number}: {$request->reason}",
                'reference' => "REV-{$journal->number}",
                'status' => 'posted',
                'total_debit' => $journal->total_debit,
                'total_credit' => $journal->total_credit,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
                'company_id' => $journal->company_id,
            ]);

            // Reverse each line (swap debit/credit)
            foreach ($journal->lines as $line) {
                JournalLine::create([
                    'journal_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'description' => "Reversal: {$line->description}",
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            return $reversal;
        });

        return $this->success($reversal->load('lines'), 'Reversing entry created', 201);
    }
}
