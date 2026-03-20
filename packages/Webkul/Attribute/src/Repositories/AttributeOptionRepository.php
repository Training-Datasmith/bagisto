<?php

declare (strict_types=1);
namespace Webkul\Attribute\Repositories;

use Illuminate\Http\Uploaded_File;
use Webkul\Core\Eloquent\Repository;
class Attribute_Option_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeOption';
    }
    /**
     * @return \Webkul\Attribute\Contracts\AttributeOption
     */
    public function create(array $data)
    {
        $option = parent::create($data);
        $this->upload_swatch_image($data, $option->id);
        return $option;
    }
    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Attribute\Contracts\AttributeOption
     */
    public function update(array $data, $id)
    {
        $option = parent::update($data, $id);
        $this->upload_swatch_image($data, $id);
        return $option;
    }
    /**
     * @param  array  $data
     * @param  int  $optionId
     * @return void
     */
    public function upload_swatch_image($data, $option_id)
    {
        if (empty($data['swatch_value'])) {
            return;
        }
        if ($data['swatch_value'] instanceof Uploaded_File) {
            parent::update(['swatch_value' => $data['swatch_value']->store('attribute_option')], $option_id);
        }
    }
}