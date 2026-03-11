<?php
namespace App\Http\Middleware;

use Closure;

class RedirectWww
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        if (str_starts_with($host, 'www.')) {
            $nonWww = substr($host, 4); // strip www.
            $url = $request->getScheme() . '://' . $nonWww . $request->getRequestUri();
            return redirect()->to($url, 301);
        }

        return $next($request);
    }
}