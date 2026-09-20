<?php

namespace App\Http\Resources\Account;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = UserType::tryFrom((int) $this->user_type_id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_short' => Str::limit((string) $this->name, 27),
            'initials' => $this->initials(),
            'email' => $this->email,
            'cell_phone' => $this->cell_phone,
            'user_type_id' => $this->user_type_id,
            'user_type' => $this->UserType->name ?? $type?->label() ?? '',
            'company_id' => $this->company_id,
            'company' => $this->Company->name ?? '',
            'liquidity' => (float) $this->liquidity,
            'liquidity_exact' => number_format((float) $this->liquidity, 8, '.', ''),
            'status_id' => $this->status_id,
            'status' => $this->status ?: 'Unknown',
            'is_active' => (int) $this->status_id === User::Active,
            'view_route' => $this->viewRoute($type),
        ];
    }

    private function initials(): string
    {
        return collect(explode(' ', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    /**
     * @return array{name: string, params: array<string, int>}|null
     */
    private function viewRoute(?UserType $type): ?array
    {
        return match ($type) {
            UserType::Investor => ['name' => 'admin.investors.show', 'params' => ['id' => $this->id]],
            UserType::Company => ['name' => 'admin.companies.show', 'params' => ['id' => $this->id]],
            UserType::Merchant => $this->merchant_row_id
                ? ['name' => 'admin.merchants.show', 'params' => ['id' => (int) $this->merchant_row_id]]
                : null,
            default => null,
        };
    }
}
