<?php

namespace App\Actions\Account;

use App\Actions\ActionResult;
use App\Http\Resources\Account\AccountCollection;
use App\Models\Merchant;
use App\Models\User;
use App\Models\UserType;
use Throwable;

class ListAccountsAction
{
    public function handle(array $attributes, bool $paginate = true): ActionResult
    {
        try {
            $sort = $attributes['sort'] ?? 'id';
            $direction = $attributes['direction'] ?? 'desc';

            $query = User::query()
                ->select(['id', 'name', 'email', 'cell_phone', 'liquidity', 'user_type_id', 'company_id', 'status_id'])
                ->with(['Company:id,name', 'UserType:id,name'])
                ->addSelect(['merchant_row_id' => Merchant::rowIdFor('users.id')])
                ->whereIn('user_type_id', UserType::accountTypeIds())
                ->when($attributes['user_type_id'] ?? null, fn ($q, $value) => $q->where('user_type_id', $value))
                ->when($attributes['status_id'] ?? null, fn ($q, $value) => $q->where('status_id', $value))
                ->when(trim($attributes['search'] ?? ''), function ($q, $term) {
                    $like = '%'.$term.'%';

                    $q->where(function ($q) use ($like) {
                        $q->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('cell_phone', 'like', $like)
                            ->orWhereHas('Company', fn ($q) => $q->where('name', 'like', $like));
                    });
                })
                ->orderBy($sort, $direction)
                ->orderBy('id', 'desc');

            if (! $paginate) {
                return ActionResult::success($query);
            }

            $accounts = $query->paginate($attributes['per_page'] ?? 10);

            return ActionResult::success(new AccountCollection($accounts));
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load accounts.', 500);
        }
    }
}
