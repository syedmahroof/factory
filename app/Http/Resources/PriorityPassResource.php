<?php

namespace App\Http\Resources;

use App\Models\PriorityPassSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One investor's Priority Pass enrolment.
 *
 * A `max_amount` of 0 means no dollar ceiling — the percentage alone binds.
 *
 * @mixin PriorityPassSetting
 */
class PriorityPassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'investor_id' => $this->investor_id,
            'investor' => $this->Investor->name ?? null,
            /*
             * The investor behind the row has since been deleted. The row is still
             * listed so it can be taken off the program — hiding it would leave an
             * enrolment nobody could reach.
             */
            'investor_exists' => $this->Investor !== null,
            'email' => $this->Investor->email ?? '',
            'company' => $this->Investor->Company->name ?? '',

            'max_percentage' => (float) $this->max_percentage,
            'max_amount' => (float) $this->max_amount,
            'has_ceiling' => (float) $this->max_amount > 0,

            'status' => (bool) $this->status,
            'enrolled_at' => optional($this->created_at)->format('Y-m-d'),
            'enrolled_on' => systemDate($this->created_at),
            // Only when the caps have actually moved since enrolment — otherwise
            // every row would claim to have been edited on the day it was created.
            'edited_on' => $this->updated_at && $this->updated_at->gt($this->created_at)
                ? systemDate($this->updated_at)
                : null,
        ];
    }
}
