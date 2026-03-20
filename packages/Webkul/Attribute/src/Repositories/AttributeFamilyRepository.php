<?php

declare (strict_types=1);
namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
class Attribute_Family_Repository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Repository $attribute_repository, protected Attribute_Group_Repository $attribute_group_repository, Container $container)
    {
        parent::__construct($container);
    }
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeFamily';
    }
    /**
     * @return \Webkul\Attribute\Contracts\AttributeFamily
     */
    public function create(array $data)
    {
        $attribute_groups = $data['attribute_groups'] ?? [];
        unset($data['attribute_groups']);
        $family = parent::create($data);
        foreach ($attribute_groups as $group) {
            $custom_attributes = $group['custom_attributes'] ?? [];
            unset($group['custom_attributes']);
            $attribute_group = $family->attribute_groups()->create($group);
            foreach ($custom_attributes as $key => $attribute) {
                $attribute_model = isset($attribute['id']) ? $this->attribute_repository->find($attribute['id']) : $this->attribute_repository->find_one_by_field('code', $attribute['code']);
                $attribute_group->custom_attributes()->save($attribute_model, ['position' => $key + 1]);
            }
        }
        return $family;
    }
    /**
     * @param  int  $id
     * @return \Webkul\Attribute\Contracts\AttributeFamily
     */
    public function update(array $data, $id)
    {
        $family = parent::update($data, $id);
        $previous_attribute_group_ids = $family->attribute_groups()->pluck('id');
        foreach ($data['attribute_groups'] ?? [] as $attribute_group_id => $attribute_group_inputs) {
            if (Str::contains($attribute_group_id, 'group_')) {
                $attribute_group = $family->attribute_groups()->create($attribute_group_inputs);
                if (empty($attribute_group_inputs['custom_attributes'])) {
                    continue;
                }
                foreach ($attribute_group_inputs['custom_attributes'] as $attribute_inputs) {
                    $attribute = $this->attribute_repository->find($attribute_inputs['id']);
                    $attribute_group->custom_attributes()->save($attribute, ['position' => $attribute_inputs['position']]);
                }
            } else {
                if (is_numeric($index = $previous_attribute_group_ids->search($attribute_group_id))) {
                    $previous_attribute_group_ids->forget($index);
                }
                $attribute_group = $this->attribute_group_repository->update($attribute_group_inputs, $attribute_group_id);
                $previous_attribute_ids = $attribute_group->custom_attributes()->get()->pluck('id');
                foreach ($attribute_group_inputs['custom_attributes'] ?? [] as $attribute_inputs) {
                    if (is_numeric($index = $previous_attribute_ids->search($attribute_inputs['id']))) {
                        $previous_attribute_ids->forget($index);
                        $attribute_group->custom_attributes()->update_existing_pivot($attribute_inputs['id'], ['position' => $attribute_inputs['position']]);
                    } else {
                        $attribute = $this->attribute_repository->find($attribute_inputs['id']);
                        $attribute_group->custom_attributes()->save($attribute, ['position' => $attribute_inputs['position']]);
                    }
                }
                if ($previous_attribute_ids->count()) {
                    $attribute_group->custom_attributes()->detach($previous_attribute_ids);
                }
            }
        }
        foreach ($previous_attribute_group_ids as $attribute_group_id) {
            $this->attribute_group_repository->delete($attribute_group_id);
        }
        return $family;
    }
    /**
     * @return array
     */
    public function get_partial()
    {
        $attribute_families = $this->model->all();
        $trimmed = [];
        foreach ($attribute_families as $key => $attribute_family) {
            if ($attribute_family->name != null || $attribute_family->name != '') {
                $trimmed[$key] = ['id' => $attribute_family->id, 'code' => $attribute_family->code, 'name' => $attribute_family->name];
            }
        }
        return $trimmed;
    }
    /**
     * Get all the comparable attributes which belongs to attribute family.
     */
    public function get_comparable_attributes_belongs_to_family()
    {
        return $this->attribute_repository->with(['options', 'options.translations'])->join('attribute_group_mappings', 'attribute_group_mappings.attribute_id', '=', 'attributes.id')->select('attributes.*')->where('attributes.is_comparable', 1)->where_not_in('code', ['name', 'price'])->distinct()->get();
    }
}