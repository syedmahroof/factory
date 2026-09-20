<script setup>
/**
 * The factory desk for one date range.
 *
 * Laid out after ip_new's Portfolio Activity screen — period buttons on the title
 * band, a strip of figures, a bar chart beside a status mix, three mixes under it,
 * the tile grid and the table — with the manufacturing questions in place of the
 * syndication ones: what did the plant make, what did it commit to buy, what did
 * it sell, what moved through the warehouse, and what is short.
 *
 * All aggregation is BuildFactoryActivityAction behind /dashboard; this only draws
 * what comes back. The arithmetic here is presentational — deltas, bar geometry,
 * shares of a total — and nothing that decides a figure.
 */
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useApi } from '@/composables/useApi';
import HbHero from '@/components/HbHero.vue';
import '@/../css/factory-activity.css';

const { get } = useApi();

const data = ref(null);
const loading = ref(true);
const failed = ref('');

const filters = reactive({ period: '30d', granularity: 'auto', from_date: '', to_date: '' });

/* ── formatting ─────────────────────────────────────────────────────────── */

function fmt(value, decimals = 0) {
    return (Number(value) || 0).toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

const currency = (value, decimals = 2) => `$${fmt(value, decimals)}`;

/** A figure short enough to stand on a bar or in an axis gutter. */
function short(value, prefix = '') {
    const n = Number(value) || 0;

    if (Math.abs(n) >= 1000000) return `${prefix}${fmt(n / 1000000, 2)}M`;
    if (Math.abs(n) >= 1000) return `${prefix}${fmt(n / 1000, Math.abs(n) >= 100000 ? 0 : 1)}K`;

    return `${prefix}${fmt(n)}`;
}

const shortMoney = (v) => short(v, '$');

/**
 * ▲/▼ against the prior period. Null when the prior period had nothing to compare
 * against — a delta off zero is noise, not signal.
 */
function delta(current, previous) {
    const prev = Number(previous) || 0;

    if (prev === 0) return null;

    return (((Number(current) || 0) - prev) / Math.abs(prev)) * 100;
}

const signed = (v, decimals = 1) => `${v >= 0 ? '+' : ''}${fmt(v, decimals)}`;
const plural = (word, n) => (Number(n) === 1 ? word : `${word}s`);
const titleise = (s) => String(s ?? '').replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());

function longDate(value) {
    if (!value) return '';

    const d = new Date(`${value}T00:00:00`);
    const month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][d.getMonth()];

    return `${d.getDate()} ${month} ${d.getFullYear()}`;
}

/* ── the figures the screen opens with ──────────────────────────────────── */

const prod = computed(() => data.value?.production ?? {});
const prodPrev = computed(() => data.value?.production_prev ?? {});
const buying = computed(() => data.value?.purchasing ?? {});
const buyingPrev = computed(() => data.value?.purchasing_prev ?? {});
const selling = computed(() => data.value?.sales ?? {});
const sellingPrev = computed(() => data.value?.sales_prev ?? {});
const quality = computed(() => data.value?.quality ?? {});
const qualityPrev = computed(() => data.value?.quality_prev ?? {});
const stock = computed(() => data.value?.stock ?? {});
const work = computed(() => data.value?.open_work ?? {});

const PRESETS = computed(() => Object.entries(data.value?.presets ?? {}));
const GRANULARITIES = computed(() => Object.entries(data.value?.granularities ?? {}));

/**
 * The delta written out for a sub line — "+17.4% vs prior", or nothing at all
 * when the prior period had zero to compare against.
 */
function vsPrior(current, previous, tail = 'vs prior') {
    const d = delta(current, previous);

    return d === null ? '' : `${signed(d)}% ${tail}`;
}

/**
 * Six readings of the same period, handed to the hero: Output leads as the gauge
 * on the right and the other five fill the band. The deltas ride in the sub lines
 * rather than as pills of their own, which is all the band has room for and all
 * they ever said.
 */
const heroStats = computed(() => [
    {
        label: 'Output',
        icon: 'mdi-factory',
        lead: true,
        value: `${fmt(prod.value.quantity)} units`,
        sub: `${fmt(prod.value.orders)} ${plural('order', prod.value.orders)} completed`,
        foot: vsPrior(prod.value.quantity, prodPrev.value.quantity, 'vs prior period'),
    },
    {
        label: 'Purchasing',
        icon: 'mdi-cart-outline',
        value: currency(buying.value.amount),
        money: true,
        dot: 'b',
        sub: [`${fmt(buying.value.orders)} ${plural('order', buying.value.orders)} raised`, vsPrior(buying.value.amount, buyingPrev.value.amount)].filter(Boolean).join(' · '),
    },
    {
        label: 'Sales booked',
        icon: 'mdi-tag-outline',
        value: currency(selling.value.amount),
        money: true,
        dot: 'g',
        sub: [`${fmt(selling.value.orders)} ${plural('order', selling.value.orders)} · ${fmt(selling.value.shipped)} shipped`, vsPrior(selling.value.amount, sellingPrev.value.amount)].filter(Boolean).join(' · '),
    },
    {
        label: 'Stock on hand',
        icon: 'mdi-warehouse',
        value: currency(stock.value.value),
        money: true,
        // No dot: this one is the position now, not a reading of the period.
        sub: `${fmt(stock.value.items)} ${plural('item', stock.value.items)} · ${fmt(stock.value.quantity)} units · right now`,
    },
    {
        label: 'Yield',
        icon: 'mdi-check-circle-outline',
        value: prod.value.yield == null ? '—' : `${fmt(prod.value.yield, 1)}%`,
        dot: prod.value.scrap ? 'o' : 'g',
        sub: prod.value.scrap ? `${fmt(prod.value.scrap)} units scrapped` : 'no scrap booked',
    },
    {
        label: 'NCRs raised',
        icon: 'mdi-alert-octagon-outline',
        value: fmt(quality.value.raised),
        dot: work.value.ncrs ? 'r' : 'g',
        sub: `${fmt(quality.value.closed)} closed · ${fmt(work.value.ncrs)} still open`,
    },
]);

/* ── the output chart ───────────────────────────────────────────────────── */

/** Every SVG coordinate is rounded to one decimal, as ip_new's chart is. */
const round = (v) => Math.round(v * 10) / 10;

/*
 * Plot geometry. The right gutter is the scale's, so a gridline's figure never
 * lands on top of a bar; bars are drawn between 0 and PLOT_W.
 */
const PLOT_W = 512;
const BASE_Y = 140;

const chart = computed(() => {
    const rows = data.value?.series?.rows ?? [];
    const n = rows.length;
    const peak = Math.max(1, ...rows.map((r) => Number(r.quantity) || 0));
    const slot = n > 0 ? PLOT_W / n : PLOT_W;
    const bw = Math.min(20, Math.max(3, slot * 0.6));
    const peakIndex = rows.findIndex((r) => (Number(r.quantity) || 0) >= peak);
    const anything = rows.some((r) => (Number(r.quantity) || 0) > 0);

    /*
     * Dates are written flat, so only as many are drawn as fit: one every ~46px.
     * Values are not written on every bar at all — twenty numbers standing on end
     * is a wall, not a reading. The peak carries its figure and the rest answer
     * on hover, which is what the axis is for.
     */
    const labelStep = Math.max(1, Math.ceil(46 / slot));
    const dense = n > 12;

    const bars = rows.map((r, i) => {
        const quantity = Number(r.quantity) || 0;
        const h = Math.max(quantity > 0 ? 2 : 0, (quantity / peak) * 124);
        const isPeak = anything && i === peakIndex;

        return {
            label: r.label,
            quantity,
            orders: r.orders,
            h: round(h),
            x: round(i * slot + (slot - bw) / 2),
            y: round(BASE_Y - h),
            cx: round(i * slot + slot / 2),
            isPeak,
            // The last date is always worth reading; the thinning starts from it.
            labelled: (n - 1 - i) % labelStep === 0,
            valued: quantity > 0 && (!dense || isPeak),
        };
    });

    return { rows, n, peak, anything, bw: round(bw), bars, bucket: data.value?.series?.bucket ?? 'day' };
});

/* ── mixes ──────────────────────────────────────────────────────────────── */

/*
 * Colour follows how far through its lifecycle a status is, not its place in the
 * result set — so "approved" is the same swatch whether or not anything was
 * cancelled that month, the legend does not reshuffle between periods, and a card
 * showing a single status still says which end of the run that status sits at.
 * The ramp itself is in factory-activity.css.
 */
const STATUS_COLOR = {
    // Nothing has happened yet.
    draft: 'var(--dash-grey)',
    planned: 'var(--dash-grey)',
    requested: 'var(--dash-grey)',
    // Agreed, not yet worked.
    submitted: 'var(--dash-blue)',
    confirmed: 'var(--dash-blue)',
    approved: 'var(--dash-brand)',
    // Being worked.
    released: 'var(--dash-brand)',
    in_progress: 'var(--dash-brand-deep)',
    allocated: 'var(--dash-brand-deep)',
    // Part-way through.
    partially_received: 'var(--dash-ochre)',
    partially_picked: 'var(--dash-ochre)',
    picked: 'var(--dash-olive)',
    packed: 'var(--dash-olive)',
    technically_complete: 'var(--dash-olive)',
    costed: 'var(--dash-teal)',
    // Finished.
    received: 'var(--dash-green)',
    shipped: 'var(--dash-green)',
    delivered: 'var(--dash-green-deep)',
    invoiced: 'var(--dash-green-deep)',
    closed: 'var(--dash-green-deep)',
    // Off to one side.
    cancelled: 'var(--dash-plum)',
    rejected: 'var(--dash-plum)',
};

const statusColor = (status) => STATUS_COLOR[status] ?? 'var(--dash-grey)';

/** Lifecycle order, so a mix always reads left-to-right through the process. */
const ORDER = {
    production: ['planned', 'approved', 'released', 'in_progress', 'technically_complete', 'costed', 'closed', 'cancelled'],
    purchase: ['draft', 'submitted', 'approved', 'partially_received', 'received', 'closed', 'cancelled'],
    sales: ['draft', 'confirmed', 'allocated', 'partially_picked', 'picked', 'packed', 'shipped', 'delivered', 'invoiced', 'closed', 'cancelled'],
};

function mix(rows, lifecycle, valueKey, countKey, suffix) {
    const order = ORDER[lifecycle];
    const sorted = [...(rows ?? [])].sort((a, b) => order.indexOf(a.status) - order.indexOf(b.status));
    const max = Math.max(1, ...sorted.map((r) => Number(r[valueKey]) || 0));

    return sorted.map((r) => ({
        status: r.status,
        label: titleise(r.status),
        value: Number(r[valueKey]) || 0,
        count: Number(r[countKey]) || 0,
        suffix,
        color: statusColor(r.status),
        width: round(((Number(r[valueKey]) || 0) / max) * 100),
    }));
}

const productionMix = computed(() => mix(data.value?.production_by_status, 'production', 'planned', 'orders', 'orders'));
const purchaseMix = computed(() => mix(data.value?.purchasing_by_status, 'purchase', 'amount', 'orders', 'orders'));
const salesMix = computed(() => mix(data.value?.sales_by_status, 'sales', 'amount', 'orders', 'orders'));

const purchaseReceived = computed(() =>
    purchaseMix.value.filter((r) => ['partially_received', 'received', 'closed'].includes(r.status)).reduce((s, r) => s + r.value, 0),
);

const salesShipped = computed(() =>
    salesMix.value.filter((r) => ['shipped', 'delivered', 'invoiced', 'closed'].includes(r.status)).reduce((s, r) => s + r.value, 0),
);

/* `document_type` is a free string, so warehouse traffic gets a plain ramp. */
const MOVEMENT_RAMP = ['var(--dash-brand)', 'var(--dash-blue)', 'var(--dash-ochre)', 'var(--dash-green)', 'var(--dash-plum)', 'var(--dash-olive)', 'var(--dash-teal)'];

const movements = computed(() => {
    const rows = data.value?.movements?.rows ?? [];
    const max = Math.max(1, ...rows.map((r) => Math.abs(Number(r.value) || 0)));

    return rows.map((r, i) => ({
        label: titleise(r.document_type),
        count: r.movements,
        value: Number(r.value) || 0,
        color: MOVEMENT_RAMP[i % MOVEMENT_RAMP.length],
        width: round((Math.abs(Number(r.value) || 0) / max) * 100),
    }));
});

/* ── the factory right now ──────────────────────────────────────────────── */

const TILES = [
    { key: 'items', label: 'Items', icon: 'mdi-package-variant-closed', tone: 'br', to: '/items' },
    { key: 'work_centers', label: 'Work centres', icon: 'mdi-robot-industrial', tone: 'br', to: '/work-centers' },
    { key: 'suppliers', label: 'Suppliers', icon: 'mdi-truck-outline', tone: 'nt', to: '/suppliers' },
    { key: 'customers', label: 'Customers', icon: 'mdi-account-tie-outline', tone: 'nt', to: '/customers' },
    { key: 'purchase_orders', label: 'Open purchase orders', icon: 'mdi-cart-arrow-down', tone: 'bl', to: '/purchase-orders' },
    { key: 'sales_orders', label: 'Open sales orders', icon: 'mdi-cart-arrow-up', tone: 'bl', to: '/sales-orders' },
    { key: 'production_orders', label: 'Open production orders', icon: 'mdi-cogs', tone: 'gn', to: '/production-orders' },
    { key: 'maintenance_orders', label: 'Open work orders', icon: 'mdi-wrench-outline', tone: 'am', to: '/maintenance-orders' },
];

const tiles = computed(() => TILES.map((t) => ({ ...t, count: work.value[t.key] ?? 0 })));

/*
 * The production book as one bar. Eight statuses is too many stripes to read, so
 * they are collapsed to the four states someone on the floor would name.
 */
const STATES = [
    { label: 'Not started', color: 'var(--dash-grey)', of: ['planned', 'approved'] },
    { label: 'On the floor', color: 'var(--dash-brand)', of: ['released', 'in_progress'] },
    { label: 'Finishing', color: 'var(--dash-olive)', of: ['technically_complete', 'costed'] },
    { label: 'Closed', color: 'var(--dash-green-deep)', of: ['closed'] },
    { label: 'Cancelled', color: 'var(--dash-plum)', of: ['cancelled'] },
];

const states = computed(() => {
    const rows = data.value?.production_by_status ?? [];
    const segs = STATES.map((s) => ({
        ...s,
        n: rows.filter((r) => s.of.includes(r.status)).reduce((sum, r) => sum + r.orders, 0),
    }));
    const total = segs.reduce((sum, s) => sum + s.n, 0);

    return { total, segs: segs.filter((s) => s.n).map((s) => ({ ...s, pct: (s.n / Math.max(1, total)) * 100 })) };
});

const lowStock = computed(() => data.value?.low_stock ?? []);
const topItems = computed(() => data.value?.top_items ?? []);

const clampPct = (v) => Math.min(100, Math.max(0, Number(v) || 0));

/* ── loading ────────────────────────────────────────────────────────────── */

const isCustom = computed(() => filters.period === 'custom');

async function load() {
    loading.value = true;
    failed.value = '';

    try {
        data.value = await get('/dashboard', { ...filters });
    } catch (e) {
        failed.value = e?.response?.data?.message ?? 'Unable to build the dashboard.';
    } finally {
        loading.value = false;
    }
}

watch(
    () => [filters.period, filters.granularity, filters.from_date, filters.to_date],
    () => {
        // A custom range needs both ends before it means anything.
        if (isCustom.value && !(filters.from_date && filters.to_date)) return;

        load();
    },
);

onMounted(load);
</script>

<template>
  <div class="dash">
    <div v-if="loading && !data" class="card p-6"><loading-spinner /></div>

    <div v-else-if="failed" class="card dash-card">
      <div class="dash-empty">{{ failed }}</div>
    </div>

    <template v-else-if="data">
      <!-- ═══ The panel the screen opens with: title, range, period, figures ═══ -->
      <HbHero icon="mdi-view-dashboard-outline" title="Dashboard" :stats="heroStats">
        <template #meta>
          <div class="dash-range">
            <span class="hb-chip">
              <i class="mdi mdi-calendar-range" />{{ longDate(data.from) }} – {{ longDate(data.to) }}
            </span>
            <span class="cmp">compared with {{ longDate(data.prev_from) }} – {{ longDate(data.prev_to) }}</span>
          </div>
        </template>

        <template #action>
          <div class="dash-presets" role="tablist" aria-label="Report period">
            <button
              v-for="[key, label] in PRESETS"
              :key="key"
              type="button"
              :class="filters.period === key && 'on'"
              @click="filters.period = key">
              {{ label }}
            </button>
            <button type="button" :class="filters.period === 'custom' && 'on'" @click="filters.period = 'custom'">
              Custom
            </button>
          </div>

          <div v-if="isCustom" class="dash-dates">
            <input v-model="filters.from_date" type="date" aria-label="From date" />
            <span class="hint">to</span>
            <input v-model="filters.to_date" type="date" aria-label="To date" />
          </div>
        </template>
      </HbHero>

      <div class="dash-dim" :class="loading && 'is-loading'">
      <!-- ═══ Row 1: output chart + production mix ═══ -->
      <div class="dash-r1">
        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">Output by {{ chart.bucket }}</p>
              <p class="s2">
                {{ short(prod.quantity) }} units over {{ chart.n }} {{ plural(chart.bucket, chart.n) }}
              </p>
            </div>
            <div class="dash-chx">
              <div class="dash-mini" role="tablist" aria-label="Chart granularity">
                <button
                  v-for="[g, gLabel] in GRANULARITIES"
                  :key="g"
                  type="button"
                  :class="chart.bucket === g && 'on'"
                  @click="filters.granularity = g">
                  {{ gLabel }}
                </button>
              </div>
              <router-link to="/production-orders">Production orders</router-link>
            </div>
          </div>
          <div class="dash-cb">
            <div v-if="!chart.anything" class="dash-empty">
              Nothing was booked as completed in this period.
            </div>
            <svg
              v-else
              viewBox="0 0 560 172"
              role="img"
              :aria-label="`Units completed per ${chart.bucket}, peaking at ${fmt(chart.peak)}`">
              <g stroke="var(--dash-ln2)" stroke-width="1">
                <line x1="0" y1="16" x2="512" y2="16" />
                <line x1="0" y1="78" x2="512" y2="78" />
              </g>
              <!-- The scale sits in its own gutter, clear of the bars. -->
              <g class="dash-axis" font-size="7.5" fill="var(--color-hb-mut)">
                <text x="560" y="16" text-anchor="end" dominant-baseline="middle">{{ short(chart.peak) }}</text>
                <text x="560" y="78" text-anchor="end" dominant-baseline="middle">{{ short(chart.peak / 2) }}</text>
                <text x="560" y="140" text-anchor="end" dominant-baseline="middle">0</text>
              </g>
              <line x1="0" y1="140" x2="512" y2="140" stroke="var(--color-hb-line)" />

              <g class="dash-bars">
                <rect
                  v-for="(b, i) in chart.bars"
                  :key="i"
                  :x="b.x"
                  :y="b.y"
                  :width="chart.bw"
                  :height="b.h"
                  rx="3"
                  :fill="b.isPeak ? 'var(--color-hb-brand)' : '#ea9a9a'">
                  <title>{{ b.label }} — {{ fmt(b.quantity) }} units, {{ fmt(b.orders) }} {{ plural('order', b.orders) }}</title>
                </rect>
              </g>

              <!-- The peak names itself; a dozen bars or fewer all do. -->
              <g class="dash-axis">
                <text
                  v-for="(b, i) in chart.bars.filter((x) => x.valued)"
                  :key="`v${i}`"
                  :x="b.cx"
                  :y="round(Math.max(9, b.y - 6))"
                  text-anchor="middle"
                  font-size="8.5"
                  :font-weight="b.isPeak ? 700 : 600"
                  :fill="b.isPeak ? 'var(--color-hb-ink)' : 'var(--color-hb-mut)'">
                  {{ short(b.quantity) }}
                </text>
              </g>

              <g class="dash-axis" font-size="7.5" font-weight="500" fill="var(--color-hb-mut)">
                <template v-for="(b, i) in chart.bars" :key="`l${i}`">
                  <text v-if="b.labelled" :x="b.cx" y="158" text-anchor="middle">{{ b.label }}</text>
                </template>
              </g>
            </svg>
          </div>
        </div>

        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">Production orders by status</p>
              <p class="s2">Raised in this period · by planned quantity</p>
            </div>
          </div>
          <div class="dash-cb">
            <div v-if="!productionMix.length" class="dash-empty">No production orders were raised in this period.</div>
            <template v-else>
              <div class="dash-mix">
                <div v-for="r in productionMix" :key="r.status" class="r">
                  <div class="t">
                    <span class="n">
                      <i :style="{ background: r.color }" />{{ r.label }}
                      <small>· {{ fmt(r.count) }} {{ plural('order', r.count) }}</small>
                    </span>
                    <span class="v">{{ fmt(r.value) }}</span>
                  </div>
                  <div class="tr"><i :style="{ width: `${r.width}%`, background: r.color }" /></div>
                </div>
              </div>
              <div class="dash-split">
                <span>Completed against plan</span>
                <b>{{ fmt(prod.quantity) }} units</b>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- ═══ Row 2: purchasing / sales / warehouse ═══ -->
      <div class="dash-r2">
        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">Purchasing</p>
              <p class="s2">{{ shortMoney(buying.amount) }} committed</p>
            </div>
            <router-link to="/purchase-orders">Purchase orders</router-link>
          </div>
          <div class="dash-cb">
            <div v-if="!purchaseMix.length" class="dash-empty">No purchase orders were raised in this period.</div>
            <template v-else>
              <div class="dash-mix">
                <div v-for="r in purchaseMix" :key="r.status" class="r">
                  <div class="t">
                    <span class="n">
                      <i :style="{ background: r.color }" />{{ r.label }}
                      <small>· {{ fmt(r.count) }}</small>
                    </span>
                    <span class="v">{{ currency(r.value) }}</span>
                  </div>
                  <div class="tr"><i :style="{ width: `${r.width}%`, background: r.color }" /></div>
                </div>
              </div>
              <div class="dash-split">
                <span>Received vs still on order</span>
                <b>{{ shortMoney(purchaseReceived) }} / {{ shortMoney(buying.amount - purchaseReceived) }}</b>
              </div>
            </template>
          </div>
        </div>

        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">Sales</p>
              <p class="s2">{{ shortMoney(selling.amount) }} booked</p>
            </div>
            <router-link to="/sales-orders">Sales orders</router-link>
          </div>
          <div class="dash-cb">
            <div v-if="!salesMix.length" class="dash-empty">No sales orders were booked in this period.</div>
            <template v-else>
              <div class="dash-mix">
                <div v-for="r in salesMix" :key="r.status" class="r">
                  <div class="t">
                    <span class="n">
                      <i :style="{ background: r.color }" />{{ r.label }}
                      <small>· {{ fmt(r.count) }}</small>
                    </span>
                    <span class="v">{{ currency(r.value) }}</span>
                  </div>
                  <div class="tr"><i :style="{ width: `${r.width}%`, background: r.color }" /></div>
                </div>
              </div>
              <div class="dash-split">
                <span>Shipped vs still open</span>
                <b>{{ shortMoney(salesShipped) }} / {{ shortMoney(selling.amount - salesShipped) }}</b>
              </div>
            </template>
          </div>
        </div>

        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">Warehouse traffic</p>
              <p class="s2">{{ fmt(data.movements.total) }} {{ plural('movement', data.movements.total) }} · {{ shortMoney(data.movements.value) }}</p>
            </div>
            <router-link to="/stock-movements">Stock movements</router-link>
          </div>
          <div class="dash-cb">
            <div v-if="!movements.length" class="dash-empty">Nothing moved through the warehouse in this period.</div>
            <template v-else>
              <div class="dash-mix">
                <div v-for="r in movements" :key="r.label" class="r">
                  <div class="t">
                    <span class="n">
                      <i :style="{ background: r.color }" />{{ r.label }}
                      <small>· {{ fmt(r.count) }}</small>
                    </span>
                    <span class="v">{{ currency(r.value) }}</span>
                  </div>
                  <div class="tr"><i :style="{ width: `${r.width}%`, background: r.color }" /></div>
                </div>
              </div>
              <div class="dash-split">
                <span>Stock held now</span>
                <b>{{ shortMoney(stock.value) }}</b>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- ═══ Row 3: the book right now + what is short ═══ -->
      <div class="dash-r3">
        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">The factory right now</p>
              <p class="s2">Not period-scoped · each tile opens its own screen</p>
            </div>
          </div>

          <div class="dash-tiles">
            <router-link v-for="tile in tiles" :key="tile.key" class="cell" :to="tile.to">
              <span class="ic" :class="tile.tone" aria-hidden="true"><i class="mdi" :class="tile.icon" /></span>
              <span style="min-width: 0">
                <span class="num">{{ fmt(tile.count) }}</span>
                <span class="lb">{{ tile.label }}</span>
              </span>
            </router-link>
          </div>

          <template v-if="states.total">
            <div class="dash-statebar" role="img" :aria-label="`${fmt(states.total)} production orders by state`">
              <i
                v-for="seg in states.segs"
                :key="seg.label"
                :style="{ width: `${round(seg.pct)}%`, background: seg.color }"
                v-tooltip.hb="`${seg.label} — ${fmt(seg.n)} of ${fmt(states.total)}`" />
            </div>
            <div class="dash-legend">
              <span v-for="seg in states.segs" :key="seg.label" class="item">
                <i class="sw" :style="{ background: seg.color }" />
                {{ seg.label }}
                <b>{{ fmt(seg.n) }}</b>
                <span class="hint">{{ Math.round(seg.pct) }}%</span>
              </span>
            </div>
          </template>
        </div>

        <div class="card dash-card">
          <div class="dash-ch">
            <div>
              <p class="t">At or below reorder level</p>
              <p class="s2">On-hand across every warehouse, against the item's own level</p>
            </div>
            <router-link to="/stock">Stock balances</router-link>
          </div>

          <router-link v-for="row in lowStock" :key="row.id" class="dash-row" :to="`/items/${row.id}/edit`">
            <span class="who">
              {{ row.code }}
              <small>{{ row.name }}</small>
            </span>
            <span class="hb-pill" :class="row.on_hand <= 0 ? 'off' : 'idle'">
              {{ row.on_hand <= 0 ? 'Out of stock' : 'Below level' }}
            </span>
            <span class="fig">
              {{ fmt(row.on_hand) }} <small>/ {{ fmt(row.reorder_level) }}</small>
            </span>
          </router-link>

          <div v-if="!lowStock.length" class="dash-empty">
            Nothing is at its reorder level — or no item has one set.
          </div>
        </div>
      </div>

      <!-- ═══ What the plant actually ran ═══ -->
      <div class="card dash-card" style="margin-top: 12px">
        <div class="dash-ch">
          <div>
            <p class="t">Most-produced items this period</p>
            <p class="s2">By quantity completed · attainment is actual against plan</p>
          </div>
          <router-link to="/items">All items</router-link>
        </div>

        <div v-if="topItems.length" style="overflow-x: auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Type</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Planned</th>
                <th class="text-right">Completed</th>
                <th class="text-right">Scrap</th>
                <th>Attainment</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in topItems" :key="row.id">
                <td>
                  <router-link :to="`/items/${row.id}/edit`">{{ row.code }}</router-link>
                  <span class="text-hb-mut"> · {{ row.name }}</span>
                </td>
                <td>{{ titleise(row.type) }}</td>
                <td class="text-right">{{ fmt(row.orders) }}</td>
                <td class="text-right">{{ fmt(row.planned) }}</td>
                <td class="text-right">{{ fmt(row.actual) }}</td>
                <td class="text-right" :class="row.scrap > 0 && 'bad'">{{ fmt(row.scrap) }}</td>
                <td>
                  <span v-if="row.attainment !== null" class="dash-prog">
                    <span class="tr"><i :style="{ width: `${clampPct(row.attainment)}%` }" /></span>
                    <small>{{ fmt(row.attainment, 1) }}%</small>
                  </span>
                  <span v-else class="text-hb-mut">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="dash-empty">No production orders were completed in this period.</div>
      </div>

      <div class="dash-foot">
        <span class="dot" />
        <span>
          Output is dated by when an order actually finished · purchases and sales by order date · NCRs by when they were
          reported · stock, open orders and reorder shortfalls are the position right now, not the period's
        </span>
      </div>
      </div>
    </template>
  </div>
</template>
