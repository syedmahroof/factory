<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\Plant::class => \App\Policies\PlantPolicy::class,
        \App\Models\Warehouse::class => \App\Policies\WarehousePolicy::class,
        \App\Models\Department::class => \App\Policies\DepartmentPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Item::class => \App\Policies\ItemPolicy::class,
        \App\Models\Uom::class => \App\Policies\UomPolicy::class,
        \App\Models\Bom::class => \App\Policies\BomPolicy::class,
        \App\Models\Routing::class => \App\Policies\RoutingPolicy::class,
        \App\Models\WorkCenter::class => \App\Policies\WorkCenterPolicy::class,
        \App\Models\Formula::class => \App\Policies\FormulaPolicy::class,
        \App\Models\EngineeringChange::class => \App\Policies\EngineeringChangePolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\PurchaseRequisition::class => \App\Policies\PurchaseRequisitionPolicy::class,
        \App\Models\PurchaseOrder::class => \App\Policies\PurchaseOrderPolicy::class,
        \App\Models\GoodsReceipt::class => \App\Policies\GoodsReceiptPolicy::class,
        \App\Models\RequestForQuotation::class => \App\Policies\RequestForQuotationPolicy::class,
        \App\Models\LandedCost::class => \App\Policies\LandedCostPolicy::class,
        \App\Models\StockBalance::class => \App\Policies\StockBalancePolicy::class,
        \App\Models\StockMovement::class => \App\Policies\StockMovementPolicy::class,
        \App\Models\CycleCount::class => \App\Policies\CycleCountPolicy::class,
        \App\Models\ProductionOrder::class => \App\Policies\ProductionOrderPolicy::class,
        \App\Models\OperationJob::class => \App\Policies\OperationJobPolicy::class,
        \App\Models\BatchRecord::class => \App\Policies\BatchRecordPolicy::class,
        \App\Models\WipBalance::class => \App\Policies\WipBalancePolicy::class,
        \App\Models\ReworkOrder::class => \App\Policies\ReworkOrderPolicy::class,
        \App\Models\ScrapRecord::class => \App\Policies\ScrapRecordPolicy::class,
        \App\Models\QualityPlan::class => \App\Policies\QualityPlanPolicy::class,
        \App\Models\Inspection::class => \App\Policies\InspectionPolicy::class,
        \App\Models\Ncr::class => \App\Policies\NcrPolicy::class,
        \App\Models\CapaAction::class => \App\Policies\CapaActionPolicy::class,
        \App\Models\CalibrationRecord::class => \App\Policies\CalibrationRecordPolicy::class,
        \App\Models\Complaint::class => \App\Policies\ComplaintPolicy::class,
        \App\Models\Recall::class => \App\Policies\RecallPolicy::class,
        \App\Models\Asset::class => \App\Policies\AssetPolicy::class,
        \App\Models\MaintenanceOrder::class => \App\Policies\MaintenanceOrderPolicy::class,
        \App\Models\MaintenancePlan::class => \App\Policies\MaintenancePlanPolicy::class,
        \App\Models\PreventiveMaintenanceSchedule::class => \App\Policies\PreventiveMaintenanceSchedulePolicy::class,
        \App\Models\SparePart::class => \App\Policies\SparePartPolicy::class,
        \App\Models\ToolRegistration::class => \App\Policies\ToolRegistrationPolicy::class,
        \App\Models\OeeRecord::class => \App\Policies\OeeRecordPolicy::class,
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Models\Attendance::class => \App\Policies\AttendancePolicy::class,
        \App\Models\Shift::class => \App\Policies\ShiftPolicy::class,
        \App\Models\ShiftRoster::class => \App\Policies\ShiftRosterPolicy::class,
        \App\Models\SkillMatrix::class => \App\Policies\SkillMatrixPolicy::class,
        \App\Models\TimeBooking::class => \App\Policies\TimeBookingPolicy::class,
        \App\Models\SafetyIncident::class => \App\Policies\SafetyIncidentPolicy::class,
        \App\Models\PermitToWork::class => \App\Policies\PermitToWorkPolicy::class,
        \App\Models\PayrollRun::class => \App\Policies\PayrollRunPolicy::class,
        \App\Models\Account::class => \App\Policies\AccountPolicy::class,
        \App\Models\Journal::class => \App\Policies\JournalPolicy::class,
        \App\Models\SupplierInvoice::class => \App\Policies\SupplierInvoicePolicy::class,
        \App\Models\CustomerInvoice::class => \App\Policies\CustomerInvoicePolicy::class,
        \App\Models\BankAccount::class => \App\Policies\BankAccountPolicy::class,
        \App\Models\BankReconciliation::class => \App\Policies\BankReconciliationPolicy::class,
        \App\Models\FixedAsset::class => \App\Policies\FixedAssetPolicy::class,
        \App\Models\DepreciationEntry::class => \App\Policies\DepreciationEntryPolicy::class,
        \App\Models\Budget::class => \App\Policies\BudgetPolicy::class,
        \App\Models\FiscalPeriod::class => \App\Policies\FiscalPeriodPolicy::class,
        \App\Models\PeriodClose::class => \App\Policies\PeriodClosePolicy::class,
        \App\Models\CostRollup::class => \App\Policies\CostRollupPolicy::class,
        \App\Models\TaxRule::class => \App\Policies\TaxRulePolicy::class,
        \App\Models\Quotation::class => \App\Policies\QuotationPolicy::class,
        \App\Models\SalesOrder::class => \App\Policies\SalesOrderPolicy::class,
        \App\Models\Shipment::class => \App\Policies\ShipmentPolicy::class,
        \App\Models\CustomerCreditExposure::class => \App\Policies\CustomerCreditExposurePolicy::class,
        \App\Models\ReturnMerchandiseAuthorization::class => \App\Policies\ReturnMerchandiseAuthorizationPolicy::class,
        \App\Models\Forecast::class => \App\Policies\ForecastPolicy::class,
        \App\Models\PlannedOrder::class => \App\Policies\PlannedOrderPolicy::class,
        \App\Models\MrpRun::class => \App\Policies\MrpRunPolicy::class,
        \App\Models\Attachment::class => \App\Policies\AttachmentPolicy::class,
        \App\Models\AuditLog::class => \App\Policies\AuditLogPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function (User $user) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
