<?php

declare (strict_types=1);
namespace Webkul\Core\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
class Channel_Repository extends Repository
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
        $model = $this->get_model();
        foreach (core()->get_all_locales() as $locale) {
            foreach ($model->translated_attributes as $attribute) {
                if (isset($data[$attribute])) {
                    $data[$locale->code][$attribute] = $data[$attribute];
                }
            }
        }
        $channel = parent::create($data);
        $channel->locales()->sync($data['locales']);
        $channel->currencies()->sync($data['currencies']);
        $channel->inventory_sources()->sync($data['inventory_sources']);
        $this->upload_images($data, $channel);
        $this->upload_images($data, $channel, 'favicon');
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
        $this->upload_images($data, $channel);
        $this->upload_images($data, $channel, 'favicon');
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
    public function upload_images($data, $channel, $type = 'logo')
    {
        if (request()->has_file($type)) {
            $channel->{$type} = current(request()->file($type))->store('channel/' . $channel->id);
            $channel->save();
        } else if (!isset($data[$type])) {
            if (!empty($data[$type])) {
                Storage::delete($channel->{$type});
            }
            $channel->{$type} = null;
            $channel->save();
        }
    }
}