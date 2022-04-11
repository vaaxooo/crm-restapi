<?php

namespace App\Http\Middleware;

use Closure;

class PermissionsReporting {

    /**
     * Checking user permissions on Reporting.
     */
    public function handle($request, Closure $next) {
        if(auth()->user()->role != "reporting") {
            return response()->json([
                "status" => false,
                "message" => "Permissions denied!"
            ]);
        }
        return $next($request);
    }

}
