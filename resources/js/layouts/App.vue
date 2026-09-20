<template>
  <!-- Guest layout (login) — no chrome -->
  <div v-if="route.meta.guest" class="min-h-screen">
    <router-view />
  </div>

  <!-- Authenticated layout — full sidebar + header -->
  <div v-else class="min-h-screen bg-hb-hover" :class="collapsed && 'nav-collapsed'">
    <!-- #page-topbar -->
    <header class="fixed inset-x-0 top-0 z-[1002] h-topbar bg-hb-brand">
      <div class="mx-auto flex h-topbar items-center justify-between pr-3">
        <div class="flex h-full">
          <!-- .navbar-brand-box -->
          <router-link to="/" data-brand
            class="hidden h-topbar w-sidebar shrink-0 items-center justify-center bg-white px-3 lg:flex">
            <BrandMark :mark="collapsed" />
          </router-link>

          <button type="button"
            class="flex h-topbar items-center px-3 text-2xl text-header-item hover:text-white"
            aria-label="Toggle navigation" @click="toggleNav">
            <i class="mdi mdi-backburger" />
          </button>
        </div>

        <div class="flex h-full items-center">
          <!-- Settings gear disc -->
          <a href="/settings"
            class="mr-1 flex size-9 items-center justify-center rounded-full bg-white/10 text-[19px] text-header-item transition-colors hover:bg-white/20 hover:text-white"
            :class="route.path === '/settings' && 'bg-white/20 text-white'"
            aria-label="General settings">
            <i class="mdi mdi-settings-outline" />
          </a>

          <!-- User dropdown -->
          <div ref="userMenuRef" class="relative h-full">
            <button type="button"
              class="flex h-topbar items-center gap-2 px-3 text-header-item transition-colors hover:text-white"
              :class="userMenuOpen && 'text-white'"
              @click="userMenuOpen = !userMenuOpen">
              <span
                class="flex size-8 items-center justify-center rounded-full bg-white/20 text-hb-sm font-bold text-white ring-white/30 transition-all"
                :class="userMenuOpen && 'ring-2'">
                {{ initials }}
              </span>
              <span class="hidden text-hb-body sm:inline-block">{{ authStore.user?.name }}</span>
              <i class="mdi mdi-chevron-down hidden transition-transform sm:inline-block"
                :class="userMenuOpen && 'rotate-180'" />
            </button>

            <!-- Dropdown panel -->
            <Transition enter-active-class="transition duration-150 ease-out"
              enter-from-class="-translate-y-1 scale-95 opacity-0"
              leave-active-class="transition duration-100 ease-in"
              leave-to-class="-translate-y-1 scale-95 opacity-0">
              <div v-if="userMenuOpen"
                class="absolute right-2 top-[calc(var(--spacing-topbar)-8px)] w-64 origin-top-right overflow-hidden rounded-hb-panel border border-hb-line bg-white shadow-hb-sheet"
                role="menu" @click="userMenuOpen = false">
                <!-- Profile card -->
                <div class="flex items-start gap-3 bg-hb-sel px-4 py-3.5">
                  <span
                    class="flex size-10 flex-none items-center justify-center rounded-full bg-hb-brand text-hb-body font-extrabold text-white">
                    {{ initials }}
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-hb-body font-extrabold text-hb-ink">{{ authStore.user?.name }}</p>
                    <p class="truncate text-hb-sm text-hb-mut">{{ authStore.user?.email }}</p>
                  </div>
                </div>

                <!-- Logout -->
                <div class="p-1.5">
                  <button type="button"
                    class="group flex w-full items-center gap-2.5 rounded-hb-ctl px-2.5 py-2 text-left text-hb-body font-semibold text-hb-ink transition-colors hover:bg-hb-red/8 hover:text-hb-red"
                    role="menuitem" @click="logout">
                    <i class="mdi mdi-logout text-base text-hb-mut transition-colors group-hover:text-hb-red" />
                    Logout
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </header>

    <!-- .vertical-menu (sidebar) -->
    <aside data-sidebar
      class="fixed bottom-0 top-topbar z-[1002] w-sidebar overflow-y-auto border-r border-hb-line bg-white transition-transform lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
      <nav class="px-2.5 py-3">
        <ul class="flex flex-col gap-0.5">
          <!-- Dashboard (leaf) -->
          <li>
            <router-link to="/" data-nav-link class="nav-row" exact-active-class="is-on"
              @click="sidebarOpen = false">
              <i class="nav-icon mdi mdi-view-dashboard-outline" />
              <span data-nav-label>Dashboard</span>
            </router-link>
          </li>

          <!-- Sections with children -->
          <li v-for="section in navSections" :key="section.label" data-nav-item>
            <button type="button" data-nav-link class="nav-row"
              :class="[sectionActive(section) && 'is-on', open[section.label] && 'is-open']"
              @click="toggleSection(section.label)">
              <i class="nav-icon mdi" :class="section.icon" />
              <span data-nav-label>{{ section.label }}</span>
              <i data-nav-arrow class="nav-caret mdi mdi-chevron-down" />
            </button>
            <ul v-show="open[section.label]" data-nav-sub class="nav-sub">
              <li v-for="child in section.children" :key="child.label">
                <router-link :to="child.to" class="nav-sub-row"
                  active-class="is-on" @click="sidebarOpen = false">
                  <i class="nav-sub-icon mdi" :class="child.icon" aria-hidden="true" />
                  <span>{{ child.label }}</span>
                </router-link>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Mobile overlay -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-[1001] bg-hb-ink/40 lg:hidden" @click="sidebarOpen = false" />

    <!-- .main-content -->
    <main data-main class="min-h-screen min-w-0 pt-topbar lg:pl-sidebar">
      <div class="px-3 pb-5">
        <!-- .page-title-box: Concentric Ring Pattern (SVG circles — exact match) -->
        <div class="relative -mx-3 overflow-hidden bg-hb-brand px-3"
          :class="ownsHeading ? 'pt-3 pb-[54px]' : 'pt-6 pb-[65px]'">
          <svg class="pointer-events-none absolute inset-0 w-full h-full select-none"
            viewBox="0 0 600 200" preserveAspectRatio="xMaxYMax meet">
            <circle cx="600" cy="200" r="500" fill="rgba(255, 255, 255, 0.02)" />
            <circle cx="600" cy="200" r="410" fill="rgba(255, 255, 255, 0.02)" />
            <circle cx="600" cy="200" r="325" fill="rgba(255, 255, 255, 0.022)" />
            <circle cx="600" cy="200" r="245" fill="rgba(255, 255, 255, 0.024)" />
            <circle cx="600" cy="200" r="170" fill="rgba(255, 255, 255, 0.026)" />
            <circle cx="600" cy="200" r="100" fill="rgba(255, 255, 255, 0.03)" />
          </svg>

          <div class="relative z-10 px-3">
            <h4 v-if="!ownsHeading" class="mb-1 text-[20px] leading-[30px] font-semibold text-white drop-shadow-xs">
              {{ pageTitle }}
            </h4>
            <ol v-if="crumbs.length" class="flex flex-wrap text-hb-body">
              <li v-for="(crumb, i) in crumbs" :key="crumb"
                class="before:inline-block before:text-white/40 before:content-['/'] first:before:hidden"
                :class="[i ? 'pl-2 before:pr-2' : '', i === crumbs.length - 1 ? 'text-white/70 font-medium' : 'text-white/95']">
                {{ crumb }}
              </li>
            </ol>
          </div>
        </div>

        <!-- Page content -->
        <div class="relative z-10 -mt-10 min-h-[65vh] px-3">
          <ToastContainer />
          <router-view :key="$route.fullPath" />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import ToastContainer from '../components/ToastContainer.vue';
import BrandMark from '../components/BrandMark.vue';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const sidebarOpen = ref(false);
const collapsed = ref(false);
const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const open = ref({});

const pageTitle = computed(() => route.meta?.title ?? '');

/*
 * A screen "owns" its heading when it draws its own brand-filled panel over the title
 * band — the ip_new registers do that, so the band goes quiet and only carries
 * the crumb. The ERP screens open with white sheets instead, so the band keeps
 * the title and they opt in by hand.
 */
const ownsHeading = computed(() => route.meta?.ownsHeading === true);

// The sidebar section the current screen sits under, so the crumb reads
// "Procurement / Purchase Orders" rather than repeating the title alone.
function sectionOf(path) {
  return navSections.find((s) => s.children?.some((c) => path === c.to || path.startsWith(c.to + '/')))?.label ?? null;
}

const crumbs = computed(() => {
  if (!pageTitle.value) return [];
  if (route.meta?.crumb === null) return [];

  const parent = route.meta?.crumb ?? sectionOf(route.path);

  return parent ? [parent, pageTitle.value] : [pageTitle.value];
});

const initials = computed(() => {
  const name = authStore.user?.name ?? 'A';
  return name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0].toUpperCase()).join('');
});

function toggleNav() {
  if (window.innerWidth >= 992) {
    collapsed.value = !collapsed.value;
  } else {
    collapsed.value = false;
    sidebarOpen.value = !sidebarOpen.value;
  }
}

function closeUserMenu(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false;
  }
}

function logout() {
  authStore.logout();
  router.push('/login');
}

function toggleSection(label) {
  open.value = open.value[label] ? {} : { [label]: true };
}

function sectionActive(section) {
  return section.children?.some(c => route.path === c.to) || Object.keys(open.value).includes(section.label);
}

const navSections = [
  /*
   * Analytics and AI Hub sit directly under Dashboard because they are the
   * "read the business" group; everything below them is the work itself. None of
   * their screens are built — each opens ComingSoon, which describes what it will
   * be. See the routes for what each one is meant to do.
   */
  { label: 'Analytics', icon: 'mdi-chart-areaspline', children: [
    { label: 'Production Performance', icon: 'mdi-speedometer', to: '/analytics/production' },
    { label: 'Inventory & Stock Health', icon: 'mdi-chart-donut', to: '/analytics/inventory' },
    { label: 'Procurement & Spend', icon: 'mdi-chart-bar', to: '/analytics/procurement' },
    { label: 'Sales & Revenue', icon: 'mdi-trending-up', to: '/analytics/sales' },
    { label: 'Quality Trends', icon: 'mdi-alert-decagram-outline', to: '/analytics/quality' },
    { label: 'Maintenance & Downtime', icon: 'mdi-timer-off', to: '/analytics/maintenance' },
    { label: 'Report Builder', icon: 'mdi-file-document-outline', to: '/analytics/reports' },
  ]},
  { label: 'AI Hub', icon: 'mdi-brain', children: [
    { label: 'Chat', icon: 'mdi-message-text-outline', to: '/ai/chat' },
    { label: 'Ask Your Data', icon: 'mdi-comment-question-outline', to: '/ai/ask' },
    { label: 'Forecasting', icon: 'mdi-crystal-ball', to: '/ai/forecasting' },
    { label: 'Anomaly Detection', icon: 'mdi-magnify-scan', to: '/ai/anomalies' },
    { label: 'Document Extraction', icon: 'mdi-file-document-outline', to: '/ai/documents' },
    { label: 'Insights', icon: 'mdi-lightbulb-outline', to: '/ai/insights' },
    { label: 'AI Settings', icon: 'mdi-tune', to: '/ai/settings' },
  ]},
  { label: 'Organization', icon: 'mdi-home-city-outline', children: [
    { label: 'Companies', icon: 'mdi-domain', to: '/companies' },
    { label: 'Plants & Departments', icon: 'mdi-factory', to: '/plants' },
    { label: 'Warehouses', icon: 'mdi-warehouse', to: '/warehouses' },
    { label: 'Users & Roles', icon: 'mdi-account-key', to: '/users' },
  ]},
  { label: 'Engineering', icon: 'mdi-cogs', children: [
    { label: 'Items (Master Data)', icon: 'mdi-package-variant-closed', to: '/items' },
    { label: 'UOM & Conversions', icon: 'mdi-ruler', to: '/uoms' },
    { label: 'BOM', icon: 'mdi-sitemap', to: '/boms' },
    { label: 'Routings', icon: 'mdi-sign-direction', to: '/routings' },
    { label: 'Work Centers', icon: 'mdi-robot-industrial', to: '/work-centers' },
  ]},
  { label: 'Procurement', icon: 'mdi-truck-outline', children: [
    { label: 'Suppliers', icon: 'mdi-truck-fast', to: '/suppliers' },
    { label: 'Purchase Requisitions', icon: 'mdi-clipboard-text-outline', to: '/purchase-requisitions' },
    { label: 'Purchase Orders', icon: 'mdi-cart-arrow-down', to: '/purchase-orders' },
    { label: 'Goods Receipts (GRN)', icon: 'mdi-package-down', to: '/goods-receipts' },
    { label: 'RFQ', icon: 'mdi-file-send-outline', to: '/rfq' },
  ]},
  { label: 'Inventory', icon: 'mdi-package-variant', children: [
    { label: 'Stock Balances', icon: 'mdi-archive-outline', to: '/stock' },
    { label: 'Stock Movements', icon: 'mdi-swap-horizontal', to: '/stock-movements' },
    { label: 'Stock Counts', icon: 'mdi-clipboard-check-outline', to: '/stock-counts' },
  ]},
  { label: 'Production', icon: 'mdi-factory', children: [
    { label: 'Production Orders', icon: 'mdi-format-list-numbered', to: '/production-orders' },
    { label: 'Shop Floor', icon: 'mdi-robot', to: '/shop-floor' },
  ]},
  { label: 'Planning', icon: 'mdi-calendar-clock', children: [
    { label: 'MRP & Planning', icon: 'mdi-chart-gantt', to: '/planning' },
  ]},
  { label: 'Sales', icon: 'mdi-account-group-outline', children: [
    { label: 'Customers', icon: 'mdi-account-tie-outline', to: '/customers' },
    { label: 'Quotations', icon: 'mdi-format-quote-close', to: '/quotations' },
    { label: 'Sales Orders', icon: 'mdi-cart-arrow-up', to: '/sales-orders' },
    { label: 'Shipments', icon: 'mdi-truck-delivery-outline', to: '/shipments' },
    { label: 'Returns (RMA)', icon: 'mdi-undo-variant', to: '/rma' },
  ]},
  { label: 'Quality', icon: 'mdi-shield-check-outline', children: [
    { label: 'Quality Plans', icon: 'mdi-file-check-outline', to: '/quality-plans' },
    { label: 'Inspections', icon: 'mdi-microscope', to: '/inspections' },
    { label: 'NCR', icon: 'mdi-alert-octagon-outline', to: '/ncrs' },
    { label: 'CAPA', icon: 'mdi-lightbulb-on-outline', to: '/capa' },
    { label: 'Calibration', icon: 'mdi-adjust', to: '/calibration' },
    { label: 'Complaints', icon: 'mdi-message-alert-outline', to: '/complaints' },
  ]},
  { label: 'Maintenance', icon: 'mdi-wrench-outline', children: [
    { label: 'Assets', icon: 'mdi-tools', to: '/assets' },
    { label: 'Work Orders', icon: 'mdi-wrench-outline', to: '/maintenance-orders' },
    { label: 'Downtime', icon: 'mdi-timer-off', to: '/downtime' },
  ]},
  { label: 'Workforce', icon: 'mdi-account-multiple-outline', children: [
    { label: 'Employees', icon: 'mdi-account-outline', to: '/employees' },
    { label: 'Attendance', icon: 'mdi-calendar-check', to: '/attendance' },
    { label: 'Shifts', icon: 'mdi-clock-outline', to: '/shifts' },
    { label: 'Skills', icon: 'mdi-school-outline', to: '/skills' },
    { label: 'Time Bookings', icon: 'mdi-clock-in', to: '/time-bookings' },
    { label: 'Payroll', icon: 'mdi-cash-multiple', to: '/payroll' },
  ]},
  { label: 'Finance', icon: 'mdi-book-open-outline', children: [
    { label: 'Chart of Accounts', icon: 'mdi-view-list', to: '/chart-of-accounts' },
    { label: 'Journals', icon: 'mdi-book-open-outline', to: '/journals' },
    { label: 'Supplier Invoices', icon: 'mdi-receipt', to: '/supplier-invoices' },
    { label: 'Customer Invoices', icon: 'mdi-currency-usd', to: '/customer-invoices' },
    { label: 'AP Aging', icon: 'mdi-clock-alert-outline', to: '/ap-aging' },
    { label: 'AR Aging', icon: 'mdi-clock-check-outline', to: '/ar-aging' },
    { label: 'Bank Accounts', icon: 'mdi-bank', to: '/bank-accounts' },
    { label: 'Fixed Assets', icon: 'mdi-calculator', to: '/fixed-assets' },
    { label: 'Budgets', icon: 'mdi-chart-pie', to: '/budgets' },
    { label: 'Trial Balance', icon: 'mdi-scale-balance', to: '/finance/trial-balance' },
    { label: 'Balance Sheet', icon: 'mdi-chart-bar', to: '/finance/balance-sheet' },
    { label: 'Profit & Loss', icon: 'mdi-chart-line', to: '/finance/profit-loss' },
    { label: 'Period Close', icon: 'mdi-lock-outline', to: '/finance/period-close' },
  ]},
  { label: 'System', icon: 'mdi-menu', children: [
    { label: 'Audit Log', icon: 'mdi-history', to: '/audit-log' },
    { label: 'Settings', icon: 'mdi-settings-outline', to: '/settings' },
  ]},
];

// Watch route to auto-open current section
watch(() => route.path, () => {
  const current = navSections.find(s => s.children?.some(c => route.path.startsWith(c.to)));
  if (current) {
    open.value = { [current.label]: true };
  }
}, { immediate: true });

onMounted(() => {
  if (!route.meta.guest) {
    authStore.fetchUser();
  }
  document.addEventListener('mousedown', closeUserMenu);
});

onUnmounted(() => {
  document.removeEventListener('mousedown', closeUserMenu);
});
</script>
