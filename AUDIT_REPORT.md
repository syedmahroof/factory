# 📋 Factory ERP — Full Audit Report

**Date:** September 2, 2026  
**SRS Version:** 1.0 — Advanced Factory Management System  
**Audit Scope:** All 12 modules, API layer, Vue.js SPA, database, security

---

## Executive Summary

| Metric | SRS Required | Implemented | Coverage |
|--------|-------------|-------------|----------|
| Functional Requirements (Must) | ~85 | ~50 | **59%** |
| Functional Requirements (Should) | ~25 | ~10 | **40%** |
| Vue Pages | — | 72 | ✅ |
| API Controllers | — | 44 | ✅ |
| Eloquent Models | — | 170 | ✅ |
| Action Classes | — | 40 (Factory) | ⚠️ |
| Database Migrations | — | 110 | ✅ |

---

## 🔴 CRITICAL GAPS — Must Requirements Not Implemented

### 1. Multi-Company / Multi-Plant Scoping

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| MDM-001 | Organization hierarchy with effective dating | ⚠️ Partial | Tables exist but no effective-dating on hierarchy changes. No hierarchy tree view. |
| MDM-003 | Revision control for BOM/formula/routing | ❌ Missing | No `effective_date` enforcement. No revision history. Only latest version accessible. |
| MDM-005 | Configurable document sequences by company/year | ⚠️ Partial | `document_sequences` table exists but no auto-increment logic in API controllers. |
| MDM-006 | Draft→Submitted→Approved→Rejected lifecycle | ⚠️ Partial | Statuses exist in DB but no state machine validation. Controllers accept any status change. |
| MDM-007 | Attachments with version/permission controls | ❌ Missing | `attachments` table exists but no upload API, no file storage, no permission check. |
| MDM-008 | Custom attributes by entity/category | ❌ Missing | No implementation at all. No EAV pattern or custom fields table. |

### 2. Product Engineering

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| ENG-001 | Multi-level BOM with alternate/phantom/configurable | ⚠️ Partial | Flat BOM only. No `parent_bom_id`, no phantom BOM flag, no alternates. |
| ENG-002 | Formula/recipe with potency, yield, co-products | ❌ Missing | `formulas` table exists but no FormulaController, no Vue page, no scaling logic. |
| ENG-004 | ECR/ECO workflow | ❌ Missing | `engineering_changes` table exists but no controller, no Vue page, no approval workflow. |
| ENG-005 | Production version linking BOM+Routing+Plant | ⚠️ Partial | `production_versions` table exists but no API or UI to manage them. |
| ENG-006 | Document control (drawings, SOPs, acknowledgments) | ❌ Missing | No document management system. No file upload/download API. |
| ENG-007 | Cost roll-up (material+labor+machine+overhead) | ❌ Missing | `cost_rollups` table exists but no calculation logic. |

### 3. Sales & Demand

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| SAL-003 | Demand management (forecasts, safety stock, inter-plant) | ⚠️ Partial | `forecasts` table exists but no demand calculation logic. |
| SAL-004 | ATP/CTP (Available/Capable to Promise) | ❌ Missing | No ATP check in any controller. No capacity validation. |
| SAL-005 | Order allocation (reserve FG or link MTO) | ❌ Missing | `stock_reservations` table exists but no allocation API. |
| SAL-006 | Dispatch: pick, pack, delivery note, e-way, invoice | ⚠️ Partial | Shipments exist but no pick/pack workflow, no delivery note generation, no e-way fields. |
| SAL-007 | Customer returns (RMA) with full workflow | ⚠️ Partial | RMA model exists but no inspection/quarantine/replacement logic. |
| SAL-008 | Credit control (limit, overdue, hold, override) | ❌ Missing | `customer_credit_exposures` table exists but no credit check in SO creation. |

### 4. Production Planning

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| PLN-001 | Planning calendar (shifts, holidays, maintenance) | ⚠️ Partial | Tables exist but no calendar UI or capacity exclusion logic. |
| PLN-002 | MPS (Master Production Schedule) | ❌ Missing | No MPS controller, no time-phased scheduling UI. |
| PLN-003 | MRP engine with full logic | ⚠️ Partial | `RunMrp` action exists but missing: lot sizing, lead time offset, safety stock logic, pegging. |
| PLN-005 | Finite capacity scheduling | ❌ Missing | No capacity load calculation. No work center scheduling. |
| PLN-006 | Material availability / shortage list | ❌ Missing | No shortage report. No material availability check. |
| PLN-008 | Rescheduling alerts | ❌ Missing | No alert system for late supply/demand changes. |

### 5. Procurement

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| PUR-001 | Supplier qualification (category approval, certifications, rating) | ⚠️ Partial | `supplier_certifications` table exists but no qualification workflow or block logic. |
| PUR-003 | RFQ comparison (price, tax, freight, lead time, quality) | ❌ Missing | RFQ model exists but no comparison matrix, no award workflow. |
| PUR-005 | Subcontracting | ❌ Missing | No subcontracting module at all. No component issue tracking. |
| PUR-006 | Supplier delivery schedule & overdue follow-up | ❌ Missing | No delivery schedule API. No overdue alert logic. |
| PUR-007 | Landed cost allocation | ⚠️ Partial | `landed_costs` table and `CalculateLandedCost` action exist but no allocation logic. |
| PUR-008 | Three-way match (PO-GRN-Invoice) | ⚠️ Partial | `three_way_matches` table exists but no matching logic in controllers. |
| PUR-009 | Supplier scorecard (OTIF, rejection, price, responsiveness) | ❌ Missing | `supplier_scorecards` table exists but no calculation or UI. |

### 6. Inventory & Warehouse

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| INV-002 | ASN/GRN with barcode scan, quarantine, bin suggestion | ⚠️ Partial | GRN exists but no barcode API, no quarantine flow, no bin suggestion algorithm. |
| INV-003 | Backflush consumption | ❌ Missing | `CompleteProductionOrder` does not auto-consume BOM components. |
| INV-005 | Lot/batch/serial/heat tracking | ⚠️ Partial | Tables exist (`lots`, `serials`) but no tracking in stock movements. |
| INV-006 | Expiry control, FEFO, shelf-life | ❌ Missing | No expiry date checking. No FEFO logic in picking. No near-expiry alerts. |
| INV-008 | Status stock (available/quarantine/hold/rejected) | ⚠️ Partial | `stock_statuses` table exists but no status-based eligibility check. |
| INV-009 | Barcode/QR generation and scanning | ❌ Missing | No barcode generation. No scan validation API. |
| INV-010 | Valuation (FIFO/moving average/standard) | ❌ Missing | Stock movements record cost but no FIFO queue. No moving average recalculation. |

### 7. Production Execution

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| MFG-002 | Operation dispatch to work center/machine/team | ❌ Missing | No dispatch queue. No work center assignment UI. |
| MFG-003 | Material staging (reservation, pick list, kit/stage) | ❌ Missing | No material reservation for production. No pick list generation. |
| MFG-005 | Batch record (ingredients, weights, process values) | ⚠️ Partial | `batch_records` table exists but no capture API or UI. |
| MFG-006 | Serial genealogy (parent-child) | ⚠️ Partial | `serial_genealogies` table exists but no linkage during production. |
| MFG-007 | Backflush with tolerance | ❌ Missing | No backflush logic at all. |
| MFG-008 | Rework order | ⚠️ Partial | `rework_orders` table exists but no creation/disposition logic. |
| MFG-009 | Scrap/by-products with valuation | ⚠️ Partial | `scrap_records` table exists but no recording API. |
| MFG-010 | WIP tracking by order/operation/work center | ⚠️ Partial | `wip_balances` table exists but no update logic during production. |
| MFG-011 | Shift handover | ❌ Missing | No shift handover feature. |
| MFG-012 | Technical close + financial close with variance | ⚠️ Partial | `CompleteProductionOrder` exists but no variance calculation. |

### 8. Quality Management

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| QMS-002 | Incoming inspection with sample/results | ⚠️ Partial | `CreateInspection` exists but no sample recording, no result entry API. |
| QMS-003 | In-process quality gates | ❌ Missing | `quality_gates` table exists but no gate enforcement in production flow. |
| QMS-004 | Final inspection + COA generation | ❌ Missing | `certificate_of_analysis` table exists but no generation logic. |
| QMS-005 | NCR with MRB decision, disposition | ⚠️ Partial | NCR model exists but no MRB workflow, no disposition logic. |
| QMS-007 | SPC (control charts, trends, out-of-control alerts) | ❌ Missing | No SPC implementation at all. |
| QMS-008 | Calibration with out-of-calibration impact | ⚠️ Partial | Calibration model exists but no impact on inspections. No expiry blocking. |
| QMS-009 | Complaints and recall with traceability | ⚠️ Partial | Models exist but no recall traceability to affected lots/shipments/customers. |

### 9. Maintenance

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| MNT-002 | Preventive + condition-based plans with checklist | ⚠️ Partial | `preventive_maintenance_schedules` table exists but no auto-generation of work orders. |
| MNT-005 | Spare parts (min-max, reservations, issue/return) | ⚠️ Partial | `spare_parts` table exists but no inventory integration. |
| MNT-006 | Meter readings with condition alarms | ⚠️ Partial | `meter_readings` table exists but no alarm trigger logic. |
| MNT-007 | Tooling (life counters, location, maintenance limits) | ⚠️ Partial | `tool_registrations` table exists but no life counter logic. |
| MNT-008 | OEE with drill-down | ⚠️ Partial | `CalculateOee` action exists but no drill-down to orders/downtime. |

### 10. HR & Workforce

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| HRM-001 | Employee master with sensitive field access control | ⚠️ Partial | Employee model exists but no field-level access control. |
| HRM-002 | Shift roster with overtime, crew allocation | ⚠️ Partial | `shift_rosters` table exists but no roster management API/UI. |
| HRM-003 | Biometric/device/manual attendance with approval | ⚠️ Partial | Attendance exists but no device integration, no correction approval workflow. |
| HRM-004 | Skill matrix with qualification blocking | ⚠️ Partial | `skill_matrix` table exists but no assignment warning/block logic. |
| HRM-006 | Safety incidents and permit-to-work | ⚠️ Partial | Tables exist but no incident reporting UI, no permit blocking logic. |
| HRM-007 | Payroll with earnings/deductions/payslips/accounting | ⚠️ Partial | `payroll_runs` exists but no payslip generation, no accounting entry posting. |

### 11. Finance

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| FIN-002 | Dimensions (plant, department, cost center, project) | ❌ Missing | `journal_dimensions` table exists but no dimension selection in journal form. |
| FIN-004 | AP workflow (invoice, debit note, advances, payment, aging) | ⚠️ Partial | `CreateAccountsPayable` exists but no debit notes, no advance tracking. |
| FIN-005 | AR workflow (invoice, credit note, receipt, allocation) | ⚠️ Partial | `CreateAccountsReceivable` exists but no credit notes, no receipt allocation. |
| FIN-006 | Bank reconciliation | ❌ Missing | `bank_reconciliations` table exists but no reconciliation API or UI. |
| FIN-007 | Tax (GST/VAT/TDS configurable) | ⚠️ Partial | `tax_rules` table exists but no tax calculation in transactions. |
| FIN-008 | Fixed assets (depreciation, transfer, impairment, disposal) | ⚠️ Partial | Models exist but no depreciation run, no transfer/disposal API. |
| FIN-009 | Budgets with commitment control | ⚠️ Partial | `budget_lines` table exists but no budget check in PR/PO creation. |
| FIN-010 | Standard cost with cost roll-up | ❌ Missing | No standard cost calculation. No cost roll-up logic. |
| FIN-011 | Actual costing by order/batch | ❌ Missing | No actual cost accumulation during production. |
| FIN-012 | Variance analysis (price/usage/rate/efficiency/yield) | ❌ Missing | No variance calculation or reporting. |
| FIN-014 | Cash flow statement | ❌ Missing | Trial balance, P&L, balance sheet exist but no cash flow statement. |

### 12. Security & Compliance

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| SEC-RBAC-001 | Scoped permissions (company/plant/warehouse) | ⚠️ Partial | Policies exist but no scope filtering in queries. All users see all data. |
| SEC-RBAC-002 | Approval matrix (document, amount, department, escalation) | ⚠️ Partial | `approval_rules` table exists but no enforcement in controllers. |
| SEC-RBAC-003 | Time-bound delegation | ❌ Missing | No delegation feature. |
| SEC-RBAC-004 | Segregation of duties | ❌ Missing | No incompatible role detection. |
| NSEC-001 | MFA/SSO readiness | ❌ Missing | No MFA. No SSO/SAML integration. |
| NSEC-002 | Session protection (timeout, device visibility, revocation) | ⚠️ Partial | Sanctum token exists but no session timeout, no device tracking. |
| NSEC-005 | Comprehensive audit log | ⚠️ Partial | AuditLog model exists but not wired into all controllers. No before/after values. |
| NSEC-006 | Period locks, immutable posted documents | ⚠️ Partial | Period close exists but no enforcement. Posted journals can be edited. |
| NSEC-009 | Audit evidence (approval history, digital evidence) | ❌ Missing | No approval history tracking. No digital evidence storage. |

### 13. Reports & Dashboards

| SRS ID | Requirement | Status | Gap |
|--------|------------|--------|-----|
| RPT-001 | Filters (company, plant, date, shift, department, item) | ❌ Missing | Dashboard has no filters. No drill-down capability. |
| RPT-002 | Drill-down (KPI → summary → source transaction) | ❌ Missing | No drill-down links. KPIs are static counts. |
| RPT-003 | CSV/XLSX/PDF export with queued processing | ❌ Missing | No export buttons. No queued export jobs. |
| RPT-004 | Scheduled report delivery (email) | ❌ Missing | No report scheduling. No email delivery. |
| RPT-005 | Threshold and anomaly alerts | ❌ Missing | No alert system. No threshold configuration. |
| RPT-006 | Central KPI definitions with consistent calculation | ❌ Missing | KPIs are hardcoded in dashboard controller. No central definition. |

### 14. Integrations & APIs

| Feature | Status | Gap |
|---------|--------|-----|
| Barcode/mobile scanner integration | ❌ Missing | No scan API. No GS1 label generation. |
| Biometric attendance | ❌ Missing | No device integration. |
| IoT/SCADA/MQTT bridge | ❌ Missing | No machine data ingestion. |
| Weighing scale integration | ❌ Missing | No scale API. |
| E-invoicing/tax portal | ❌ Missing | No tax portal integration. |
| CRM/e-commerce/EDI | ❌ Missing | No external system sync. |
| Shipping/logistics API | ❌ Missing | No carrier booking or tracking. |
| Email/SMS/WhatsApp notifications | ⚠️ Partial | `FactoryNotification` class exists but no SMS/WhatsApp. No queue dispatch. |
| SSO (SAML/OIDC) | ❌ Missing | No identity provider integration. |
| BI tool connector | ❌ Missing | No read-only data warehouse or BI API. |

### 15. Non-Functional Requirements

| Category | SRS Target | Current Status |
|----------|-----------|----------------|
| Performance | ≤2s p95 for list/detail | ⚠️ Not tested |
| Availability | 99.9% monthly | ❌ No health checks, no redundancy |
| Localization | Timezone, language, currency per company | ❌ Hardcoded to USD, English, UTC |
| Accessibility | WCAG 2.1 AA | ❌ Not implemented |
| PWA support | Installable progressive web app | ❌ No service worker, no manifest |
| Browser support | Chrome/Edge/Firefox/Safari | ⚠️ Untested on Safari |

---

## 🟡 MODERATE GAPS — Should Requirements Not Implemented

### Missing Action Classes

| Module | Missing Action | Impact |
|--------|---------------|--------|
| Sales | `CreateQuotation` | Cannot create quotations via API |
| Sales | `ConfirmQuotation` → SO conversion | Quotation-to-order traceability lost |
| Sales | `CreateShipment` with pick/pack | No dispatch workflow |
| Procurement | `CreatePurchaseRequisition` | No requisition creation |
| Procurement | `ReceiveGoods` with 3-way match | No matching validation |
| Quality | `CreateNcr` | No NCR creation via API |
| Quality | `RecordInspectionResult` | No result recording |
| Quality | `CreateCalibration` | No calibration recording |
| Maintenance | `CreateAsset` | No asset creation |
| Maintenance | `SchedulePreventiveMaintenance` | No PM scheduling |
| HR | `CreateShift` | No shift creation |
| HR | `CreateLeaveRequest` | No leave management |
| Finance | `CreateBudget` | No budget creation |
| Finance | `RecordBankReconciliation` | No reconciliation |
| Finance | `RunDepreciation` | No depreciation calculation |

### Missing Vue Pages (List pages exist but no form/detail)

| Module | Missing Page | Priority |
|--------|-------------|----------|
| Organization | CompanyForm, PlantForm, WarehouseForm, UserForm | Should |
| HR | EmployeeForm, ShiftForm | Should |
| Quality | NcrForm, InspectionForm, QualityPlanForm | Should |
| Maintenance | AssetForm, WorkOrderForm | Should |
| Inventory | StockMovementForm (dedicated) | Should |

### Missing API Endpoints

| Module | Missing Endpoint | Purpose |
|--------|-----------------|---------|
| Sales | `POST /quotations/{id}/confirm` | Convert quotation to SO |
| Sales | `GET /sales-orders/{id}/atp` | Available-to-promise check |
| Procurement | `POST /purchase-requisitions` | Create requisition |
| Production | `GET /production-orders/{id}/material-availability` | Check BOM availability |
| Quality | `POST /inspections/{id}/results` | Record inspection results |
| Quality | `POST /ncrs` | Create NCR |
| Finance | `POST /accounts/reconcile` | Bank reconciliation |
| Finance | `GET /finance/cash-flow` | Cash flow statement |
| System | `POST /notifications/mark-read` | Mark notifications read |

---

## 🔵 MINOR GAPS — Could/Nice-to-Have

| Feature | Status | Notes |
|---------|--------|-------|
| SPC control charts | ❌ | No statistical process control |
| Predictive maintenance | ❌ | No IoT/anomaly detection |
| AI-assisted exceptions | ❌ | No ML/AI features |
| Supplier/customer portals | ❌ | No external user portal |
| Mobile app (native) | ❌ | API exists but no mobile app |
| E-signature | ❌ | No electronic signature support |
| WebSocket real-time updates | ❌ | No Laravel Echo/Pusher setup |
| PDF report generation | ❌ | No DomPDF installed |
| Import/export wizards | ❌ | No bulk import UI |
| Print-optimized views | ❌ | No print CSS or PDF templates |

---

## 🐛 BUGS & ISSUES FOUND

### Critical Bugs

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| 1 | **Unscoped data access** — All API queries return all companies' data | All API Controllers | Data leak across companies |
| 2 | **No auth guard on most API routes** — Sanctum middleware exists but policies not enforced | `routes/api.php` | Any authenticated user can access all data |
| 3 | **Posted journals editable** — No immutability check on posted financial documents | `JournalController@update` | Financial data integrity compromised |
| 4 | **No CSRF validation on API** — Token-based but web routes lack CSRF on some forms | Web routes | Potential CSRF attacks |
| 5 | **Stock movements don't update balances atomically** — Race condition possible | `CreateStockMovement` | Inventory discrepancy under concurrent access |

### Moderate Bugs

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| 6 | **MRP doesn't calculate net demand** — RunMrp just counts items, no actual MRP logic | `Planning/RunMrp` | MRP feature is non-functional |
| 7 | **CompleteProductionOrder doesn't backflush** — No BOM consumption | `Production/CompleteProductionOrder` | Inventory not updated after production |
| 8 | **Journal form allows unbalanced posting** — Client-side check only, server doesn't validate | `JournalController@store` | Unbalanced journals can be posted |
| 9 | **No duplicate detection** — Items, customers, suppliers can have duplicate codes | All resource controllers | Master data quality issues |
| 10 | **API responses inconsistent** — Some return `data.data`, others return `data` directly | Various API controllers | Frontend parsing errors |
| 11 | **Missing model `$fillable`** — Some models may not have proper fillable arrays | Various models | Mass assignment vulnerabilities |
| 12 | **No pagination on some endpoints** — Dashboard returns unbounded queries | `DashboardController` | Performance degradation with large datasets |
| 13 | **Vue router missing catch-all** — 404 pages not handled for SPA routes | `router/index.js` | Blank page on direct URL access |
| 14 | **Toast notification `remove` function bug** — Uses `splice` on reactive array incorrectly | `ToastContainer.vue` | Toast dismiss may not work |
| 15 | **No error boundary** — Vue app crashes on API errors without fallback UI | All pages | Poor user experience on failures |

### Minor Bugs / Code Quality

| # | Issue | Location | Impact |
|---|-------|----------|--------|
| 16 | **Old MCA models still in codebase** — `Merchant`, `Investor`, `Lender` models unused | `app/Models/` | Code bloat, confusion |
| 17 | **Old MCA API controllers still present** — `Api/V1/Admin/*` controllers | `app/Http/Controllers/Api/V1/Admin/` | Route conflicts possible |
| 18 | **Old MCA actions still present** — `Ach/*`, `Merchant/*`, `Investor/*`, `Lender/*` | `app/Actions/` | Code bloat |
| 19 | **Missing `.env.example` updates** — No Factory ERP specific env vars | `.env.example` | Setup confusion |
| 20 | **No API documentation** — No OpenAPI/Swagger spec | — | Developer onboarding difficult |
| 21 | **No rate limiting** — API endpoints have no rate limiting | `routes/api.php` | Abuse potential |
| 22 | **No idempotency keys** — Create/posting endpoints not idempotent | All POST endpoints | Duplicate transactions possible |
| 23 | **Hardcoded USD currency** — No multi-currency support | Throughout | International use blocked |

---

## 📊 Coverage by Module

| Module | SRS Requirements | Implemented | Coverage |
|--------|-----------------|-------------|----------|
| Organization & Master Data | 8 | 4 | 50% |
| Product Engineering | 7 | 3 | 43% |
| Sales & Demand | 8 | 3 | 38% |
| Production Planning | 8 | 2 | 25% |
| Procurement | 9 | 4 | 44% |
| Inventory & Warehouse | 10 | 4 | 40% |
| Production Execution | 12 | 4 | 33% |
| Quality Management | 10 | 4 | 40% |
| Maintenance & Assets | 10 | 4 | 40% |
| HR & Workforce | 8 | 3 | 38% |
| Finance & Costing | 14 | 6 | 43% |
| Security & Compliance | 9 | 3 | 33% |
| Reports & Dashboards | 6 | 1 | 17% |
| Integrations | 10 | 1 | 10% |
| **TOTAL** | **~127** | **~46** | **36%** |

---

## 🎯 Recommended Priority Fix Order

### Phase 1 — Data Integrity (Week 1-2)
1. Add company/plant scoping to ALL API queries
2. Enforce journal immutability (no edit/delete of posted documents)
3. Make stock movements atomic with proper locking
4. Add server-side balanced journal validation
5. Add duplicate detection for master data

### Phase 2 — Core Business Logic (Week 3-4)
6. Implement real MRP engine with net demand, lead times, lot sizing
7. Add BOM backflush in CompleteProductionOrder
8. Build ATP/CTP check for sales orders
9. Add credit control check in SO creation
10. Wire up all policies for scoped authorization

### Phase 3 — Missing Features (Week 5-8)
11. Build Formula/Recipe management (controller + Vue page)
12. Build Engineering Change (ECR/ECO) workflow
13. Build shift handover feature
14. Build batch record capture
15. Build serial genealogy tracking
16. Build SPC control charts
17. Build bank reconciliation
18. Build quotation-to-order conversion

### Phase 4 — Reports & Polish (Week 9-10)
19. Add report filters and drill-down
20. Add CSV/PDF export for all tables
21. Add scheduled report email delivery
22. Add threshold alerts
23. Build cash flow statement
24. Remove old MCA codebase files

### Phase 5 — Security & NFR (Week 11-12)
25. Implement MFA
26. Add API rate limiting
27. Add OpenAPI documentation
28. Add comprehensive audit logging
29. Add PWA support
30. Performance testing and optimization

---

## 📁 Files to Clean Up (Old MCA Code)

The following files from the old MCA syndication platform should be removed:

- `app/Models/Merchant*.php` (~15 files)
- `app/Models/Investor*.php` (~5 files)
- `app/Models/Lender*.php` (~2 files)
- `app/Models/SyndicationPayment.php`
- `app/Models/ActumRequest.php`
- `app/Models/ActumWebhookEvent.php`
- `app/Http/Controllers/Api/V1/Admin/*` (~30 files)
- `app/Http/Controllers/Admin/*` (~2 files)
- `app/Actions/Merchant/*` (~20 files)
- `app/Actions/Investor/*` (~15 files)
- `app/Actions/Lender/*` (~6 files)
- `app/Actions/Ach/*` (~10 files)
- `app/Actions/Actum/*` (~3 files)
- `app/Actions/Report/*` (~12 files — MCA-specific reports)
- `resources/js/pages/admin/*` (~60 files)
- `resources/views/Admin/*` (old Blade views)

---

*Audit generated on September 2, 2026 by automated codebase analysis against SRS v1.0*
