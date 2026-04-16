<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        DB::table('api_logs')->insert([
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto-detect abusive users: block IP if > 100 requests in 1 minute
        $ip = $request->ip();
        $count = DB::table('api_logs')
            ->where('ip', $ip)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        if ($count > 100) {
            DB::table('blocked_ips')->updateOrInsert(
                ['ip' => $ip],
                ['blocked_until' => now()->addMinutes(100)]
            );
        }

        return $response;
    }
}