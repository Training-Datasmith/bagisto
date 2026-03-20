<?php

declare (strict_types=1);
namespace Webkul\Core\Traits;

use enshrined\Svg_Sanitize\Sanitizer as MainSanitizer;
use Illuminate\Support\Facades\Storage;
trait Sanitizer
{
    /**
     * List of mime types which needs to check.
     */
    public $mime_types = ['image/svg', 'image/svg+xml'];
    /**
     * Sanitize SVG file.
     *
     * @param  string  $path
     * @return void
     */
    public function sanitize_svg($path, $mime_type)
    {
        if ($this->check_mime_type($mime_type)) {
            $sanitizer = new Main_Sanitizer();
            $sanitizer->remove_remote_references(true);
            $dirty_svg = Storage::get($path);
            Storage::put($path, $sanitizer->sanitize($dirty_svg));
        }
    }
    /**
     * Sanitize SVG file.
     *
     * @param  string  $path
     * @return void
     */
    public function check_mime_type($mime_type)
    {
        return in_array($mime_type, $this->mime_types);
    }
}