<?php
namespace App\Actions\System;

use App\Models\ApprovalEntry;
use App\Models\ApprovalRule;
use App\Services\AuditService;

class ProcessApproval
{
    public function submit(string $type, int $modelId, int $submittedBy, ?string $notes = null): ApprovalEntry
    {
        $rule = ApprovalRule::where('approvable_type', $type)->where('is_active', true)->first();

        $entry = ApprovalEntry::create([
            'approval_rule_id' => $rule?->id,
            'approvable_type' => $type,
            'approvable_id' => $modelId,
            'submitted_by' => $submittedBy,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        AuditService::log('submitted', 'system', $entry);
        return $entry;
    }

    public function approve(ApprovalEntry $entry, int $approvedBy, ?string $comments = null): ApprovalEntry
    {
        $entry->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'comments' => $comments,
        ]);

        AuditService::log('approved', 'system', $entry);
        return $entry;
    }

    public function reject(ApprovalEntry $entry, int $rejectedBy, ?string $reason = null): ApprovalEntry
    {
        $entry->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedBy,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        AuditService::log('rejected', 'system', $entry);
        return $entry;
    }
}