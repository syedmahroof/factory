<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            $this->command?->warn('Skipping NotificationTemplateSeeder: table "notification_templates" does not exist in the current schema.');

            return;
        }

        NotificationTemplate::create([
            'name' => 'Purchase Order Approved',
            'event_type' => 'purchase_order.approved',
            'channel' => 'in_app',
            'subject' => 'PO approved',
            'body' => 'Purchase order {{number}} has been approved.',
            'is_active' => true,
        ]);
    }
}
