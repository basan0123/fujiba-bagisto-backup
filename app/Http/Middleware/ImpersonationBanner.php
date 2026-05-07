<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the current admin session was started via super-admin impersonation,
 * inject a persistent red banner into every HTML response so the
 * impersonator can never forget they're acting on someone else's data.
 *
 * Banner is only injected on text/html responses to avoid corrupting JSON.
 */
class ImpersonationBanner
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $imp = $request->session()->get('impersonation');
        if (! $imp) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</body>')) {
            return $response;
        }

        $started = htmlspecialchars($imp['started_at'] ?? '', ENT_QUOTES);
        $stopUrl = url('/admin/impersonation/stop');
        $banner = <<<HTML
<div id="impersonation-banner" style="position:fixed; bottom:0; left:0; right:0; z-index:99999; background:#dc3545; color:#fff; padding:10px 16px; font-family:system-ui,Arial,sans-serif; font-size:14px; text-align:center; box-shadow:0 -2px 12px rgba(0,0,0,.3);">
    <strong>⚠️ IMPERSONATING</strong>
    — you are acting as a tenant admin (since {$started}) via super-admin.
    Every action is audit-logged.
    <a href="{$stopUrl}" style="color:#fff; text-decoration:underline; margin-left:12px;">Return to super-admin</a>
</div>
<style>body { padding-bottom: 56px !important; }</style>
HTML;

        $injected = str_replace('</body>', $banner . '</body>', $content);
        $response->setContent($injected);
        return $response;
    }
}
