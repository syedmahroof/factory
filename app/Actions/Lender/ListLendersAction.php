<?php

namespace App\Actions\Lender;

use App\Actions\ActionResult;
use App\Http\Resources\Lender\LenderCollection;
use App\Models\Lender;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * The Lenders register query, shared by the table and the export.
 */
class ListLendersAction
{
    /** Columns that live on `lenders` rather than `users`. */
    public const PROFILE_SORTS = [
        'username',
        'management_fee_percentage',
        'syndication_fee_percentage',
        'syndication_fee_type',
        'lag_time_days',
    ];

    public function handle(array $attributes, bool $paginate = true): ActionResult
    {
        try {
            $sort = $attributes['sort'] ?? 'id';
            $direction = $attributes['direction'] ?? 'desc';

            $query = User::query()
                ->select(['id', 'name', 'email', 'cell_phone', 'company_id', 'status_id', 'user_type_id'])
                ->with(['Company:id,name', 'Lender'])
                ->where('user_type_id', UserType::Lender);

            foreach (self::PROFILE_SORTS as $column) {
                $query->addSelect([$column => Lender::select($column)
                    ->whereColumn('lenders.user_id', 'users.id')
                    ->limit(1),
                ]);
            }

            $this->applyFilters($query, $attributes);

            $query->orderBy($sort, $direction)->orderBy('id', 'desc');

            if (! $paginate) {
                return ActionResult::success($query);
            }

            return ActionResult::success(new LenderCollection($query->paginate($attributes['per_page'] ?? 10)));
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load lenders.', 500);
        }
    }

    /** The register's filter row: status, company, fee type, and free-text search. */
    private function applyFilters(Builder $query, array $attributes): void
    {
        $query->when($attributes['status_id'] ?? null, fn ($q, $value) => $q->where('status_id', $value))
            ->when($attributes['company_id'] ?? null, fn ($q, $value) => $q->where('company_id', $value))
            ->when(
                ($attributes['syndication_fee_type'] ?? null) !== null && $attributes['syndication_fee_type'] !== '',
                fn ($q) => $q->whereHas('Lender', fn ($q) => $q->where('syndication_fee_type', (int) $attributes['syndication_fee_type']))
            )
            ->when(trim($attributes['search'] ?? ''), function ($q, $term) {
                $like = '%'.$term.'%';

                $q->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('cell_phone', 'like', $like)
                        ->orWhereHas('Lender', fn ($q) => $q->where('username', 'like', $like)
                            ->orWhere('notification_email', 'like', $like))
                        ->orWhereHas('Company', fn ($q) => $q->where('name', 'like', $like));
                });
            });
    }
}
