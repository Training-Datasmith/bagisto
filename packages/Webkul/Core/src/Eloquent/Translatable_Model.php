<?php

declare (strict_types=1);
namespace Webkul\Core\Eloquent;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Helpers\Locales;
class Translatable_Model extends Model
{
    use Translatable;
    /**
     * Get locales helper.
     */
    protected function get_locales_helper(): Locales
    {
        return app(Locales::class);
    }
    /**
     * Locale. This method is being overridden to address the
     * performance issues caused by the existing implementation
     * which increases application time.
     *
     * @return string
     */
    protected function locale()
    {
        if ($this->is_channel_based()) {
            return core()->get_default_locale_code_from_default_channel();
        } else {
            if ($this->default_locale) {
                return $this->default_locale;
            }
            return config('translatable.locale') ?: app()->make('translator')->get_locale();
        }
    }
    /**
     * Is channel based.
     *
     * @return bool
     */
    protected function is_channel_based()
    {
        return false;
    }
    public function scope_where_translation_in(Builder $query, string $translation_field, $value, ?string $locale = null, string $method = 'whereHas')
    {
        return $query->{$method}('translations', function (Builder $query) use ($translation_field, $value, $locale) {
            $query->where_in($this->get_translations_table() . '.' . $translation_field, $value);
            if ($locale) {
                $query->where_in($this->get_translations_table() . '.' . $this->get_locale_key(), $locale);
            }
        });
    }
}