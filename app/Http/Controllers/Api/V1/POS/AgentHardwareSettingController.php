<?php

namespace App\Http\Controllers\Api\V1\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Pos\Models\AgentHardwareSetting;

class AgentHardwareSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $agent   = auth('pos')->user();
        $setting = AgentHardwareSetting::firstOrNew(['agent_id' => $agent->id]);

        return response()->json([
            'agent_id'        => $agent->id,
            'paper_size'      => $setting->paper_size      ?? '80mm',
            'default_printer' => $setting->default_printer ?? null,
            'default_scanner' => $setting->default_scanner ?? 'keyboard-wedge',
            'extra'           => $setting->extra           ?? null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'paper_size'      => 'sometimes|in:80mm,58mm',
            'default_printer' => 'sometimes|nullable|string|max:255',
            'default_scanner' => 'sometimes|nullable|string|max:255',
            'extra'           => 'sometimes|nullable|array',
        ]);

        $agent   = auth('pos')->user();
        $setting = AgentHardwareSetting::updateOrCreate(
            ['agent_id' => $agent->id],
            $data,
        );

        return response()->json([
            'agent_id'        => $agent->id,
            'paper_size'      => $setting->paper_size,
            'default_printer' => $setting->default_printer,
            'default_scanner' => $setting->default_scanner,
            'extra'           => $setting->extra,
        ]);
    }
}
