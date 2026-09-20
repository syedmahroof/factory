<?php
namespace App\Actions\Engineering;

use App\Models\Bom;
use App\Models\BomLine;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateBom
{
    public function execute(array $data): Bom
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $bom = Bom::create($data);

            foreach ($lines as $line) {
                $line['bom_id'] = $bom->id;
                BomLine::create($line);
            }

            AuditService::log('created', 'engineering', $bom, null, $data);
            return $bom;
        });
    }
}