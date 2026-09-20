<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    public function run()
    {
        // FOREIGN_KEY_CHECKS is a flat session flag with no nesting, so restore
        // whatever it was instead of forcing it back on: DatabaseSeeder disables
        // it for the whole run, and the seeders after this one still need it off.
        $previous = DB::selectOne('select @@SESSION.foreign_key_checks as enabled')->enabled;
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $data = [];
        $data[] = ['name' => 'Admin'];
        $data[] = ['name' => 'Investor'];
        $data[] = ['name' => 'Branch Manager'];
        $data[] = ['name' => 'Lender'];
        $data[] = ['name' => 'Editor'];
        $data[] = ['name' => 'Company'];
        $data[] = ['name' => 'Merchant'];
        $data[] = ['name' => 'Viewer'];
        $data[] = ['name' => 'Collection User'];
        $data[] = ['name' => 'Wire Ach'];
        $data[] = ['name' => 'Accounts'];
        $data[] = ['name' => 'Editor with creditcard access'];
        $data[] = ['name' => 'OverPayment'];
        $data[] = ['name' => 'Crm'];
        $data[] = ['name' => 'Agent Fee'];
        $data[] = ['name' => 'Accounting dschug'];
        $data[] = ['name' => 'Merchant Fees'];
        try {
            DB::table('user_types')->truncate();
            DB::table('user_types')->insert($data);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS='.$previous.';');
        }
    }
}
