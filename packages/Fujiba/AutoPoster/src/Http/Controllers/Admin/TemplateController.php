<?php

namespace Fujiba\AutoPoster\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Fujiba\AutoPoster\Models\AutoPosterTemplate;
use Fujiba\AutoPoster\Services\CaptionGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Webkul\Core\Models\Channel;

class TemplateController extends Controller
{
    public function index(Request $request, CaptionGenerator $captions)
    {
        $channels  = Channel::all();
        $channelId = (int) $request->input('channel_id') ?: $channels->first()?->id;

        return view('autoposter::admin.templates', [
            'channels'     => $channels,
            'channelId'    => $channelId,
            'templates'    => AutoPosterTemplate::where('channel_id', $channelId)->orderBy('name')->get(),
            'placeholders' => CaptionGenerator::PLACEHOLDERS,
            'aiAvailable'  => $captions->isAiAvailable(),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'id'            => ['nullable', 'integer'],
            'channel_id'    => ['required', 'integer'],
            'name'          => ['required', 'string', 'max:120'],
            'language_mode' => ['required', Rule::in(['en', 'ja', 'my', 'en_ja', 'en_my', 'en_ja_my', 'custom'])],
            'body_en'       => ['nullable', 'string', 'max:20000'],
            'body_ja'       => ['nullable', 'string', 'max:20000'],
            'body_my'       => ['nullable', 'string', 'max:20000'],
            'campaign_text' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! Channel::where('id', $data['channel_id'])->exists()) {
            return back()->with('error', 'Invalid channel.');
        }

        // A template with no body in any of its selected languages would render
        // an empty caption, so require at least one.
        if (blank($data['body_en'] ?? null) && blank($data['body_ja'] ?? null) && blank($data['body_my'] ?? null)) {
            return back()->with('error', 'Enter caption text for at least one language.');
        }

        $template = $data['id']
            ? AutoPosterTemplate::findOrFail($data['id'])
            : new AutoPosterTemplate(['channel_id' => $data['channel_id']]);

        $template->fill([
            'name'                     => $data['name'],
            'language_mode'            => $data['language_mode'],
            'body_en'                  => $data['body_en'] ?? null,
            'body_ja'                  => $data['body_ja'] ?? null,
            'body_my'                  => $data['body_my'] ?? null,
            'campaign_text'            => $data['campaign_text'] ?? null,
            'include_emojis'           => $request->boolean('include_emojis'),
            'include_hashtags'         => $request->boolean('include_hashtags'),
            'include_campaign_text'    => $request->boolean('include_campaign_text'),
            'include_new_arrival_text' => $request->boolean('include_new_arrival_text'),
            'is_active'                => $request->boolean('is_active'),
        ]);

        $isDefault = $request->boolean('is_default');

        DB::transaction(function () use ($template, $isDefault) {
            $template->is_default = $isDefault;
            $template->save();

            // Exactly one default per channel.
            if ($isDefault) {
                AutoPosterTemplate::where('channel_id', $template->channel_id)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }
        });

        return back()->with('success', 'Template saved.');
    }

    public function destroy(Request $request, int $id)
    {
        $template = AutoPosterTemplate::findOrFail($id);
        $template->delete();

        return back()->with('success', 'Template deleted.');
    }
}
