<?php

use App\Http\Controllers\Api\V1\Factory\AccountController;
use App\Http\Controllers\Api\V1\Factory\AssetController;
use App\Http\Controllers\Api\V1\Factory\AuditLogController;
use App\Http\Controllers\Api\V1\Factory\AuthController;
use App\Http\Controllers\Api\V1\Factory\BankAccountController;
use App\Http\Controllers\Api\V1\Factory\BomController;
use App\Http\Controllers\Api\V1\Factory\CalibrationController;
use App\Http\Controllers\Api\V1\Factory\CapaController;
use App\Http\Controllers\Api\V1\Factory\CompanyController;
use App\Http\Controllers\Api\V1\Factory\ComplaintController;
use App\Http\Controllers\Api\V1\Factory\CustomerController;
use App\Http\Controllers\Api\V1\Factory\DashboardController;
use App\Http\Controllers\Api\V1\Factory\DepartmentController;
use App\Http\Controllers\Api\V1\Factory\EmployeeController;
use App\Http\Controllers\Api\V1\Factory\ExportController;
use App\Http\Controllers\Api\V1\Factory\FinancialController;
use App\Http\Controllers\Api\V1\Factory\FixedAssetController;
use App\Http\Controllers\Api\V1\Factory\GoodsReceiptController;
use App\Http\Controllers\Api\V1\Factory\InspectionController;
use App\Http\Controllers\Api\V1\Factory\ItemController;
use App\Http\Controllers\Api\V1\Factory\JournalController;
use App\Http\Controllers\Api\V1\Factory\MaintenanceOrderController;
use App\Http\Controllers\Api\V1\Factory\NcrController;
use App\Http\Controllers\Api\V1\Factory\PayrollRunController;
use App\Http\Controllers\Api\V1\Factory\PlanningController;
use App\Http\Controllers\Api\V1\Factory\PlantController;
use App\Http\Controllers\Api\V1\Factory\ProductionOrderController;
use App\Http\Controllers\Api\V1\Factory\PurchaseOrderController;
use App\Http\Controllers\Api\V1\Factory\PurchaseRequisitionController;
use App\Http\Controllers\Api\V1\Factory\QualityPlanController;
use App\Http\Controllers\Api\V1\Factory\QuotationController;
use App\Http\Controllers\Api\V1\Factory\RmaController;
use App\Http\Controllers\Api\V1\Factory\RoleController;
use App\Http\Controllers\Api\V1\Factory\RoutingController;
use App\Http\Controllers\Api\V1\Factory\SalesOrderController;
use App\Http\Controllers\Api\V1\Factory\SchemaController;
use App\Http\Controllers\Api\V1\Factory\ShipmentController;
use App\Http\Controllers\Api\V1\Factory\SkillMatrixController;
use App\Http\Controllers\Api\V1\Factory\StatsController;
use App\Http\Controllers\Api\V1\Factory\StockController;
use App\Http\Controllers\Api\V1\Factory\SupplierController;
use App\Http\Controllers\Api\V1\Factory\TimeBookingController;
use App\Http\Controllers\Api\V1\Factory\UomController;
use App\Http\Controllers\Api\V1\Factory\UserController;
use App\Http\Controllers\Api\V1\Factory\WarehouseController;
use App\Http\Controllers\Api\V1\Factory\WorkCenterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Factory ERP v1 (Vue.js SPA Backend)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Auth (public)
    Route::post('/login', [AuthController::class, 'login']);

    // Protected API routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Excel export for any register — see ExportController for the whitelist.
        Route::get('/exports/{resource}', [ExportController::class, 'download']);

        // What a record looks like, so the SPA can draw its form.
        Route::get('/schema/{resource}', [SchemaController::class, 'show']);

        // The figures a register opens with, above its table.
        Route::get('/stats/{resource}', [StatsController::class, 'show']);

        // === ORGANIZATION ===
        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('plants', PlantController::class);
        Route::apiResource('warehouses', WarehouseController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);

        // === ENGINEERING ===
        Route::apiResource('items', ItemController::class);
        Route::apiResource('uoms', UomController::class);
        Route::apiResource('boms', BomController::class);
        Route::apiResource('routings', RoutingController::class);
        Route::apiResource('work-centers', WorkCenterController::class);

        // === PROCUREMENT ===
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('purchase-requisitions', PurchaseRequisitionController::class);
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::apiResource('goods-receipts', GoodsReceiptController::class);

        // === INVENTORY ===
        Route::get('/stock', [StockController::class, 'index']);
        Route::get('/stock-movements', [StockController::class, 'movements']);
        Route::post('/stock-movements', [StockController::class, 'store']);

        // === PRODUCTION ===
        Route::apiResource('production-orders', ProductionOrderController::class);
        Route::post('production-orders/{production_order}/start', [ProductionOrderController::class, 'start']);
        Route::post('production-orders/{production_order}/complete', [ProductionOrderController::class, 'complete']);

        // === PLANNING ===
        Route::get('/planning', [PlanningController::class, 'index']);
        Route::post('/planning/run-mrp', [PlanningController::class, 'runMrp']);
        Route::patch('/planning/planned-orders/{planned_order}/convert', [PlanningController::class, 'convertPlannedOrder']);

        // === SALES ===
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('quotations', QuotationController::class);
        Route::apiResource('sales-orders', SalesOrderController::class);
        Route::apiResource('shipments', ShipmentController::class);
        Route::apiResource('rmas', RmaController::class);

        // === QUALITY ===
        Route::apiResource('quality-plans', QualityPlanController::class);
        Route::apiResource('inspections', InspectionController::class);
        Route::apiResource('ncrs', NcrController::class);
        Route::apiResource('capas', CapaController::class);
        Route::apiResource('calibrations', CalibrationController::class);
        Route::apiResource('complaints', ComplaintController::class);

        // === MAINTENANCE ===
        Route::apiResource('assets', AssetController::class);
        Route::apiResource('maintenance-orders', MaintenanceOrderController::class);

        // === HR ===
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('skills', SkillMatrixController::class);
        Route::apiResource('time-bookings', TimeBookingController::class);
        Route::apiResource('payroll-runs', PayrollRunController::class);

        // === FINANCE ===
        Route::apiResource('accounts', AccountController::class);
        Route::apiResource('journals', JournalController::class);
        Route::apiResource('bank-accounts', BankAccountController::class);
        Route::apiResource('fixed-assets', FixedAssetController::class);
        Route::get('/finance/trial-balance', [FinancialController::class, 'trialBalance']);
        Route::get('/finance/balance-sheet', [FinancialController::class, 'balanceSheet']);
        Route::get('/finance/profit-loss', [FinancialController::class, 'profitLoss']);
        Route::get('/finance/period-close', [FinancialController::class, 'periodList']);
        Route::post('/finance/period-close', [FinancialController::class, 'periodClose']);

        // === SYSTEM ===
        Route::get('/audit-log', [AuditLogController::class, 'index']);
    });
});
