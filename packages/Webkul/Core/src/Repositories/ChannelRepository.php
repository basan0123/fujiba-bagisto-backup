<?php

namespace Webkul\Core\Repositories;

use App\Services\ImageProcessor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class ChannelRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Core\Contracts\Channel';
    }

    /**
     * Create.
     *
     * @return \Webkul\Core\Contracts\Channel
     */
    public function create(array $data)
    {

        $model = $this->getModel();

        foreach (core()->getAllLocales() as $locale) {
            foreach ($model->translatedAttributes as $attribute) {
                if (isset($data[$attribute])) {
                    $data[$locale->code][$attribute] = $data[$attribute];
                }
            }
        }

        $channel = parent::create($data);

        $channel->locales()->sync($data['locales']);

        $channel->currencies()->sync($data['currencies']);

        $channel->inventory_sources()->sync($data['inventory_sources']);

        $this->uploadImages($data, $channel);

        $this->uploadImages($data, $channel, 'favicon');

        return $channel;
    }

    /**
     * Update.
     *
     * @param  int  $id
     * @return \Webkul\Core\Contracts\Channel
     */
    public function update(array $data, $id)
    {
        $channel = parent::update($data, $id);

        $channel->locales()->sync($data['locales']);

        $channel->currencies()->sync($data['currencies']);

        $channel->inventory_sources()->sync($data['inventory_sources']);

        $this->uploadImages($data, $channel);

        $this->uploadImages($data, $channel, 'favicon');

        return $channel;
    }

    /**
     * Upload images.
     *
     * @param  array  $data
     * @param  \Webkul\Core\Contracts\Channel  $channel
     * @param  string  $type
     * @return void
     */
    public function uploadImages($data, $channel, $type = 'logo')
    {
        if (request()->hasFile($type)) {
            // Run logo/favicon through ImageProcessor so HEIC/AVIF/oversize
            // uploads are normalised to a small WebP. Falls back to raw
            // store() for files we can't decode (e.g. .ico for favicon).
            $uploaded  = current(request()->file($type));
            $processed = app(ImageProcessor::class)->processToWebp($uploaded);

            if ($processed !== null) {
                $path = 'channel/'.$channel->id.'/'.Str::random(40).'.'.$processed['extension'];
                Storage::put($path, $processed['data']);
                $channel->{$type} = $path;
            } else {
                $channel->{$type} = $uploaded->store('channel/'.$channel->id);
            }

            $channel->save();
        } else {
            if (! isset($data[$type])) {
                if (! empty($data[$type])) {
                    Storage::delete($channel->{$type});
                }

                $channel->{$type} = null;

                $channel->save();
            }
        }
    }
}
