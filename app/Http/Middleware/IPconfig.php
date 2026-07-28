<?php

namespace App\Http\Middleware;

use App\Models\IPModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IPconfig
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $ipAddress = $request->ip();

        $ipAddressList = IPModel::pluck('ip_address');

        if (!$ipAddressList->contain($ipAddress)){
            abort(403,'Access denied: Your IP address is not authorised');
        }


        return $next($request);
    }
}
