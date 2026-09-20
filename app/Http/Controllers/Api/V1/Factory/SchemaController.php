<?php

namespace App\Http\Controllers\Api\V1\Factory;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * What a record looks like, so the client can draw a form for it.
 *
 * The alternative was a hand-written field list per entity in the SPA — thirty-odd
 * of them, each free to drift from the table it writes to. The table is already
 * the authority on what may be stored: which columns exist, which are required,
 * how long a string may be, what an enum permits and which columns point at
 * another table. The same introspection backs the validation in BaseController, so
 * a form drawn from this and the rules that check it cannot disagree.
 */
class SchemaController extends BaseController
{
    /** @var array<string, string> column => foreign table, for the resource in hand. */
    private array $foreignTables = [];

    /** Never offered as a form field. */
    private const HIDDEN = [
        'id', 'uuid', 'created_at', 'updated_at', 'deleted_at',
        'company_id', 'created_by', 'approved_by', 'approved_at',
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    public function show(Request $request, string $resource)
    {
        abort_unless(isset(ExportController::RESOURCES[$resource]), 404, "No schema for '{$resource}'.");

        $class = ExportController::RESOURCES[$resource];
        $model = new $class;
        $table = $model->getTable();
        $fillable = $model->getFillable();

        $this->foreignTables = $this->foreignKeys($table);

        $fields = [];

        foreach (Schema::getColumns($table) as $column) {
            $name = $column['name'];

            if (in_array($name, self::HIDDEN, true)) {
                continue;
            }

            if ($fillable !== [] && ! in_array($name, $fillable, true)) {
                continue;
            }

            $fields[] = $this->field($column, $name);
        }

        $fields = array_map($this->decorate(...), $fields);

        return $this->success([
            'resource' => $resource,
            'label' => Str::headline($resource),
            'singular' => Str::singular(Str::headline($resource)),
            'sections' => $this->sections($fields),
            'fields' => $fields,
        ]);
    }

    /**
     * The four groups a record's fields fall into, in the order someone fills them
     * in: what it is, what it holds, what it points at, and where it stands.
     *
     * Grouping happens here rather than in the SPA so every generated form is
     * arranged the same way, and so the arrangement is derived from the column
     * rather than from a list someone has to maintain per screen. A group with no
     * fields is not returned, so a small table gets one section, not four empty ones.
     *
     * @param  list<array<string, mixed>>  $fields
     * @return list<array{key: string, no: string, title: string, hint: string}>
     */
    private function sections(array $fields): array
    {
        $present = array_unique(array_column($fields, 'section'));

        $all = [
            'identity' => ['Identity', 'what this record is called'],
            'details' => ['Details', 'the figures and text it carries'],
            'links' => ['Links', 'what it belongs to'],
            'status' => ['Dates & status', 'where it stands'],
        ];

        $out = [];
        $n = 0;

        foreach ($all as $key => [$title, $hint]) {
            if (! in_array($key, $present, true)) {
                continue;
            }

            $out[] = [
                'key' => $key,
                'no' => str_pad((string) ++$n, 2, '0', STR_PAD_LEFT),
                'title' => $title,
                'hint' => $hint,
            ];
        }

        return $out;
    }

    /**
     * Give a field the three things a bare column cannot: which section it belongs
     * to, a placeholder that shows the shape of the answer, and a glyph so a long
     * form can be skimmed rather than read.
     *
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function decorate(array $field): array
    {
        $name = $field['name'];
        $type = $field['type'];
        $label = $field['label'];

        /*
         * Matched as whole underscore-separated words, not substrings: `country`
         * contains "count" and was being offered a quantity placeholder, and
         * `postal_code` ends in "code" without being an identifier.
         */
        $words = explode('_', $name);
        $has = fn (array $list) => (bool) array_intersect($words, $list);

        $money = $has(['cost', 'price', 'amount', 'total', 'balance', 'value', 'salary', 'limit', 'salvage']);
        $quantity = $has(['quantity', 'qty', 'count', 'stock', 'level', 'capacity', 'hours', 'days', 'months']);
        $place = $has(['address', 'city', 'state', 'country', 'postal', 'location', 'region']);

        $field['section'] = match (true) {
            $type === 'relation' => 'links',
            in_array($type, ['date', 'datetime-local', 'time'], true) => 'status',
            in_array($name, ['status', 'is_active', 'priority', 'severity', 'criticality'], true) => 'status',
            str_starts_with($name, 'is_'), str_starts_with($name, 'has_') => 'status',
            (bool) preg_match('/^(code|number|name|title|first_name|last_name|legal_name|description|type|category|revision|asset_code|employee_code|skill_name|period)$/', $name) => 'identity',
            default => 'details',
        };

        $field['icon'] = match (true) {
            $type === 'email' => 'mdi-email-outline',
            $type === 'relation' => 'mdi-link-variant',
            $type === 'checkbox' => 'mdi-toggle-switch-outline',
            $type === 'textarea' => 'mdi-note-text-outline',
            $type === 'date', $type === 'datetime-local' => 'mdi-calendar-outline',
            $type === 'time' => 'mdi-clock-outline',
            $type === 'select' => 'mdi-shape-outline',
            $has(['phone', 'mobile', 'cell', 'fax']) => 'mdi-phone-outline',
            $place => 'mdi-map-marker-outline',
            $has(['code', 'barcode', 'number', 'serial']) => 'mdi-barcode',
            $has(['name', 'title']) => 'mdi-format-title',
            $has(['rate', 'percent', 'factor']) => 'mdi-percent-outline',
            $money => 'mdi-currency-usd',
            $quantity => 'mdi-counter',
            $type === 'number' => 'mdi-numeric',
            default => 'mdi-text',
        };

        $field['placeholder'] = match (true) {
            $type === 'select', $type === 'relation' => 'Please select',
            $type === 'email' => 'name@company.com',
            $type === 'textarea' => 'Optional notes…',
            in_array($type, ['date', 'datetime-local', 'time'], true) => '',
            $has(['phone', 'mobile', 'cell', 'fax']) => '(555) 000-0000',
            $name === 'currency' => 'USD',
            $name === 'country' => 'Two-letter code, e.g. GB',
            $name === 'postal_code' => 'Postcode',
            $has(['rate', 'percent']) => '0.00',
            $money => '0.00',
            $quantity => '0',
            $type === 'number' => '0',
            // An identifier, not merely a field whose name happens to end in code.
            $name === 'code' || str_ends_with($name, '_code') => 'e.g. ABC-001',
            $name === 'number' => 'e.g. PO-0001',
            default => $label,
        };

        return $field;
    }

    /** @return array<string, mixed> */
    private function field(array $column, string $name): array
    {
        $type = $column['type'] ?? $column['type_name'];

        $isInteger = in_array($column['type_name'], ['int', 'integer', 'bigint', 'smallint', 'mediumint'], true);
        $isForeignKey = $isInteger && str_ends_with($name, '_id');

        $field = [
            'name' => $name,
            // `_id` is dropped from a foreign key, whose label names the thing it
            // points at — but not from `tax_id`, which is a VAT number and reads
            // as "Tax" without it.
            'label' => $this->label($isForeignKey ? preg_replace('/_id$/', '', $name) : $name),
            'required' => ! $column['nullable'] && $column['default'] === null,
            'default' => $this->defaultValue($column),
            'type' => 'text',
        ];

        if (str_starts_with($type, 'enum(')) {
            return array_merge($field, [
                'type' => 'select',
                'options' => array_map(
                    fn ($v) => ['value' => $v, 'label' => $this->humanise($v)],
                    str_getcsv(substr($type, 5, -1), ',', "'", '\\'),
                ),
            ]);
        }

        // A foreign key becomes a picker the client fills from that register.
        if ($isForeignKey) {
            return array_merge($field, [
                'type' => 'relation',
                'table' => $this->foreignTables[$name] ?? null,
                'resource' => $this->resourceForTable($this->foreignTables[$name] ?? null),
            ]);
        }

        if ($name === 'email' || str_ends_with($name, '_email')) {
            return array_merge($field, ['type' => 'email']);
        }

        return array_merge($field, match ($column['type_name']) {
            'tinyint' => str_contains($type, '(1)') ? ['type' => 'checkbox'] : ['type' => 'number'],
            'int', 'integer', 'bigint', 'smallint', 'mediumint' => ['type' => 'number', 'step' => '1'],
            'decimal', 'numeric', 'float', 'double' => ['type' => 'number', 'step' => 'any'],
            'date' => ['type' => 'date'],
            'datetime', 'timestamp' => ['type' => 'datetime-local'],
            'time' => ['type' => 'time'],
            'text', 'mediumtext', 'longtext', 'json' => ['type' => 'textarea'],
            default => ['type' => 'text', 'maxlength' => $this->lengthOf($type)],
        });
    }

    /**
     * Which table each foreign key points at, read from the constraint rather
     * than guessed from the column name: `base_uom_id` refers to `uoms`, and
     * pluralising the prefix would look for a `base_uoms` table that is not there.
     *
     * @return array<string, string>
     */
    private function foreignKeys(string $table): array
    {
        $map = [];

        foreach (Schema::getForeignKeys($table) as $key) {
            if (count($key['columns']) === 1) {
                $map[$key['columns'][0]] = $key['foreign_table'];
            }
        }

        return $map;
    }

    /** The register that owns a table, so the form can offer its records. */
    private function resourceForTable(?string $table): ?string
    {
        if ($table === null) {
            return null;
        }

        foreach (ExportController::RESOURCES as $slug => $class) {
            if ((new $class)->getTable() === $table) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * The column's own default, so a new record starts where the database says it
     * should. Without it a generated form opened every boolean at "No" — and
     `is_active` defaults to true on most of these tables, so every item created
     * through the form would have arrived switched off.
     */
    private function defaultValue(array $column): string|bool|int|float|null
    {
        $raw = $column['default'];

        if ($raw === null) {
            return null;
        }

        // MySQL reports defaults as strings, quoted for non-numerics.
        $value = trim((string) $raw, "'\"");

        if (in_array($column['type_name'], ['tinyint', 'bool', 'boolean'], true) && str_contains($column['type'], '(1)')) {
            return $value === '1';
        }

        if (strcasecmp($value, 'NULL') === 0 || str_contains($value, 'CURRENT_TIMESTAMP')) {
            return null;
        }

        // A decimal column reports its default at full scale — `0.0000`. The form
        // shows what it is given, and a box opening at 0.0000 reads as a value
        // someone typed rather than as empty.
        if (is_numeric($value)) {
            return 0 + $value;
        }

        return $value;
    }

    private function lengthOf(string $type): int
    {
        return preg_match('/\((\d+)\)/', $type, $m) ? (int) $m[1] : 255;
    }

    /**
     * A column name as a person would write it.
     *
     * `Str::headline` gets the words right and the acronyms wrong — "Base Uom",
     * "Tax Id", "Ncr" — so the ones this domain uses are restored afterwards.
     */
    private function label(string $name): string
    {
        $acronyms = ['Id' => 'ID', 'Uom' => 'UOM', 'Bom' => 'BOM', 'Ncr' => 'NCR', 'Rma' => 'RMA',
            'Capa' => 'CAPA', 'Sku' => 'SKU', 'Hsn' => 'HSN', 'Vat' => 'VAT', 'Url' => 'URL',
            'Api' => 'API', 'Po' => 'PO', 'Grn' => 'GRN', 'Mrp' => 'MRP', 'Wip' => 'WIP'];

        $words = explode(' ', Str::headline($name));

        return implode(' ', array_map(fn ($w) => $acronyms[$w] ?? $w, $words));
    }

    private function humanise(string $value): string
    {
        return Str::headline(preg_replace('/_id$/', '', $value));
    }
}
