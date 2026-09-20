<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('comments')) {
            $this->command?->warn('Skipping CommentSeeder: table "comments" not found.');

            return;
        }

        $po = PurchaseOrder::where('number', 'PO-0001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $po || ! $admin) {
            $this->command?->warn('Skipping CommentSeeder: purchase order or admin user not found.');

            return;
        }

        Comment::firstOrCreate([
            'commentable_type' => PurchaseOrder::class,
            'commentable_id' => $po->id,
            'user_id' => $admin->id,
            'body' => 'Please expedite the steel delivery.',
        ]);
    }
}
