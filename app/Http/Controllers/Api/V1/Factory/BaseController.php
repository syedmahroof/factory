<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BaseController extends Controller
{
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = ['success' => false, 'message' => $message];
        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function paginated($query, Request $request, array $with = [], ?array $searchFields = null)
    {
        $perPage = min((int) $request->get('per_page', 25), 100);
        $query->with($with);

        // Search
        if ($search = $request->get('search')) {
            $fields = $searchFields ?? $request->get('search_fields', ['name', 'code', 'number']);
            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        // Sorting
        if ($sort = $request->get('sort')) {
            $dir = $request->get('dir', 'asc');
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Does the table behind this query actually carry the column?
     *
     * `isFillable()` answers a different question — whether the model lets the
     * column be mass-assigned — and several models list columns their tables do
     * not have. Scoping on that produced "Unknown column 'plant_id'" rather than
     * an unscoped query, so the schema is what gets asked. Answers are memoised
     * per table for the life of the request.
     */
    protected function tableHasColumn($query, string $column): bool
    {
        static $cache = [];

        $model = $query->getModel();
        $key = $model->getConnectionName().'.'.$model->getTable().'.'.$column;

        return $cache[$key] ??= Schema::connection($model->getConnectionName())
            ->hasColumn($model->getTable(), $column);
    }

    /**
     * Scope query to current user's company.
     * Call this on every query to enforce multi-tenancy.
     */
    protected function scopeToCompany($query, Request $request): void
    {
        $companyId = $request->user()->company_id ?? null;
        if ($companyId && $this->tableHasColumn($query, 'company_id')) {
            $query->where('company_id', $companyId);
        }
    }

    /**
     * Scope query to current user's plant (optional filter).
     */
    protected function scopeToPlant($query, Request $request): void
    {
        $plantId = $request->get('plant_id') ?? $request->user()->plant_id ?? null;
        if ($plantId && $this->tableHasColumn($query, 'plant_id')) {
            $query->where('plant_id', $plantId);
        }
    }

    /**
     * Apply both company and plant scoping in one call.
     */
    protected function applyScopes($query, Request $request): void
    {
        $this->scopeToCompany($query, $request);
        $this->scopeToPlant($query, $request);
    }

    /**
     * Columns a client never sends: the key, the surrogate uuid, the audit stamps
     * and the soft-delete marker.
     */
    private const NOT_INPUT = [
        'id', 'uuid', 'created_at', 'updated_at', 'deleted_at',
        'created_by', 'approved_by', 'approved_at',
    ];

    /**
     * Validation rules read off the table.
     *
     * Every controller here used to write `$request->validate(Model::$rules ?? [])`,
     * and no model ever declared `$rules` — so nothing was validated, `$validated`
     * came back empty, and `create()` inserted a blank row (or died on the first
     * NOT NULL column). The table already knows what it will accept: which columns
     * exist, which are nullable, how long a string may be, what an enum permits and
     * which columns are unique. Asking it is both correct and one implementation
     * instead of ninety-nine.
     *
     * @return array<string, string>
     */
    protected function rulesFor(Model $model, ?int $ignoreId = null): array
    {
        $table = $model->getTable();
        $fillable = $model->getFillable();
        $rules = [];

        $uniques = collect(Schema::getIndexes($table))
            ->where('unique', true)
            ->pluck('columns')
            ->filter(fn ($cols) => count($cols) === 1)
            ->flatten()
            ->all();

        foreach (Schema::getColumns($table) as $column) {
            $name = $column['name'];

            if (in_array($name, self::NOT_INPUT, true)) {
                continue;
            }

            // $fillable is the model's own statement of what may be set. An empty
            // one means the model never declared it, so the table decides instead.
            if ($fillable !== [] && ! in_array($name, $fillable, true)) {
                continue;
            }

            $rule = [];

            if ($ignoreId !== null) {
                // On update every field is optional — a form may send one of them —
                // and a nullable column may be sent as null to clear it, which
                // `sometimes|string` would otherwise reject.
                $rule[] = 'sometimes';

                if ($column['nullable']) {
                    $rule[] = 'nullable';
                }
            } else {
                $rule[] = $this->isRequired($column) ? 'required' : 'nullable';
            }

            $rule = array_merge($rule, $this->typeRules($column, $name));

            if (in_array($name, $uniques, true)) {
                $rule[] = $ignoreId === null
                    ? "unique:{$table},{$name}"
                    : "unique:{$table},{$name},{$ignoreId}";
            }

            $rules[$name] = implode('|', $rule);
        }

        return $rules;
    }

    /** A column with no default that will not take null has to be supplied. */
    private function isRequired(array $column): bool
    {
        return ! $column['nullable']
            && $column['default'] === null
            && ! ($column['auto_increment'] ?? false);
    }

    /** @return list<string> */
    private function typeRules(array $column, string $name): array
    {
        $type = $column['type'] ?? $column['type_name'];

        // enum('a','b') carries its own whitelist; nothing else may be stored.
        if (str_starts_with($type, 'enum(')) {
            $values = str_getcsv(substr($type, 5, -1), ',', "'", '\\');

            return ['in:'.implode(',', $values)];
        }

        // A foreign key is an integer column ending in `_id`. The name alone is
        // not enough — `tax_id` is a VAT number, and validating it as an integer
        // rejects every real one.
        $isInteger = in_array($column['type_name'], ['int', 'integer', 'bigint', 'smallint', 'mediumint'], true);

        if ($isInteger && str_ends_with($name, '_id')) {
            $related = Str::plural(Str::beforeLast($name, '_id'));

            return Schema::hasTable($related)
                ? ['integer', "exists:{$related},id"]
                : ['integer'];
        }

        if ($name === 'email' || str_ends_with($name, '_email')) {
            return ['email', 'max:'.$this->lengthOf($type, 255)];
        }

        return match ($column['type_name']) {
            'tinyint' => str_contains($type, '(1)') ? ['boolean'] : ['integer'],
            'int', 'integer', 'bigint', 'smallint', 'mediumint' => ['integer'],
            'decimal', 'numeric', 'float', 'double' => ['numeric'],
            'date' => ['date'],
            'datetime', 'timestamp' => ['date'],
            'json' => ['array'],
            'varchar', 'char' => ['string', 'max:'.$this->lengthOf($type, 255)],
            default => ['string'],
        };
    }

    private function lengthOf(string $type, int $fallback): int
    {
        return preg_match('/\((\d+)\)/', $type, $m) ? (int) $m[1] : $fallback;
    }

    /**
     * Stamp the columns a client has no business choosing.
     *
     * Tenancy and authorship come from the token, not the payload — otherwise a
     * form has to know the company id, and anyone can post a different one.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function stampOwnership(array $data, Model $model, Request $request): array
    {
        $user = $request->user();
        $table = $model->getTable();

        // Tenancy and authorship are taken from the token and overwrite whatever
        // was posted: a client that can name its own company_id can write into
        // another tenant. Plant is different — it is a filter, not a boundary, so
        // a payload may choose one and the user's own is only the fallback.
        foreach (['company_id' => $user?->company_id, 'created_by' => $user?->id] as $column => $value) {
            if ($value !== null && Schema::hasColumn($table, $column)) {
                $data[$column] = $value;
            }
        }

        if ($user?->plant_id !== null && Schema::hasColumn($table, 'plant_id') && blank($data['plant_id'] ?? null)) {
            $data['plant_id'] = $user->plant_id;
        }

        return $data;
    }

    /**
     * Validate a write against the table and stamp it. `$ignoreId` is the record
     * being updated, so its own value does not trip a unique rule.
     *
     * @return array<string, mixed>
     */
    protected function validatedFor(Request $request, Model $model, ?int $ignoreId = null): array
    {
        // Stamped before validation, not after: company_id is NOT NULL on most of
        // these tables, so a payload that omits it has to be completed before the
        // rules see it — otherwise every create fails on a field no form should
        // have been asked to fill in.
        if ($ignoreId === null) {
            $request->merge($this->stampOwnership($request->all(), $model, $request));
        }

        // Stripped before the rules run, not after: a null aimed at a NOT NULL
        // column is not a value the rules should be asked to judge, and on update
        // `sometimes|string` would reject it before it could be dropped.
        $request->replace($this->dropNullsWithDefaults($request->all(), $model));

        return $request->validate($this->rulesFor($model, $ignoreId));
    }

    /**
     * Drop nulls aimed at columns that will not take one.
     *
     * A NOT NULL column with a default is optional to *supply* — leave it out and
     * the database fills it in — but writing an explicit NULL to it still fails.
     * A generated form has no way to tell those apart from genuinely nullable
     * columns, and sends null for every field left blank, so "create a supplier
     * without typing payment terms" died on a constraint. Blank means "use the
     * default", so the key is removed rather than sent as null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function dropNullsWithDefaults(array $data, Model $model): array
    {
        $columns = collect(Schema::getColumns($model->getTable()))->keyBy('name');

        return collect($data)
            ->reject(fn ($value, $key) => $value === null && ($columns[$key]['nullable'] ?? true) === false)
            ->all();
    }

    /**
     * Handle validation errors with consistent response format.
     */
    protected function handleValidation($request, array $rules)
    {
        try {
            return $request->validate($rules);
        } catch (ValidationException $e) {
            return; // Signal to caller to return error
        }
    }
}
