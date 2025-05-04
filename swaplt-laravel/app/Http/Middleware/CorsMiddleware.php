<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Manejar preflight OPTIONS
        if ($request->isMethod('OPTIONS')) {
            return response()->json('OK', 200, [
                'Access-Control-Allow-Origin' => 'https://angular.swaplt-tfc.duckdns.org', // Cambia esto si tu frontend está en otro origen
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            ]);
        }

        // Continuar con la solicitud y agregar headers CORS
        $response = $next($request);

        // Aquí defines los headers que necesitas:
        $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
            // Opcional: Si necesitas cookies o auth:
            // 'Access-Control-Allow-Credentials' => 'true',
        ]);

        return $response;
    }
}
