<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One register, as a spreadsheet.
 *
 * Built from a query rather than a collection so Excel chunks it: the export is
 * the whole filtered set, which for a stock ledger is not something to hold in
 * memory all at once.
 *
 * Columns and their headings come from the screen the reader was looking at, so
 * the file matches the table they exported — including the columns they had
 * hidden, which are absent here too.
 */
class ResourceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  list<string>  $columns
     * @param  list<string>  $headings
     */
    public function __construct(
        private Builder $builder,
        private array $columns,
        private array $headings,
    ) {}

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /** @return list<scalar|null> */
    public function map($row): array
    {
        return array_map(fn (string $column) => $this->cell($row->{$column} ?? null), $this->columns);
    }

    public function styles(Worksheet $sheet): array
    {
        // The heading row is the only thing styled: a spreadsheet someone is
        // about to pivot should arrive as data, not as a design.
        return [1 => ['font' => ['bold' => true]]];
    }

    /**
     * Excel takes scalars. A cast that produced a date, an enum or a JSON column
     * is written as text rather than as "Object" or an exception.
     */
    private function cell(mixed $value): string|int|float|null
    {
        return match (true) {
            $value === null, is_scalar($value) => $value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \Stringable, method_exists($value, '__toString') => (string) $value,
            default => json_encode($value),
        };
    }
}
