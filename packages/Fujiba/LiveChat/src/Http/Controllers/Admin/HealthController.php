<?php

namespace Fujiba\LiveChat\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fujiba\LiveChat\Models\LiveChatSetting;
use Fujiba\LiveChat\Services\ChatpionFailoverService;

class HealthController extends Controller
{
    public function __invoke(Request $request)
    {
        $tenantId = auth()->user()->tenant_id ?? $request->header('X-Tenant-Id', '');
        $settings = LiveChatSetting::where('tenant_id', $tenantId)->get();

        $results = [];
        foreach ($settings as $setting) {
            $service = new ChatpionFailoverService($setting);
            $results[$setting->channel_code] = $service->healthCheck();
        }

        return response()->json($results);
    }
}
