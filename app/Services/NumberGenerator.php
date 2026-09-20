<?php
namespace App\Services;

use App\Models\DocumentSequence;
use Illuminate\Support\Str;

class NumberGenerator
{
    public static function next(string $type, ?string $prefix = null): string
    {
        $seq = DocumentSequence::firstOrCreate(
            ['type' => $type],
            ['current_number' => 0, 'prefix' => $prefix ?? strtoupper(Str::limit($type, 2))]
        );

        $seq->increment('current_number');

        return sprintf('%s-%06d', $seq->prefix, $seq->current_number);
    }
}