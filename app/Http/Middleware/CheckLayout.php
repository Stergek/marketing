<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLayout
{
    public function handle(Request $request, Closure $next)
    {
        view()->share('useCustomLayout', config('custom.use_custom_layout', true));
        return $next($request);
    }
}