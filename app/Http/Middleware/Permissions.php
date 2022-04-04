<?php

namespace App\Http\Middleware;

use Closure;

class Permissions {

    /**
     * Checking user permissions on administrator.
     */
    public function handle($request, Closure $next) {
        if(auth()->user()->role != "admin") {
            return response()->json([
                "status" => false,
                "message" => "Permissions denied!"
            ]);
        }
        return $next($request);
    }

}