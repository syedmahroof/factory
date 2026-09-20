<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LabelSeeder extends Seeder
{
    public function run()
    {
        DB::table('labels')->truncate();
        $data = [];
        $data[] = ['name' => 'MCA (Default)'];
        $data[] = ['name' => 'Luther Sales'];
        $data[] = ['name' => 'Insurance'];
        DB::table('labels')->insert($data);
    }
}
