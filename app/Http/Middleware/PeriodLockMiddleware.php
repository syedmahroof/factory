<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\PeriodClose;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PeriodLockMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $date = $request->input('date') ?? $request->input('invoice_date') ?? $request->input('order_date');
        
        if ($date) {
            $isLocked = PeriodClose::where('module', 'finance')
                ->where('is_locked', true)
                ->whereHas('fiscalPeriod', function ($q) use ($date) {
                    $q->where('start_date', '<=', $date)->where('end_date', '>=', $date);
                })
                ->exists();

            if ($isLocked) {
                throw new HttpException(422, 'Cannot post to a closed financial period.');
            }
        }

        return $next($request);
    }
}
