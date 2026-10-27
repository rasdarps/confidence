<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureApiJson
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        $response = $next($request);
        if ($response->getStatusCode() >= 500) {
            return response()->json(['message' => 'The server could not complete this request.', 'code' => 'server_error'], $response->getStatusCode());
        }
        if ($response->getStatusCode() === 404) {
            return response()->json(['message' => 'Record not found.', 'code' => 'not_found'], 404);
        }
        return $response;
    }
}