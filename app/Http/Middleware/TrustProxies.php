<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Hanya proxy Nuxt di mesin yang sama. Laravel tidak boleh dapat diakses langsung dari luar.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = ['127.0.0.1', '::1'];

    /**
     * Hanya alamat IP yang dipercaya; host/port/proto dari header tidak dipakai.
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR;
}
