<script setup>
/**
 * The register every list screen is built on.
 *
 * Beyond the grid and the pager it owns the toolbar the screens used to each
 * build by hand: search with a reset, a filter drawer, a column-visibility
 * picker, and an Excel export of the whole filtered set — not just the page on
 * screen. Owning them here is the point: there are ~50 registers and a toolbar
 * per screen would be ~50 slightly different toolbars.
 *
 * Rows carry a serial number taken from the paginator (`meta.from`), so row 26 on
 * page two reads 26 rather than starting again at 1.
 *
 * The `#header` slot still works, so a screen that dresses its own controls is
 * not broken by this; pass `hide-toolbar` to suppress the built-in one entirely.
 */
import { computed, ref, watch } from 'vue';
import client from '../api/client';
import { useToast } from '../composables/useToast';

const props = defineProps({
    columns: { type: Array, required: true },
    items: { type: Array, default: () => [] },
    meta: Object,
    loading: Boolean,
    emptyText: { type: String, default: 'No records found.' },
    hideActions: Boolean,

    /** The row-number column. Off for a grid that is not a register. */
    index: { type: Boolean, default: true },

    /** Search text, two-way. Without it the search box is not drawn. */
    search: { type: String, default: null },
    searchPlaceholder: { type: String, default: 'Search…' },

    /**
     * Filter definitions: [{ key, label, type: 'text'|'select'|'date'|'number',
     * options: [{ value, label }] }]. Values are two-way through `filterValues`.
     */
    filters: { type: Array, default: () => [] },
    filterValues: { type: Object, default: () => ({}) },

    /**
     * Where the Excel export comes from. `exportUrl` is an API path that returns
     * a spreadsheet; `exportName` names the downloaded file. Without a url the
     * button is not drawn.
     */
    exportUrl: { type: String, default: '' },
    exportName: { type: String, default: 'export' },

    /**
     * Identity for remembered column choices. Defaults to the route path, which
     * is unique per register; set it if two registers share a path.
     */
    tableKey: { type: String, default: '' },

    /**
     * Sort state, two-way: `{ key, dir }`. Every column sorts unless its
     * definition says `sortable: false` — a column rendered from a slot with no
     * column of its own behind it cannot.
     */
    sort: { type: Object, default: () => ({ key: '', dir: 'asc' }) },

    hideToolbar: Boolean,
});

const emit = defineEmits(['page', 'update:search', 'update:filterValues', 'update:sort', 'apply', 'reset']);

const toast = useToast();

/* ── columns the reader has chosen to see ───────────────────────────────── */

const storageKey = computed(() => `dt.cols.${props.tableKey || window.location.pathname}`);

/**
 * Hidden rather than visible is what gets stored: a column added to a register
 * later should appear for everyone, not stay hidden because it was missing from
 * a list written months ago.
 */
const hidden = ref(read());

function read() {
    try {
        return new Set(JSON.parse(localStorage.getItem(storageKey.value) || '[]'));
    } catch {
        return new Set();
    }
}

function persist() {
    try {
        localStorage.setItem(storageKey.value, JSON.stringify([...hidden.value]));
    } catch {
        // A browser with site data blocked still gets a working table.
    }
}

function toggleColumn(key) {
    // The last visible column cannot be hidden — an empty grid is not a view.
    if (!hidden.value.has(key) && visibleColumns.value.length <= 1) return;

    hidden.value.has(key) ? hidden.value.delete(key) : hidden.value.add(key);
    hidden.value = new Set(hidden.value);
    persist();
}

function showAllColumns() {
    hidden.value = new Set();
    persist();
}

const visibleColumns = computed(() => props.columns.filter((c) => !hidden.value.has(c.key)));
const hiddenCount = computed(() => props.columns.length - visibleColumns.value.length);

/* ── toolbar state ──────────────────────────────────────────────────────── */

const filtersOpen = ref(false);
const columnsOpen = ref(false);
const exporting = ref(false);

const hasSearch = computed(() => props.search !== null);
const activeFilters = computed(() => Object.values(props.filterValues ?? {}).filter((v) => v !== '' && v != null).length);
const isDirty = computed(() => Boolean(props.search) || activeFilters.value > 0);

function setSearch(value) {
    emit('update:search', value);
}

function setFilter(key, value) {
    emit('update:filterValues', { ...props.filterValues, [key]: value });
}

function apply() {
    filtersOpen.value = false;
    emit('apply');
}

/** Clears the search and every filter in one go, then reloads. */
function resetAll() {
    emit('update:search', '');
    emit('update:filterValues', Object.fromEntries(Object.keys(props.filterValues ?? {}).map((k) => [k, ''])));
    filtersOpen.value = false;
    emit('reset');
}

/* ── export ─────────────────────────────────────────────────────────────── */

/**
 * The server builds the file, so what downloads is the whole filtered set rather
 * than the 25 rows on screen. Which columns, and in what order, is the reader's
 * current choice — so the spreadsheet matches what they are looking at.
 */
async function exportRows() {
    if (exporting.value) return;

    exporting.value = true;

    try {
        const { data } = await client.get(props.exportUrl, {
            responseType: 'blob',
            params: {
                ...props.filterValues,
                search: props.search ?? '',
                columns: visibleColumns.value.map((c) => c.key).join(','),
                labels: visibleColumns.value.map((c) => c.label).join('|'),
                filename: props.exportName,
            },
        });

        const url = URL.createObjectURL(data);
        const a = document.createElement('a');

        a.href = url;
        a.download = `${props.exportName}-${new Date().toISOString().slice(0, 10)}.xlsx`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    } catch (e) {
        toast.error('Export failed. Please try again.');
    } finally {
        exporting.value = false;
    }
}

/* ── sorting ────────────────────────────────────────────────────────────── */

// A dotted key names a field on a related record, which the API cannot ORDER BY.
const sortable = (col) => col.sortable !== false && !String(col.key).includes('.');

/** First click sorts ascending; clicking the same column again reverses it. */
function toggleSort(col) {
    if (!sortable(col)) return;

    const same = props.sort?.key === col.key;

    emit('update:sort', { key: col.key, dir: same && props.sort.dir === 'asc' ? 'desc' : 'asc' });
    emit('apply');
}

function sortIcon(col) {
    if (!sortable(col)) return '';
    if (props.sort?.key !== col.key) return 'mdi-unfold-more-horizontal';

    return props.sort.dir === 'asc' ? 'mdi-arrow-up' : 'mdi-arrow-down';
}

/* ── grid ───────────────────────────────────────────────────────────────── */

const span = computed(
    () => visibleColumns.value.length + (props.index ? 1 : 0) + (props.hideActions ? 0 : 1),
);

/** Row 26 on page two reads 26, not 1. */
const serial = (i) => (props.meta?.from ?? 1) + i;

/**
 * What a cell shows.
 *
 * A key may walk a relation — `item.code` — so a register can name the thing a
 * foreign key points at instead of its id. And a value that turns out to be an
 * object is never printed raw: an eager-loaded relation stringifies to its entire
 * JSON, which is what a cell full of `{"id":26,"uuid":"6f50…` was. It falls back
 * to whatever that record is known by.
 */
function cellValue(row, key) {
    const value = String(key).split('.').reduce((o, k) => (o == null ? o : o[k]), row);

    if (value === null || value === undefined || value === '') return '-';

    if (typeof value === 'object') {
        return value.name ?? value.code ?? value.number ?? value.title ?? value.label ?? '-';
    }

    if (typeof value === 'boolean') return value ? 'Yes' : 'No';

    return format(value, key);
}

/*
 * The API returns what the database holds, which is not what a register should
 * print: a cast date arrives as `2026-04-28T00:00:00.000000Z`, a decimal column as
 * `334.0000`, and a boolean the model never cast as `1`. Formatting here rather
 * than on fifty screens means one rule per shape, and a column can still opt out
 * with `format: 'raw'` or pick a different one.
 */
function format(value, key) {
    const column = props.columns.find((c) => c.key === key);

    if (column?.format === 'raw') return value;

    // A date, or a timestamp whose time is midnight, reads as a date.
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}([T ]|$)/.test(value)) {
        const date = new Date(value.length <= 10 ? `${value}T00:00:00` : value);

        if (!Number.isNaN(date.getTime())) {
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    }

    /*
     * `is_active` and friends are 0/1 in the payload whenever the model did not
     * cast them. The name is the only thing that separates a flag from a count,
     * so the name is what decides.
     */
    if (/^(is|has|can)_/.test(key) && (value === 0 || value === 1 || value === '0' || value === '1')) {
        return Number(value) ? 'Yes' : 'No';
    }

    /*
     * Decimal columns come back as strings with the column's full scale —
     * `334.0000`, `42.0000`. Trailing zeros are noise. A string with no decimal
     * point is left alone, so an account number is not turned into 40,218,882.
     */
    const numeric = typeof value === 'number' || (typeof value === 'string' && /^-?\d+\.\d+$/.test(value));

    if (numeric) {
        const n = Number(value);

        return n.toLocaleString('en-US', { maximumFractionDigits: 2 });
    }

    /*
     * Enum values are stored lower_snake and should not be read that way:
     * `in_progress` is "In progress". The pattern is deliberately narrow — all
     * lower case, letters, digits and underscores only — so it cannot catch a
     * code (upper case), an email (has @) or a period like `2026-08` (has a dash).
     */
    if (typeof value === 'string' && /^[a-z][a-z0-9_]*$/.test(value)) {
        return value.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
    }

    return value;
}

const pages = computed(() => {
    if (!props.meta) return [];

    const { current_page, last_page } = props.meta;
    const range = [];

    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) range.push(i);

    return range;
});

// A register that switches identity gets that register's remembered columns.
watch(storageKey, () => { hidden.value = read(); });

function closeMenus(e) {
    if (!e.target.closest('[data-dt-menu]')) {
        columnsOpen.value = false;
    }
}
</script>

<template>
  <div class="card overflow-visible" @mousedown="closeMenus">
    <div v-if="$slots.header" class="card-header"><slot name="header" /></div>

    <!-- ── Toolbar ───────────────────────────────────────────────────────── -->
    <div v-if="!hideToolbar" class="card-header">
      <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
        <div v-if="hasSearch" class="relative">
          <i class="mdi mdi-magnify pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[16px] text-hb-mut" />
          <input
            :value="search"
            type="search"
            :placeholder="searchPlaceholder"
            class="hb-input-sm w-64 max-w-full !pl-8"
            @input="setSearch($event.target.value)"
            @keyup.enter="apply" />
        </div>

        <button v-if="hasSearch" type="button" class="btn btn-secondary btn-sm" @click="apply">
          Search
        </button>

        <button
          v-if="filters.length"
          type="button"
          class="btn btn-secondary btn-sm"
          @click="filtersOpen = !filtersOpen">
          <i class="mdi mdi-filter-variant" />
          More filters
          <span v-if="activeFilters" class="hb-pill neutral">{{ activeFilters }}</span>
        </button>

        <button v-if="isDirty" type="button" class="btn btn-secondary btn-sm" @click="resetAll">
          <i class="mdi mdi-close" />
          Reset
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <slot name="toolbar" />

        <!-- Column visibility -->
        <div class="relative" data-dt-menu>
          <button type="button" class="btn btn-secondary btn-sm" @click="columnsOpen = !columnsOpen">
            <i class="mdi mdi-view-column" />
            Columns
            <span v-if="hiddenCount" class="hb-pill neutral">{{ hiddenCount }} hidden</span>
          </button>

          <div
            v-if="columnsOpen"
            class="absolute right-0 top-[calc(100%+6px)] z-30 max-h-[320px] w-60 overflow-y-auto rounded-hb-panel border border-hb-line bg-white p-1.5 shadow-hb-sheet">
            <label
              v-for="col in columns"
              :key="col.key"
              class="flex cursor-pointer items-center gap-2.5 rounded-hb-ctl px-2.5 py-1.5 text-hb-body font-semibold hover:bg-hb-hover">
              <input type="checkbox" :checked="!hidden.has(col.key)" @change="toggleColumn(col.key)" />
              {{ col.label }}
            </label>
            <button
              type="button"
              class="mt-1 w-full rounded-hb-ctl px-2.5 py-1.5 text-left text-hb-sm font-bold text-hb-brand hover:bg-hb-hover"
              @click="showAllColumns">
              Show all columns
            </button>
          </div>
        </div>

        <button
          v-if="exportUrl"
          type="button"
          class="btn btn-secondary btn-sm"
          :disabled="exporting"
          @click="exportRows">
          <i class="mdi" :class="exporting ? 'mdi-loading animate-spin' : 'mdi-file-excel-outline'" />
          {{ exporting ? 'Exporting…' : 'Export' }}
        </button>
      </div>
    </div>

    <!-- ── Filter drawer ─────────────────────────────────────────────────── -->
    <div v-if="filtersOpen && filters.length" class="border-b border-hb-line bg-hb-hover px-[18px] py-4">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="f in filters" :key="f.key">
          <label class="hb-lbl" :for="`dt-f-${f.key}`">{{ f.label }}</label>
          <select
            v-if="f.type === 'select'"
            :id="`dt-f-${f.key}`"
            class="hb-input-sm"
            :value="filterValues[f.key] ?? ''"
            @change="setFilter(f.key, $event.target.value)">
            <option value="">All</option>
            <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
          <input
            v-else
            :id="`dt-f-${f.key}`"
            class="hb-input-sm"
            :type="f.type || 'text'"
            :value="filterValues[f.key] ?? ''"
            @input="setFilter(f.key, $event.target.value)"
            @keyup.enter="apply" />
        </div>
      </div>

      <div class="mt-3 flex items-center gap-2">
        <button type="button" class="btn btn-primary btn-sm" @click="apply">Apply filters</button>
        <button type="button" class="btn btn-secondary btn-sm" @click="resetAll">Clear all</button>
      </div>
    </div>

    <div class="overflow-x-auto px-2">
      <table class="data-table">
        <thead>
          <tr>
            <th v-if="index" class="w-12 text-right">#</th>
            <th
              v-for="col in visibleColumns"
              :key="col.key"
              :class="[col.class, sortable(col) && 'is-sortable']"
              :aria-sort="sort?.key === col.key ? (sort.dir === 'asc' ? 'ascending' : 'descending') : 'none'"
              @click="toggleSort(col)">
              {{ col.label }}<i
                v-if="sortable(col)"
                class="mdi dt-sort"
                :class="[sortIcon(col), sort?.key === col.key && 'is-on']" />
            </th>
            <th v-if="!hideActions" class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="span" class="text-center"><loading-spinner /></td>
          </tr>
          <tr v-else-if="!items?.length">
            <td :colspan="span"><empty-state :message="emptyText" /></td>
          </tr>
          <tr v-for="(item, i) in items" v-else :key="item.id ?? i">
            <td v-if="index" class="tabular text-right text-hb-mut">{{ serial(i) }}</td>
            <td v-for="col in visibleColumns" :key="col.key" :class="col.cellClass">
              <slot :name="'cell-' + col.key" :item="item" :value="item[col.key]">
                {{ cellValue(item, col.key) }}
              </slot>
            </td>
            <td v-if="!hideActions" class="whitespace-nowrap text-right">
              <slot name="actions" :item="item" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- .hb-foot: the count on one side, the pager on the other. -->
    <div v-if="meta" class="card-header">
      <span class="tabular text-[13px] text-hb-mut">
        Showing {{ meta.from }}-{{ meta.to }} of {{ meta.total }}
      </span>
      <ul class="pagination">
        <li v-for="p in pages" :key="p" class="page-item" :class="p === meta.current_page && 'active'">
          <button type="button" class="page-link" @click="$emit('page', p)">{{ p }}</button>
        </li>
      </ul>
    </div>
  </div>
</template>
