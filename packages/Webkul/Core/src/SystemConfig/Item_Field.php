<?php

declare (strict_types=1);
namespace Webkul\Core\System_Config;

use Illuminate\Support\Str;
class Item_Field
{
    /**
     * Laravel to Vee Validation mappings.
     *
     * @var array
     */
    protected $vee_validate_mappings = ['max' => ['text' => 'max', 'number' => 'max_value'], 'min' => ['text' => 'min', 'number' => 'min_value']];
    /**
     * Create a new ItemField instance.
     */
    public function __construct(public string $item_key, public string $name, public string $title, public ?string $info, public string $type, public ?string $path, public ?string $validation, public ?string $depends, public ?string $default, public ?bool $channel_based, public ?bool $locale_based, public ?string $placeholder, public array|string $options, public bool $is_visible = true)
    {
        $this->options = $this->get_options();
    }
    /**
     * Get name of config item.
     */
    public function get_name(): ?string
    {
        return $this->name;
    }
    /**
     * Get info of config item.
     */
    public function get_info(): ?string
    {
        return $this->info ?? '';
    }
    /**
     * Get title of config item.
     */
    public function get_title(): ?string
    {
        return $this->title ?? '';
    }
    /**
     * Get type of config item.
     */
    public function get_type(): string
    {
        return $this->type;
    }
    /**
     * Get path of config item.
     */
    public function get_path(): ?string
    {
        return $this->path;
    }
    /**
     * Get item key of config item.
     */
    public function get_item_key(): string
    {
        return $this->item_key;
    }
    /**
     * Get validation of config item.
     */
    public function get_validations(): ?string
    {
        if (empty($this->validation)) {
            return '';
        }
        foreach ($this->vee_validate_mappings as $laravel_rule => $vee_validate_rule) {
            if (!array_key_exists($this->get_type(), $vee_validate_rule)) {
                continue;
            }
            $this->validation = str_replace($laravel_rule, $vee_validate_rule[$this->get_type()], $this->validation);
        }
        return $this->validation;
    }
    /**
     * Get depends of config item.
     */
    public function get_depends(): ?string
    {
        return $this->depends;
    }
    /**
     * Get default value of config item.
     */
    public function get_default(): ?string
    {
        return $this->default;
    }
    /**
     * Get channel based of config item.
     */
    public function get_channel_based(): ?bool
    {
        return $this->channel_based;
    }
    /**
     * Get locale based of config item.
     */
    public function get_locale_based(): ?bool
    {
        return $this->locale_based;
    }
    /**
     * Get name field for forms in configuration page.
     */
    public function get_name_key(): string
    {
        return $this->item_key . '.' . $this->name;
    }
    /**
     * Check if the field is required.
     */
    public function is_required(): string
    {
        return Str::contains($this->get_validations(), 'required') ? 'required' : '';
    }
    /**
     * Get placeholder of config item.
     */
    public function get_placeholder(): ?string
    {
        return $this->placeholder;
    }
    /**
     * Get options of config item.
     */
    public function get_options(): array
    {
        if (is_array($this->options)) {
            return collect($this->options)->map(fn($option) => ['title' => trans($option['title']), 'value' => $option['value']])->to_array();
        }
        return collect($this->get_field_options($this->options))->map(fn($option) => ['title' => trans($option['title']), 'value' => $option['value']])->to_array();
    }
    /**
     * Convert the field to an array.
     */
    public function to_array()
    {
        return ['name' => $this->get_name(), 'title' => $this->get_title(), 'info' => $this->get_info(), 'type' => $this->get_type(), 'path' => $this->get_path(), 'depends' => $this->get_depends(), 'validation' => $this->get_validations(), 'default' => $this->get_default(), 'channel_based' => $this->get_channel_based(), 'locale_based' => $this->get_locale_based(), 'placeholder' => $this->get_placeholder(), 'options' => $this->get_options(), 'item_key' => $this->get_item_key()];
    }
    /**
     * Get name field for forms in configuration page.
     *
     * @param  string  $key
     * @return string
     */
    public function get_name_field($key = null)
    {
        if (!$key) {
            $key = $this->item_key . '.' . $this->name;
        }
        $name_field = '';
        foreach (explode('.', $key) as $key => $field) {
            $name_field .= $key === 0 ? $field : '[' . $field . ']';
        }
        return $name_field;
    }
    /**
     * Get depend the field name.
     */
    public function get_depend_field_name(): string
    {
        if (empty($depends = $this->get_depends())) {
            return '';
        }
        $depend_name_key = $this->get_item_key() . '.' . collect(explode(':', $depends))->first();
        return $this->get_name_field($depend_name_key);
    }
    /**
     * Returns the select options for the field.
     */
    protected function get_field_options(string $options): array
    {
        [$class, $method] = Str::parse_callback($options);
        return app($class)->{$method}();
    }
}