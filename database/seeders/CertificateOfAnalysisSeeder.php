<?php

namespace Database\Seeders;

use App\Models\CertificateOfAnalysis;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CertificateOfAnalysisSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('certificates_of_analysis')) {
            $this->command?->warn('Skipping CertificateOfAnalysisSeeder: table "certificates_of_analysis" does not exist in the current schema.');

            return;
        }

        CertificateOfAnalysis::create([
            'number' => 'COA-0001',
            'inspection_id' => 1,
            'item_id' => 1,
            'issued_by' => 1,
            'issue_date' => now()->toDateString(),
            'status' => 'issued',
        ]);
    }
}
