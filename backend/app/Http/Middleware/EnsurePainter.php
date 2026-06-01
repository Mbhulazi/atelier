<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePainter
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->hasRole('painter')) {
            return response()->json(['message' => 'Painter access required'], 403);
        }

        return $next($request);
    }
}
