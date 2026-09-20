<?php

namespace Database\Seeders;

use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        $data = [];
        $data[] = ['name' => 'rahees', 'email' => 'rahees@iocod.com', 'cell_phone' => '(963) 315-5669', 'user_Type_id' => UserType::Admin, 'company_id' => null, 'password' => Hash::make('asdasd')];
        $data[] = ['name' => 'iocod', 'email' => 'iocod@iocod.com', 'cell_phone' => '(963) 315-5669', 'user_Type_id' => UserType::Admin, 'company_id' => null, 'password' => Hash::make('asdasd')];
        // $data[] = [ 'name' => 'AgentFee', 'email'    => 'AgentFee@iocod.com', 'cell_phone'    => '9633155669', 'user_Type_id' => UserType::AgentFee,'company_id'=>NULL, 'password'    => Hash::make('asdasd') ];
        // $data[] = [ 'name' => 'OverPayment', 'email' => 'OverPayment@iocod.com', 'cell_phone' => '9633155669', 'user_Type_id' => UserType::OverPayment,'company_id'=>NULL, 'password' => Hash::make('asdasd') ];
        for ($i = 1; $i <= 0; $i++) {
            $data[] = ['name' => "Company-$i", 'email' => "Company-$i@iocod.com", 'cell_phone' => '9633155669', 'user_Type_id' => UserType::Company, 'company_id' => null, 'password' => Hash::make('asdasd')];
        }
        for ($i = 1; $i <= 0; $i++) {
            $data[] = ['name' => "Lender-$i", 'email' => "Lender-$i@iocod.com", 'cell_phone' => '9633155669', 'user_Type_id' => UserType::Lender, 'company_id' => null, 'password' => Hash::make('asdasd')];
        }
        for ($i = 1; $i <= 0; $i++) {
            $data[] = ['name' => "Investor-$i", 'email' => "Investor-$i@iocod.com", 'cell_phone' => '9633155669', 'user_Type_id' => UserType::Investor, 'company_id' => rand(5, 9), 'password' => Hash::make('asdasd')];
        }
        DB::table('users')->insert($data);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
