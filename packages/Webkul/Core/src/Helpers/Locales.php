<?php

declare (strict_types=1);
namespace Webkul\Core\Helpers;

use Astrotomic\Translatable\Locales as BaseLocales;
class Locales extends Base_Locales
{
    /**
     * Load.
     */
    public function load(): void
    {
        $this->locales = [];
        foreach (core()->get_all_locales() as $locale) {
            $this->locales[$locale->code] = $locale->code;
        }
    }
}