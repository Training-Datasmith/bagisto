<?php

declare (strict_types=1);
namespace Webkul\Core\Repositories;

use Illuminate\Http\Uploaded_File;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Contracts\Locale;
use Webkul\Core\Eloquent\Repository;
class Locale_Repository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Locale::class;
    }
    /**
     * Create.
     *
     * @return mixed
     */
    public function create(array $attributes)
    {
        Event::dispatch('core.locale.create.before');
        $locale = parent::create($attributes);
        $this->upload_image($attributes, $locale);
        Event::dispatch('core.locale.create.after', $locale);
        return $locale;
    }
    /**
     * Update.
     *
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        Event::dispatch('core.locale.update.before', $id);
        $locale = parent::update($attributes, $id);
        $this->upload_image($attributes, $locale);
        Event::dispatch('core.locale.update.after', $locale);
        return $locale;
    }
    /**
     * Delete.
     *
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        Event::dispatch('core.locale.delete.before', $id);
        $locale = parent::find($id);
        $locale->delete($id);
        Storage::delete((string) $locale->logo_path);
        Event::dispatch('core.locale.delete.after', $id);
    }
    /**
     * Upload image.
     *
     * @param  array  $attributes
     * @param  \Webkul\Core\Models\Locale  $locale
     * @return void
     */
    public function upload_image($locale_images, $locale)
    {
        if (!isset($locale_images['logo_path'])) {
            if (!empty($locale_images['logo_path'])) {
                Storage::delete((string) $locale->logo_path);
            }
            $locale->logo_path = null;
            $locale->save();
            return;
        }
        foreach ($locale_images['logo_path'] as $image) {
            if ($image instanceof Uploaded_File) {
                $locale->logo_path = $image->store_as('locales', $locale->code . '.' . $image->get_client_original_extension());
                $locale->save();
            }
        }
    }
}