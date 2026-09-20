<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('document_templates')) {
            $this->command?->warn('Skipping DocumentTemplateSeeder: table "document_templates" does not exist in the current schema.');

            return;
        }

        DocumentTemplate::create([
            'name' => 'Standard Purchase Order',
            'document_type' => 'purchase_order',
            'format' => 'pdf',
            'template_content' => '<h1>Purchase Order {{number}}</h1>',
            'is_default' => true,
        ]);
    }
}
