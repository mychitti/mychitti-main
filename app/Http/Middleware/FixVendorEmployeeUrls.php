<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class FixVendorEmployeeUrls
{
    private const VENDOR_HOST = 'vendor.mcvendorhub.com';
    private const STAFF_HOST  = 'vendor-staff.mcvendorhub.com';

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->getHost() !== self::STAFF_HOST) {
            return $response;
        }

        // vendor.php and vendor_employee.php register the same `vendor.*` route names, so
        // route() resolves to whichever was registered last — the vendor-domain copy. Every
        // generated URL therefore points at the vendor host and has to be rewritten back.

        // Redirects carry their target in the Location header, not the body. Left alone, a
        // redirect()->route(...) after a form POST lands staff on the vendor host, where the
        // session cookie is vendor_session and their vendor_employee_session is not sent at
        // all — so VendorMiddleware sees no guard and bounces them to the login page.
        if ($response instanceof RedirectResponse) {
            $response->setTargetUrl($this->rewrite($response->getTargetUrl()));

            return $response;
        }

        // JSON payloads carry redirect targets too (the lab order endpoint returns one).
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            if (is_array($data)) {
                $response->setData($this->rewriteDeep($data));
            }

            return $response;
        }

        if ($response instanceof \Illuminate\Http\Response) {
            $response->setContent($this->rewrite((string) $response->getContent()));
        }

        return $response;
    }

    // Safe against double-rewriting: "vendor-staff.mcvendorhub.com" does not contain the
    // "vendor.mcvendorhub.com" substring, so an already-correct URL is left untouched.
    private function rewrite(string $value): string
    {
        return str_replace(self::VENDOR_HOST, self::STAFF_HOST, $value);
    }

    private function rewriteDeep(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->rewrite($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->rewriteDeep($value);
            }
        }

        return $data;
    }
}
