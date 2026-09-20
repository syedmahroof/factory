<?php

namespace App\Jobs\Users;

use App\Events\InvestorLiquidityUpdated;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LiquidityUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    public function handle()
    {
        $User = User::find($this->user_id);
        if ($User) {
            $User->liquidity = $User->TotalLiquidity();
            $User->save();

            event(new InvestorLiquidityUpdated((int) $User->id, (float) $User->liquidity));
        }
    }
}
