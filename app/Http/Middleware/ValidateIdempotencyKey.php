<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateIdempotencyKey
{
    public function handle(Request $request, Closure $next)
    {
        $indempotencyKey = $request->header('Idempotency-Key');

        if (!$indempotencyKey) {
            return response()->json([
                'error' => 'Entidade Nao Processavel'
            ], 422);
        }

        return $next($request);
    }
}
