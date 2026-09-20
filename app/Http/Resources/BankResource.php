<?php

namespace App\Http\Resources;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One bank account on an advance.
 *
 * The account and routing numbers are DELIBERATELY ABSENT. They are encrypted at
 * rest and listed in Bank::$hidden precisely so they do not ride along in
 * a payload; sending them to the browser would undo that. Only the stored last-4
 * columns are exposed, which is what every screen actually displays and costs no
 * decryption to read.
 *
 * @mixin Bank
 */
class BankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            // Which kind of party owns it, so a shared screen can say whether it
            // is showing collection accounts or payout accounts.
            'owner_type' => $this->owner_type,
            'account_holder_name' => $this->account_holder_name,
            'bank_name' => $this->bank_name,

            'account_number_last4' => $this->account_number_last4,
            'routing_number_last4' => $this->routing_number_last4,

            'account_type' => $this->account_type,
            'account_type_name' => Bank::accountTypeOptions()[$this->account_type] ?? '',

            'status_id' => $this->status_id,
            'status' => $this->status,
            'is_usable' => (bool) $this->is_usable,

            'debit' => (bool) $this->debit,
            'credit' => (bool) $this->credit,
            'default_debit' => (bool) $this->default_debit,
            'default_credit' => (bool) $this->default_credit,

            'verification_note' => $this->verification_note,
            'actum_consumer_code' => $this->actum_consumer_code,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
