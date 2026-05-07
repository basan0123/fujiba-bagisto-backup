<?php

namespace Fujiba\PwaFrontend\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Dynamic robots.txt — emits the correct Sitemap: line for whichever
 * hostname the request came in on (snssale.com, kimono.fujiba.co.jp,
 * mmfood.fujiba.co.jp, etc.). The static file at public/robots.txt was
 * moved to robots.txt.bak-snssale on 2026-05-15 so nginx falls through
 * to this route.
 */
class RobotsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $scheme = $request->getScheme();
        $host   = $request->getHttpHost();
        $body = <<<TXT
User-agent: *
Disallow: /admin
Disallow: /admin/*
Disallow: /customer/account
Disallow: /customer/account/*
Disallow: /checkout
Disallow: /checkout/*
Disallow: /cart
Disallow: /search
Disallow: /api/
Disallow: /*?currency=
Disallow: /*&currency=
Allow: /

Sitemap: $scheme://$host/sitemap.xml

TXT;

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
