<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Traits\Sanitizer;
class Tiny_Mce_Controller extends Controller
{
    use Sanitizer;
    /**
     * Storage folder path.
     *
     * @var string
     */
    private $storage_path = 'tinymce';
    /**
     * Allowed image MIME types.
     *
     * @var array
     */
    private $allowed_mime_types = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml', 'image/webp'];
    /**
     * Upload file from tinymce.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload()
    {
        $result = $this->store_media();
        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }
        if (!empty($result)) {
            return response()->json(['location' => $result['file_url']]);
        }
        return response()->json(['error' => trans('admin::app.components.tinymce.errors.file-upload-failed')], 400);
    }
    /**
     * Store media.
     *
     * @return array
     */
    public function store_media()
    {
        if (!request()->has_file('file')) {
            return ['error' => trans('admin::app.components.tinymce.errors.no-file-uploaded')];
        }
        $file = request()->file('file');
        $mime_type = $file->get_mime_type();
        if (!in_array($mime_type, $this->allowed_mime_types)) {
            return ['error' => trans('admin::app.components.tinymce.errors.invalid-file-type')];
        }
        $extension = strtolower($file->get_client_original_extension());
        $valid_extensions = ['image/jpeg' => ['jpg', 'jpeg'], 'image/jpg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/gif' => ['gif'], 'image/webp' => ['webp'], 'image/svg+xml' => ['svg']];
        if (!isset($valid_extensions[$mime_type]) || !in_array($extension, $valid_extensions[$mime_type])) {
            return ['error' => trans('admin::app.components.tinymce.errors.file-extension-mismatch')];
        }
        $path = $file->store($this->storage_path);
        $this->sanitize_svg($path, $mime_type);
        return ['file' => $path, 'file_name' => $file->get_client_original_name(), 'file_url' => Storage::url($path)];
    }
}