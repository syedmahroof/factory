<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        DB::table('settings')->truncate();
        $data = [];
        $data[] = ['key' => 'admin_email', 'values' => 'emailnotification22@gmail.com'];
        $data[] = ['key' => 'system_admin', 'values' => 'ipnotifications@vgusa.com'];
        $data[] = ['key' => 'minimum_investment_value', 'values' => '15'];
        $data[] = ['key' => 'max_investment_percentage', 'values' => '15'];
        $data[] = ['key' => 'max_assign_percentage', 'values' => '20'];
        $data[] = ['key' => 'enable_email_alerts', 'values' => true];
        DB::table('settings')->insert($data);
    }
}
