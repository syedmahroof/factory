<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Exports\ResourceExport;
use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Excel export for every register, from one endpoint.
 *
 * The alternative was an export method on each of the thirty-odd resource
 * controllers, which is thirty copies of the same filtering. A register asks for
 * `/exports/{resource}` with the search, filters and visible columns it is
 * currently showing, and gets back that same view as a spreadsheet — the whole
 * filtered set, not the page on screen.
 *
 * Both the resource and the columns are whitelisted: the resource against the map
 * below, the columns against the table's real schema. A caller cannot name a
 * table or an attribute that was not meant to leave.
 */
class ExportController extends BaseController
{
    /**
     * Every register that can be exported, by the slug its apiResource uses.
     *
     * Every routed resource is here. Two of them sit on tables whose names do
     * not match their slug — rmas on `customer_returns`, inspections on
     * `inspections` — which their models declare.
     *
     * @var array<string, class-string<Model>>
     */
    public const RESOURCES = [
        'companies' => Models\Company::class,
        'plants' => Models\Plant::class,
        'warehouses' => Models\Warehouse::class,
        'departments' => Models\Department::class,
        'users' => Models\User::class,
        'roles' => Models\Role::class,
        'items' => Models\Item::class,
        'uoms' => Models\Uom::class,
        'boms' => Models\Bom::class,
        'routings' => Models\Routing::class,
        'work-centers' => Models\WorkCenter::class,
        'suppliers' => Models\Supplier::class,
        'purchase-requisitions' => Models\PurchaseRequisition::class,
        'purchase-orders' => Models\PurchaseOrder::class,
        'goods-receipts' => Models\GoodsReceipt::class,
        'production-orders' => Models\ProductionOrder::class,
        'customers' => Models\Customer::class,
        'quotations' => Models\Quotation::class,
        'sales-orders' => Models\SalesOrder::class,
        'shipments' => Models\Shipment::class,
        'quality-plans' => Models\QualityPlan::class,
        'rmas' => Models\ReturnMerchandiseAuthorization::class,
        'inspections' => Models\QualityInspection::class,
        'ncrs' => Models\Ncr::class,
        'capas' => Models\Capa::class,
        'calibrations' => Models\Calibration::class,
        'accounts' => Models\Account::class,
        'complaints' => Models\Complaint::class,
        'assets' => Models\Asset::class,
        'maintenance-orders' => Models\MaintenanceOrder::class,
        'employees' => Models\Employee::class,
        'skills' => Models\SkillMatrix::class,
        'time-bookings' => Models\TimeBooking::class,
        'payroll-runs' => Models\PayrollRun::class,
        'journals' => Models\Journal::class,
        'bank-accounts' => Models\BankAccount::class,
        'fixed-assets' => Models\FixedAsset::class,
        'stock' => Models\StockBalance::class,
        'stock-movements' => Models\StockMovement::class,
    ];

    /** Never exported, whatever the caller asks for. */
    private const NEVER = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    public function download(Request $request, string $resource): BinaryFileResponse
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404, "Nothing to export for '{$resource}'.");

        /** @var class-string<Model> $class */
        $class = self::RESOURCES[$resource];

        $query = $class::query();
        $this->applyScopes($query, $request);

        $available = array_diff(Schema::getColumnListing($query->getModel()->getTable()), self::NEVER);

        [$columns, $headings] = $this->columns($request, $available);

        $this->search($query, $request, $columns);
        $this->filters($query, $request, $available);

        $query->select($columns)->orderBy($query->getModel()->getKeyName());

        $name = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $request->get('filename', $resource));

        return Excel::download(
            new ResourceExport($query, $columns, $headings),
            $name.'-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * The columns the screen was showing, with its own headings, filtered down to
     * what the table actually has. An export with no usable column asked for
     * falls back to every column, which is what "export this" means when the
     * caller sent nothing.
     *
     * @param  list<string>  $available
     * @return array{0: list<string>, 1: list<string>}
     */
    private function columns(Request $request, array $available): array
    {
        $asked = array_filter(explode(',', (string) $request->get('columns')));
        $labels = array_filter(explode('|', (string) $request->get('labels')));

        $columns = [];
        $headings = [];

        foreach (array_values($asked) as $i => $column) {
            if (in_array($column, $available, true)) {
                $columns[] = $column;
                $headings[] = $labels[$i] ?? $this->humanise($column);
            }
        }

        if ($columns === []) {
            $columns = array_values($available);
            $headings = array_map($this->humanise(...), $columns);
        }

        // The key is worth having in a spreadsheet even when the screen hides it.
        if (! in_array('id', $columns, true) && in_array('id', $available, true)) {
            array_unshift($columns, 'id');
            array_unshift($headings, 'ID');
        }

        return [$columns, $headings];
    }

    /** Same free-text search the register ran, across the columns being exported. */
    private function search($query, Request $request, array $columns): void
    {
        $search = trim((string) $request->get('search'));

        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Any query parameter naming a real column narrows the export, so the
     * register's filter drawer needs no per-resource wiring here.
     *
     * @param  list<string>  $available
     */
    private function filters($query, Request $request, array $available): void
    {
        foreach ($request->query() as $key => $value) {
            if (! in_array($key, $available, true) || $value === '' || $value === null || is_array($value)) {
                continue;
            }

            $query->where($key, $value);
        }
    }

    private function humanise(string $column): string
    {
        return ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $column)));
    }
}
