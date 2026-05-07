<?php
namespace App\Http\Middleware;

use App\Models\PlatformAnnouncement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects active platform announcements as a top banner on:
 *  - tenant admin HTML pages (config('app.admin_url') routes)
 *  - super-admin HTML pages
 *
 * Announcements live in the central DB. We read via the central connection
 * explicitly so this works even after stancl/tenancy has switched to a
 * tenant connection.
 */
class PlatformAnnouncementBanner
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $path = '/' . ltrim($request->path(), '/');
        $isAdmin      = str_starts_with($path, '/' . trim(config('app.admin_url'), '/'));
        $isSuperAdmin = str_starts_with($path, '/superadmin');
        if (! $isAdmin && ! $isSuperAdmin) {
            return $response;
        }

        $audience = $isSuperAdmin ? 'show_to_super_admins' : 'show_to_tenant_admins';

        $announcements = Cache::remember(
            'platform_announcements_' . $audience,
            30,
            fn () => $this->fetch($audience)
        );

        if (empty($announcements)) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || ! str_contains($content, '</body>')) {
            return $response;
        }

        $html = $this->renderBanner($announcements);
        $response->setContent(str_replace('</body>', $html . '</body>', $content));
        return $response;
    }

    private function fetch(string $audienceColumn): array
    {
        try {
            return DB::connection('mysql')
                ->table('platform_announcements')
                ->where($audienceColumn, true)
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->orderByDesc('id')
                ->limit(3)
                ->get(['id', 'message', 'severity'])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function renderBanner(array $announcements): string
    {
        $bg = ['info' => '#0d6efd', 'warning' => '#ffc107', 'danger' => '#dc3545'];
        $fg = ['info' => '#fff',     'warning' => '#000',    'danger' => '#fff'];

        $items = '';
        foreach ($announcements as $a) {
            $sev = $a->severity ?? 'info';
            $msg = htmlspecialchars($a->message, ENT_QUOTES);
            $bgC = $bg[$sev] ?? $bg['info'];
            $fgC = $fg[$sev] ?? $fg['info'];
            $items .= "<div style=\"background:{$bgC}; color:{$fgC}; padding:8px 16px; font-family:system-ui,Arial,sans-serif; font-size:13px; text-align:center;\">{$msg}</div>";
        }

        return "<div id=\"platform-announcement-stack\" style=\"position:fixed; top:0; left:0; right:0; z-index:99998;\">{$items}</div><style>body { padding-top: 36px !important; }</style>";
    }
}
