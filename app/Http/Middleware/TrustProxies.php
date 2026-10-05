<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustProxies
{
    /**
     * Cloudflare IP ranges (IPv4 and IPv6), used when `venue.trusted_proxies` names `cloudflare`.
     * Updated list: https://www.cloudflare.com/ips/
     */
    protected $cloudflareIPs = [
        // IPv4
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',

        // IPv6
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * Handle an incoming request.
     *
     * Applied to the API as well as the web group since 5 Oct, and that was the §3e defect rather
     * than the numbers: as `TrustCloudflareProxies` on the `web` group only, every `/api/v1` request
     * was rate-limited by the address the connection came from — a Cloudflare edge node — instead of
     * the phone's. `login-code` at 60/min was therefore never 60 per phone; it was 60 per edge node,
     * shared by the whole event.
     *
     * The list comes from `config/venue.php` so a venue can name its own proxy, and it must only ever
     * contain the proxy: anything in it can claim any address it likes through `X-Forwarded-For`.
     * Empty is the right answer on a LAN where nginx reaches php-fpm directly.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $proxies = $this->proxies();

        if ($proxies !== []) {
            $request->setTrustedProxies(
                $proxies,
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }

        return $next($request);
    }

    /**
     * The configured proxies, with `cloudflare` expanded to the published ranges.
     *
     * @return list<string>
     */
    public function proxies(): array
    {
        $out = [];

        foreach ((array) config('venue.trusted_proxies', []) as $entry) {
            if (strtolower((string) $entry) === 'cloudflare') {
                $out = array_merge($out, $this->cloudflareIPs);

                continue;
            }
            if ($entry !== '' && $entry !== null) {
                $out[] = (string) $entry;
            }
        }

        return array_values(array_unique($out));
    }
}
