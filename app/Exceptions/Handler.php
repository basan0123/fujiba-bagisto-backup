<?php
namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\RestApi\Exceptions\Handler as RestApiHandler;

/**
 * Extends Webkul/RestApi's exception handler (which itself extends
 * Webkul/Core's). Both upstream registers shop/admin redirects on
 * AuthenticationException; neither knows about /superadmin/*. Without this
 * override, an unauthenticated request to /superadmin/* gets redirected to
 * the customer login page.
 *
 * Strategy: register our super-admin renderable BEFORE calling parent so
 * Laravel walks our renderable first (Laravel iterates renderables in
 * registration order; first non-null match wins).
 *
 * For non-super-admin paths we return null and fall through to the upstream
 * shop/admin logic.
 */
class Handler extends RestApiHandler
{
    public function register(): void
    {
        if (config('app.debug')) {
            return;
        }

        // Register our super-admin renderable BEFORE parent so it wins for /superadmin/*.
        $this->renderable(function (AuthenticationException $exception, Request $request) {
            if (! str_starts_with(ltrim($request->path(), '/'), 'superadmin')) {
                return null; // fall through to other renderables
            }
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->guest(route('superadmin.login'));
        });

        // POS GraphQL clients (mobile + cashier web) need real error
        // messages, not Webkul's untranslated `rest-api::app.shop.errors.message`
        // string. Intercept Throwable on /graphql before Webkul's catch-all
        // renderable so the GraphQL layer can return a proper error envelope.
        // /superadmin and /admin paths still fall through to Webkul.
        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('graphql') && ! $request->is('graphql/*')) {
                return null;
            }
            Log::error('GraphQL server error: '.$e->getMessage(), [
                'trace' => collect($e->getTrace())->take(8)->all(),
            ]);
            $payload = ['error' => $e->getMessage()];
            if (config('app.debug')) {
                $payload['file'] = $e->getFile().':'.$e->getLine();
                $payload['type'] = get_class($e);
            }
            return response()->json($payload, 500);
        });

        parent::register();
    }
}
