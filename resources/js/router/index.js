import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    // Auth
    { path: '/login', name: 'login', component: () => import('../pages/auth/Login.vue'), meta: { guest: true, layout: 'blank' } },

    // Dashboard
    // The dashboard opens with HbHero, which draws its own panel over the title
    // band — so the band carries neither the heading nor a crumb behind it.
    { path: '/', name: 'dashboard', component: () => import('../pages/dashboard/Dashboard.vue'), meta: { title: 'Dashboard', ownsHeading: true, crumb: null } },

    // Organization
    { path: '/companies', name: 'companies.index', component: () => import('../pages/organization/Companies.vue'), meta: { title: 'Companies' } },
    { path: '/plants', name: 'plants.index', component: () => import('../pages/organization/Plants.vue'), meta: { title: 'Plants' } },
    { path: '/warehouses', name: 'warehouses.index', component: () => import('../pages/organization/Warehouses.vue'), meta: { title: 'Warehouses' } },
    { path: '/users', name: 'users.index', component: () => import('../pages/organization/Users.vue'), meta: { title: 'Users & Roles' } },

    // Engineering
    { path: '/items', name: 'items.index', component: () => import('../pages/engineering/Items.vue'), meta: { title: 'Items' } },
    { path: '/items/create', name: 'items.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Item', resource: 'items', listPath: '/items', singular: 'Item' } },
    { path: '/items/:id/edit', name: 'items.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Item', resource: 'items', listPath: '/items', singular: 'Item' } },
    { path: '/uoms', name: 'uoms.index', component: () => import('../pages/engineering/Uoms.vue'), meta: { title: 'UOM' } },
    { path: '/boms', name: 'boms.index', component: () => import('../pages/engineering/Boms.vue'), meta: { title: 'BOM' } },
    { path: '/routings', name: 'routings.index', component: () => import('../pages/engineering/Routings.vue'), meta: { title: 'Routings' } },
    { path: '/work-centers', name: 'work-centers.index', component: () => import('../pages/engineering/WorkCenters.vue'), meta: { title: 'Work Centers' } },

    // Procurement
    { path: '/suppliers', name: 'suppliers.index', component: () => import('../pages/procurement/Suppliers.vue'), meta: { title: 'Suppliers' } },
    { path: '/purchase-requisitions', name: 'purchase-requisitions.index', component: () => import('../pages/procurement/PurchaseRequisitions.vue'), meta: { title: 'Purchase Requisitions' } },
    { path: '/purchase-orders', name: 'purchase-orders.index', component: () => import('../pages/procurement/PurchaseOrders.vue'), meta: { title: 'Purchase Orders' } },
    { path: '/goods-receipts', name: 'goods-receipts.index', component: () => import('../pages/procurement/GoodsReceipts.vue'), meta: { title: 'Goods Receipts' } },
    { path: '/rfq', name: 'rfq.index', component: () => import('../pages/procurement/Rfq.vue'), meta: { title: 'RFQ' } },

    // Inventory
    { path: '/stock', name: 'stock.index', component: () => import('../pages/inventory/Stock.vue'), meta: { title: 'Stock Balances' } },
    { path: '/stock-movements', name: 'stock-movements.index', component: () => import('../pages/inventory/StockMovements.vue'), meta: { title: 'Stock Movements' } },
    { path: '/stock-counts', name: 'stock-counts.index', component: () => import('../pages/inventory/StockCounts.vue'), meta: { title: 'Stock Counts' } },

    // Production
    { path: '/production-orders', name: 'production-orders.index', component: () => import('../pages/production/ProductionOrders.vue'), meta: { title: 'Production Orders' } },
    { path: '/shop-floor', name: 'shop-floor', component: () => import('../pages/production/ShopFloor.vue'), meta: { title: 'Shop Floor' } },

    // Planning
    { path: '/planning', name: 'planning.index', component: () => import('../pages/planning/Planning.vue'), meta: { title: 'MRP & Planning' } },

    // Sales
    { path: '/customers', name: 'customers.index', component: () => import('../pages/sales/Customers.vue'), meta: { title: 'Customers' } },
    { path: '/quotations', name: 'quotations.index', component: () => import('../pages/sales/Quotations.vue'), meta: { title: 'Quotations' } },
    { path: '/sales-orders', name: 'sales-orders.index', component: () => import('../pages/sales/SalesOrders.vue'), meta: { title: 'Sales Orders' } },
    { path: '/shipments', name: 'shipments.index', component: () => import('../pages/sales/Shipments.vue'), meta: { title: 'Shipments' } },
    { path: '/rma', name: 'rma.index', component: () => import('../pages/sales/Rma.vue'), meta: { title: 'Returns (RMA)' } },

    // Quality
    { path: '/quality-plans', name: 'quality-plans.index', component: () => import('../pages/quality/QualityPlans.vue'), meta: { title: 'Quality Plans' } },
    { path: '/inspections', name: 'inspections.index', component: () => import('../pages/quality/Inspections.vue'), meta: { title: 'Inspections' } },
    { path: '/ncrs', name: 'ncrs.index', component: () => import('../pages/quality/Ncrs.vue'), meta: { title: 'NCR' } },
    { path: '/capa', name: 'capa.index', component: () => import('../pages/quality/Capa.vue'), meta: { title: 'CAPA' } },
    { path: '/calibration', name: 'calibration.index', component: () => import('../pages/quality/Calibration.vue'), meta: { title: 'Calibration' } },
    { path: '/complaints', name: 'complaints.index', component: () => import('../pages/quality/Complaints.vue'), meta: { title: 'Complaints' } },

    // Maintenance
    { path: '/assets', name: 'assets.index', component: () => import('../pages/maintenance/Assets.vue'), meta: { title: 'Assets' } },
    { path: '/maintenance-orders', name: 'maintenance-orders.index', component: () => import('../pages/maintenance/WorkOrders.vue'), meta: { title: 'Work Orders' } },
    { path: '/downtime', name: 'downtime.index', component: () => import('../pages/maintenance/Downtime.vue'), meta: { title: 'Downtime' } },

    // HR
    { path: '/employees', name: 'employees.index', component: () => import('../pages/hr/Employees.vue'), meta: { title: 'Employees' } },
    { path: '/attendance', name: 'attendance.index', component: () => import('../pages/hr/Attendance.vue'), meta: { title: 'Attendance' } },
    { path: '/shifts', name: 'shifts.index', component: () => import('../pages/hr/Shifts.vue'), meta: { title: 'Shifts' } },
    { path: '/skills', name: 'skills.index', component: () => import('../pages/hr/Skills.vue'), meta: { title: 'Skills Matrix' } },
    { path: '/time-bookings', name: 'time-bookings.index', component: () => import('../pages/hr/TimeBookings.vue'), meta: { title: 'Time Bookings' } },
    { path: '/payroll', name: 'payroll.index', component: () => import('../pages/hr/Payroll.vue'), meta: { title: 'Payroll' } },

    // Finance
    { path: '/chart-of-accounts', name: 'chart-of-accounts.index', component: () => import('../pages/finance/ChartOfAccounts.vue'), meta: { title: 'Chart of Accounts' } },
    { path: '/journals', name: 'journals.index', component: () => import('../pages/finance/Journals.vue'), meta: { title: 'Journals' } },
    { path: '/ap-aging', name: 'ap-aging', component: () => import('../pages/finance/ApAging.vue'), meta: { title: 'AP Aging' } },
    { path: '/ar-aging', name: 'ar-aging', component: () => import('../pages/finance/ArAging.vue'), meta: { title: 'AR Aging' } },
    { path: '/budgets', name: 'budgets.index', component: () => import('../pages/finance/Budgets.vue'), meta: { title: 'Budgets' } },
    { path: '/supplier-invoices', name: 'supplier-invoices.index', component: () => import('../pages/finance/SupplierInvoices.vue'), meta: { title: 'Supplier Invoices' } },
    { path: '/customer-invoices', name: 'customer-invoices.index', component: () => import('../pages/finance/CustomerInvoices.vue'), meta: { title: 'Customer Invoices' } },
    { path: '/bank-accounts', name: 'bank-accounts.index', component: () => import('../pages/finance/BankAccounts.vue'), meta: { title: 'Bank Accounts' } },
    { path: '/fixed-assets', name: 'fixed-assets.index', component: () => import('../pages/finance/FixedAssets.vue'), meta: { title: 'Fixed Assets' } },
    { path: '/finance/trial-balance', name: 'finance.trial-balance', component: () => import('../pages/finance/TrialBalance.vue'), meta: { title: 'Trial Balance' } },
    { path: '/finance/balance-sheet', name: 'finance.balance-sheet', component: () => import('../pages/finance/BalanceSheet.vue'), meta: { title: 'Balance Sheet' } },
    { path: '/finance/profit-loss', name: 'finance.profit-loss', component: () => import('../pages/finance/ProfitLoss.vue'), meta: { title: 'Profit & Loss' } },
    { path: '/finance/period-close', name: 'finance.period-close', component: () => import('../pages/finance/PeriodClose.vue'), meta: { title: 'Period Close' } },


    // Purchase Order Form & Detail
    { path: '/purchase-orders/create', name: 'purchase-orders.create', component: () => import('../pages/procurement/PurchaseOrderForm.vue'), meta: { title: 'New PO' } },
    { path: '/purchase-orders/:id/edit', name: 'purchase-orders.edit', component: () => import('../pages/procurement/PurchaseOrderForm.vue'), meta: { title: 'Edit PO' } },
    { path: '/purchase-orders/:id', name: 'purchase-orders.show', component: () => import('../pages/procurement/PurchaseOrderShow.vue'), meta: { title: 'Purchase Order' } },

    // Sales Order Form & Detail
    { path: '/sales-orders/create', name: 'sales-orders.create', component: () => import('../pages/sales/SalesOrderForm.vue'), meta: { title: 'New SO' } },
    { path: '/sales-orders/:id/edit', name: 'sales-orders.edit', component: () => import('../pages/sales/SalesOrderForm.vue'), meta: { title: 'Edit SO' } },
    { path: '/sales-orders/:id', name: 'sales-orders.show', component: () => import('../pages/sales/SalesOrderShow.vue'), meta: { title: 'Sales Order' } },

    // Production Order Form & Detail
    { path: '/production-orders/create', name: 'production-orders.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Production Order', resource: 'production-orders', listPath: '/production-orders', singular: 'Production Order' } },
    { path: '/production-orders/:id', name: 'production-orders.show', component: () => import('../pages/production/ProductionOrderShow.vue'), meta: { title: 'Production Order' } },

    // BOM Form
    { path: '/boms/create', name: 'boms.create', component: () => import('../pages/engineering/BomForm.vue'), meta: { title: 'New BOM' } },
    { path: '/boms/:id/edit', name: 'boms.edit', component: () => import('../pages/engineering/BomForm.vue'), meta: { title: 'Edit BOM' } },

    // Routing Form
    { path: '/routings/create', name: 'routings.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Routing', resource: 'routings', listPath: '/routings', singular: 'Routing' } },
    { path: '/routings/:id/edit', name: 'routings.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Routing', resource: 'routings', listPath: '/routings', singular: 'Routing' } },

    // Journal Form
    { path: '/journals/create', name: 'journals.create', component: () => import('../pages/finance/JournalForm.vue'), meta: { title: 'New Journal Entry' } },

    // Invoice Forms
    { path: '/supplier-invoices/create', name: 'supplier-invoices.create', component: () => import('../pages/finance/SupplierInvoiceForm.vue'), meta: { title: 'New Supplier Invoice' } },
    { path: '/customer-invoices/create', name: 'customer-invoices.create', component: () => import('../pages/finance/CustomerInvoiceForm.vue'), meta: { title: 'New Customer Invoice' } },
    /*
     * Analytics and AI Hub.
     *
     * The screens do not exist yet — every one of these renders ComingSoon, which
     * reads its title, glyph and description straight off the meta below. Building
     * one means swapping its `component`; nothing in the sidebar or the breadcrumb
     * has to change.
     */
    { path: '/analytics/production', name: 'analytics.production', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Production Performance', icon: 'mdi-speedometer', blurb: 'Throughput, yield and scrap by work centre and item, with OEE and attainment against plan over time.' } },
    { path: '/analytics/inventory', name: 'analytics.inventory', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Inventory & Stock Health', icon: 'mdi-chart-donut', blurb: 'Stock turns, ageing and slow movers, ABC classification, and the value sitting still in each warehouse.' } },
    { path: '/analytics/procurement', name: 'analytics.procurement', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Procurement & Spend', icon: 'mdi-chart-bar', blurb: 'Spend by supplier, category and plant, price variance against standard cost, and on-time delivery per supplier.' } },
    { path: '/analytics/sales', name: 'analytics.sales', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Sales & Revenue', icon: 'mdi-trending-up', blurb: 'Revenue by customer, item and period, order-to-ship lead times, and margin once cost of goods is posted.' } },
    { path: '/analytics/quality', name: 'analytics.quality', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Quality Trends', icon: 'mdi-alert-decagram-outline', blurb: 'Defect Pareto, NCR ageing and recurrence, cost of poor quality, and inspection pass rates by item and supplier.' } },
    { path: '/analytics/maintenance', name: 'analytics.maintenance', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Maintenance & Downtime', icon: 'mdi-timer-off', blurb: 'MTBF and MTTR per asset, downtime reasons ranked by hours lost, and planned against unplanned work.' } },
    { path: '/analytics/reports', name: 'analytics.reports', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Report Builder', icon: 'mdi-file-document-outline', blurb: 'Pick a dataset, choose columns and filters, save the result as a named report and schedule it to a mailbox.' } },

    { path: '/ai/chat', name: 'ai.chat', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Chat', icon: 'mdi-message-text-outline', blurb: 'A conversational assistant that can read the ERP — look up an order, explain a variance, draft a purchase requisition.' } },
    { path: '/ai/ask', name: 'ai.ask', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Ask Your Data', icon: 'mdi-comment-question-outline', blurb: 'Questions in plain language answered as a figure, a table or a chart — "which supplier slipped most last quarter?"' } },
    { path: '/ai/forecasting', name: 'ai.forecasting', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Forecasting', icon: 'mdi-crystal-ball', blurb: 'Demand and consumption forecasts per item, feeding reorder levels and the MRP run rather than sitting beside them.' } },
    { path: '/ai/anomalies', name: 'ai.anomalies', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Anomaly Detection', icon: 'mdi-magnify-scan', blurb: 'Watches the transaction stream for the unusual — a price out of line, a yield that dropped, a count that will not reconcile.' } },
    { path: '/ai/documents', name: 'ai.documents', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Document Extraction', icon: 'mdi-file-document-outline', blurb: 'Supplier invoices, order confirmations and certificates read into structured lines for a human to confirm.' } },
    { path: '/ai/insights', name: 'ai.insights', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'Insights', icon: 'mdi-lightbulb-outline', blurb: 'A standing feed of what changed and why it matters, written against the same figures the dashboard draws.' } },
    { path: '/ai/settings', name: 'ai.settings', component: () => import('../pages/ComingSoon.vue'), meta: { title: 'AI Settings', icon: 'mdi-tune', blurb: 'Which model answers, what it is allowed to read, how long conversations are kept, and who may use each feature.' } },

    /*
     * Generated CRUD for the registers that have no hand-built form of their own.
     *
     * Each of these renders ResourceFormPage or ResourceViewPage, which draw
     * themselves from `/schema/{resource}` — the table's own definition — so a
     * column added to a migration reaches the form without a route or a component
     * being edited. `resource` is the API slug, which is not always the path: the
     * RMA register lives at /rma and writes to /rmas.
     *
     * The nine document screens that carry line items keep their own forms; their
     * create and edit routes are above and are not regenerated here.
     */
    { path: '/assets/create', name: 'assets.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Asset', resource: 'assets', listPath: '/assets', singular: 'Asset' } },
    { path: '/assets/:id/edit', name: 'assets.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Asset', resource: 'assets', listPath: '/assets', singular: 'Asset' } },
    { path: '/assets/:id', name: 'assets.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Asset', resource: 'assets', listPath: '/assets', singular: 'Asset' } },
    { path: '/bank-accounts/create', name: 'bank-accounts.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Bank Account', resource: 'bank-accounts', listPath: '/bank-accounts', singular: 'Bank Account' } },
    { path: '/bank-accounts/:id/edit', name: 'bank-accounts.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Bank Account', resource: 'bank-accounts', listPath: '/bank-accounts', singular: 'Bank Account' } },
    { path: '/bank-accounts/:id', name: 'bank-accounts.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Bank Account', resource: 'bank-accounts', listPath: '/bank-accounts', singular: 'Bank Account' } },
    { path: '/boms/:id', name: 'boms.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'BOM', resource: 'boms', listPath: '/boms', singular: 'BOM' } },
    { path: '/calibration/create', name: 'calibration.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Calibration', resource: 'calibrations', listPath: '/calibration', singular: 'Calibration' } },
    { path: '/calibration/:id/edit', name: 'calibration.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Calibration', resource: 'calibrations', listPath: '/calibration', singular: 'Calibration' } },
    { path: '/calibration/:id', name: 'calibration.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Calibration', resource: 'calibrations', listPath: '/calibration', singular: 'Calibration' } },
    { path: '/capa/create', name: 'capa.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New CAPA', resource: 'capas', listPath: '/capa', singular: 'CAPA' } },
    { path: '/capa/:id/edit', name: 'capa.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit CAPA', resource: 'capas', listPath: '/capa', singular: 'CAPA' } },
    { path: '/capa/:id', name: 'capa.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'CAPA', resource: 'capas', listPath: '/capa', singular: 'CAPA' } },
    { path: '/chart-of-accounts/create', name: 'chart-of-accounts.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Account', resource: 'accounts', listPath: '/chart-of-accounts', singular: 'Account' } },
    { path: '/chart-of-accounts/:id/edit', name: 'chart-of-accounts.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Account', resource: 'accounts', listPath: '/chart-of-accounts', singular: 'Account' } },
    { path: '/chart-of-accounts/:id', name: 'chart-of-accounts.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Account', resource: 'accounts', listPath: '/chart-of-accounts', singular: 'Account' } },
    { path: '/companies/create', name: 'companies.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Companie', resource: 'companies', listPath: '/companies', singular: 'Companie' } },
    { path: '/companies/:id/edit', name: 'companies.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Companie', resource: 'companies', listPath: '/companies', singular: 'Companie' } },
    { path: '/companies/:id', name: 'companies.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Companie', resource: 'companies', listPath: '/companies', singular: 'Companie' } },
    { path: '/complaints/create', name: 'complaints.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Complaint', resource: 'complaints', listPath: '/complaints', singular: 'Complaint' } },
    { path: '/complaints/:id/edit', name: 'complaints.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Complaint', resource: 'complaints', listPath: '/complaints', singular: 'Complaint' } },
    { path: '/complaints/:id', name: 'complaints.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Complaint', resource: 'complaints', listPath: '/complaints', singular: 'Complaint' } },
    { path: '/customers/create', name: 'customers.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Customer', resource: 'customers', listPath: '/customers', singular: 'Customer' } },
    { path: '/customers/:id/edit', name: 'customers.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Customer', resource: 'customers', listPath: '/customers', singular: 'Customer' } },
    { path: '/customers/:id', name: 'customers.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Customer', resource: 'customers', listPath: '/customers', singular: 'Customer' } },
    { path: '/employees/create', name: 'employees.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Employee', resource: 'employees', listPath: '/employees', singular: 'Employee' } },
    { path: '/employees/:id/edit', name: 'employees.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Employee', resource: 'employees', listPath: '/employees', singular: 'Employee' } },
    { path: '/employees/:id', name: 'employees.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Employee', resource: 'employees', listPath: '/employees', singular: 'Employee' } },
    { path: '/fixed-assets/create', name: 'fixed-assets.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Fixed Asset', resource: 'fixed-assets', listPath: '/fixed-assets', singular: 'Fixed Asset' } },
    { path: '/fixed-assets/:id/edit', name: 'fixed-assets.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Fixed Asset', resource: 'fixed-assets', listPath: '/fixed-assets', singular: 'Fixed Asset' } },
    { path: '/fixed-assets/:id', name: 'fixed-assets.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Fixed Asset', resource: 'fixed-assets', listPath: '/fixed-assets', singular: 'Fixed Asset' } },
    { path: '/goods-receipts/create', name: 'goods-receipts.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Goods Receipt', resource: 'goods-receipts', listPath: '/goods-receipts', singular: 'Goods Receipt' } },
    { path: '/goods-receipts/:id/edit', name: 'goods-receipts.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Goods Receipt', resource: 'goods-receipts', listPath: '/goods-receipts', singular: 'Goods Receipt' } },
    { path: '/goods-receipts/:id', name: 'goods-receipts.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Goods Receipt', resource: 'goods-receipts', listPath: '/goods-receipts', singular: 'Goods Receipt' } },
    { path: '/inspections/create', name: 'inspections.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Inspection', resource: 'inspections', listPath: '/inspections', singular: 'Inspection' } },
    { path: '/inspections/:id/edit', name: 'inspections.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Inspection', resource: 'inspections', listPath: '/inspections', singular: 'Inspection' } },
    { path: '/inspections/:id', name: 'inspections.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Inspection', resource: 'inspections', listPath: '/inspections', singular: 'Inspection' } },
    { path: '/journals/:id/edit', name: 'journals.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Journal', resource: 'journals', listPath: '/journals', singular: 'Journal' } },
    { path: '/journals/:id', name: 'journals.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Journal', resource: 'journals', listPath: '/journals', singular: 'Journal' } },
    { path: '/maintenance-orders/create', name: 'maintenance-orders.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Maintenance Order', resource: 'maintenance-orders', listPath: '/maintenance-orders', singular: 'Maintenance Order' } },
    { path: '/maintenance-orders/:id/edit', name: 'maintenance-orders.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Maintenance Order', resource: 'maintenance-orders', listPath: '/maintenance-orders', singular: 'Maintenance Order' } },
    { path: '/maintenance-orders/:id', name: 'maintenance-orders.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Maintenance Order', resource: 'maintenance-orders', listPath: '/maintenance-orders', singular: 'Maintenance Order' } },
    { path: '/ncrs/create', name: 'ncrs.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New NCR', resource: 'ncrs', listPath: '/ncrs', singular: 'NCR' } },
    { path: '/ncrs/:id/edit', name: 'ncrs.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit NCR', resource: 'ncrs', listPath: '/ncrs', singular: 'NCR' } },
    { path: '/ncrs/:id', name: 'ncrs.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'NCR', resource: 'ncrs', listPath: '/ncrs', singular: 'NCR' } },
    { path: '/payroll/create', name: 'payroll.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Payroll Run', resource: 'payroll-runs', listPath: '/payroll', singular: 'Payroll Run' } },
    { path: '/payroll/:id/edit', name: 'payroll.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Payroll Run', resource: 'payroll-runs', listPath: '/payroll', singular: 'Payroll Run' } },
    { path: '/payroll/:id', name: 'payroll.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Payroll Run', resource: 'payroll-runs', listPath: '/payroll', singular: 'Payroll Run' } },
    { path: '/plants/create', name: 'plants.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Plant', resource: 'plants', listPath: '/plants', singular: 'Plant' } },
    { path: '/plants/:id/edit', name: 'plants.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Plant', resource: 'plants', listPath: '/plants', singular: 'Plant' } },
    { path: '/plants/:id', name: 'plants.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Plant', resource: 'plants', listPath: '/plants', singular: 'Plant' } },
    { path: '/production-orders/:id/edit', name: 'production-orders.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Production Order', resource: 'production-orders', listPath: '/production-orders', singular: 'Production Order' } },
    { path: '/purchase-requisitions/create', name: 'purchase-requisitions.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Purchase Requisition', resource: 'purchase-requisitions', listPath: '/purchase-requisitions', singular: 'Purchase Requisition' } },
    { path: '/purchase-requisitions/:id/edit', name: 'purchase-requisitions.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Purchase Requisition', resource: 'purchase-requisitions', listPath: '/purchase-requisitions', singular: 'Purchase Requisition' } },
    { path: '/purchase-requisitions/:id', name: 'purchase-requisitions.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Purchase Requisition', resource: 'purchase-requisitions', listPath: '/purchase-requisitions', singular: 'Purchase Requisition' } },
    { path: '/quality-plans/create', name: 'quality-plans.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Quality Plan', resource: 'quality-plans', listPath: '/quality-plans', singular: 'Quality Plan' } },
    { path: '/quality-plans/:id/edit', name: 'quality-plans.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Quality Plan', resource: 'quality-plans', listPath: '/quality-plans', singular: 'Quality Plan' } },
    { path: '/quality-plans/:id', name: 'quality-plans.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Quality Plan', resource: 'quality-plans', listPath: '/quality-plans', singular: 'Quality Plan' } },
    { path: '/quotations/create', name: 'quotations.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Quotation', resource: 'quotations', listPath: '/quotations', singular: 'Quotation' } },
    { path: '/quotations/:id/edit', name: 'quotations.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Quotation', resource: 'quotations', listPath: '/quotations', singular: 'Quotation' } },
    { path: '/quotations/:id', name: 'quotations.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Quotation', resource: 'quotations', listPath: '/quotations', singular: 'Quotation' } },
    { path: '/rma/create', name: 'rma.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New RMA', resource: 'rmas', listPath: '/rma', singular: 'RMA' } },
    { path: '/rma/:id/edit', name: 'rma.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit RMA', resource: 'rmas', listPath: '/rma', singular: 'RMA' } },
    { path: '/rma/:id', name: 'rma.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'RMA', resource: 'rmas', listPath: '/rma', singular: 'RMA' } },
    { path: '/routings/:id', name: 'routings.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Routing', resource: 'routings', listPath: '/routings', singular: 'Routing' } },
    { path: '/shipments/create', name: 'shipments.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Shipment', resource: 'shipments', listPath: '/shipments', singular: 'Shipment' } },
    { path: '/shipments/:id/edit', name: 'shipments.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Shipment', resource: 'shipments', listPath: '/shipments', singular: 'Shipment' } },
    { path: '/shipments/:id', name: 'shipments.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Shipment', resource: 'shipments', listPath: '/shipments', singular: 'Shipment' } },
    { path: '/skills/create', name: 'skills.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Skill', resource: 'skills', listPath: '/skills', singular: 'Skill' } },
    { path: '/skills/:id/edit', name: 'skills.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Skill', resource: 'skills', listPath: '/skills', singular: 'Skill' } },
    { path: '/skills/:id', name: 'skills.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Skill', resource: 'skills', listPath: '/skills', singular: 'Skill' } },
    { path: '/stock/create', name: 'stock.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Stock Balance', resource: 'stock', listPath: '/stock', singular: 'Stock Balance' } },
    { path: '/stock/:id/edit', name: 'stock.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Stock Balance', resource: 'stock', listPath: '/stock', singular: 'Stock Balance' } },
    { path: '/stock/:id', name: 'stock.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Stock Balance', resource: 'stock', listPath: '/stock', singular: 'Stock Balance' } },
    { path: '/stock-movements/create', name: 'stock-movements.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Stock Movement', resource: 'stock-movements', listPath: '/stock-movements', singular: 'Stock Movement' } },
    { path: '/stock-movements/:id/edit', name: 'stock-movements.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Stock Movement', resource: 'stock-movements', listPath: '/stock-movements', singular: 'Stock Movement' } },
    { path: '/stock-movements/:id', name: 'stock-movements.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Stock Movement', resource: 'stock-movements', listPath: '/stock-movements', singular: 'Stock Movement' } },
    { path: '/suppliers/create', name: 'suppliers.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Supplier', resource: 'suppliers', listPath: '/suppliers', singular: 'Supplier' } },
    { path: '/suppliers/:id/edit', name: 'suppliers.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Supplier', resource: 'suppliers', listPath: '/suppliers', singular: 'Supplier' } },
    { path: '/suppliers/:id', name: 'suppliers.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Supplier', resource: 'suppliers', listPath: '/suppliers', singular: 'Supplier' } },
    { path: '/time-bookings/create', name: 'time-bookings.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Time Booking', resource: 'time-bookings', listPath: '/time-bookings', singular: 'Time Booking' } },
    { path: '/time-bookings/:id/edit', name: 'time-bookings.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Time Booking', resource: 'time-bookings', listPath: '/time-bookings', singular: 'Time Booking' } },
    { path: '/time-bookings/:id', name: 'time-bookings.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Time Booking', resource: 'time-bookings', listPath: '/time-bookings', singular: 'Time Booking' } },
    { path: '/uoms/create', name: 'uoms.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New UOM', resource: 'uoms', listPath: '/uoms', singular: 'UOM' } },
    { path: '/uoms/:id/edit', name: 'uoms.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit UOM', resource: 'uoms', listPath: '/uoms', singular: 'UOM' } },
    { path: '/uoms/:id', name: 'uoms.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'UOM', resource: 'uoms', listPath: '/uoms', singular: 'UOM' } },
    { path: '/users/create', name: 'users.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New User', resource: 'users', listPath: '/users', singular: 'User' } },
    { path: '/users/:id/edit', name: 'users.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit User', resource: 'users', listPath: '/users', singular: 'User' } },
    { path: '/users/:id', name: 'users.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'User', resource: 'users', listPath: '/users', singular: 'User' } },
    { path: '/warehouses/create', name: 'warehouses.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Warehouse', resource: 'warehouses', listPath: '/warehouses', singular: 'Warehouse' } },
    { path: '/warehouses/:id/edit', name: 'warehouses.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Warehouse', resource: 'warehouses', listPath: '/warehouses', singular: 'Warehouse' } },
    { path: '/warehouses/:id', name: 'warehouses.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Warehouse', resource: 'warehouses', listPath: '/warehouses', singular: 'Warehouse' } },
    { path: '/work-centers/create', name: 'work-centers.create', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'New Work Center', resource: 'work-centers', listPath: '/work-centers', singular: 'Work Center' } },
    { path: '/work-centers/:id/edit', name: 'work-centers.id.edit', component: () => import('../pages/ResourceFormPage.vue'), meta: { title: 'Edit Work Center', resource: 'work-centers', listPath: '/work-centers', singular: 'Work Center' } },
    { path: '/work-centers/:id', name: 'work-centers.id', component: () => import('../pages/ResourceViewPage.vue'), meta: { title: 'Work Center', resource: 'work-centers', listPath: '/work-centers', singular: 'Work Center' } },

    // System
    { path: '/audit-log', name: 'audit-log', component: () => import('../pages/system/AuditLog.vue'), meta: { title: 'Audit Log' } },
    { path: '/settings', name: 'settings', component: () => import('../pages/system/Settings.vue'), meta: { title: 'Settings' } },

    // Anything else. `guest` so it renders without the chrome rather than
    // bouncing an unknown URL through the auth guard to /login.
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('../pages/NotFound.vue'), meta: { guest: true, title: 'Page not found' } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() { return { top: 0 }; },
});

// Auth guard
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');
    if (!to.meta.guest && !token) {
        next({ name: 'login' });
    } else if (to.meta.guest && token && to.name !== 'not-found') {
        next({ name: 'dashboard' });
    } else {
        document.title = (to.meta.title || 'Factory ERP') + ' — Factory ERP';
        next();
    }
});

export default router;
