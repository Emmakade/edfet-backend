<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlockSuspiciousIps
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        $blocked = DB::table('blocked_ips')
            ->where('ip', $ip)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                      ->orWhere('blocked_until', '>', now());
            })
            ->exists();

        if ($blocked) {
            return response()->json([
                'message' => 'Your IP has been temporarily blocked'
            ], 403);
        }

        return $next($request);
    }
}
