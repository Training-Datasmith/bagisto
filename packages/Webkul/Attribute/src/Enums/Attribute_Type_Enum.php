<?php

declare (strict_types=1);
namespace Webkul\Attribute\Enums;

enum Attribute_Type_Enum : string
{
    /**
     * Text type attribute.
     */
    case TEXT = 'text';
    /**
     * Textarea type attribute.
     */
    case TEXTAREA = 'textarea';
    /**
     * Price type attribute.
     */
    case PRICE = 'price';
    /**
     * Boolean type attribute.
     */
    case BOOLEAN = 'boolean';
    /**
     * Checkbox type attribute.
     */
    case CHECKBOX = 'checkbox';
    /**
     * Select type attribute.
     */
    case SELECT = 'select';
    /**
     * Multiselect type attribute.
     */
    case MULTISELECT = 'multiselect';
    /**
     * Date type attribute.
     */
    case DATE = 'date';
    /**
     * Datetime type attribute.
     */
    case DATETIME = 'datetime';
    /**
     * Image type attribute.
     */
    case IMAGE = 'image';
    /**
     * File type attribute.
     */
    case FILE = 'file';
    /**
     * Get all attribute type values as an array.
     */
    public static function get_values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }
    /**
     * Get boolean options.
     */
    public static function get_boolean_options(): array
    {
        return [['id' => 0, 'name' => trans('attribute::app.boolean.options.no')], ['id' => 1, 'name' => trans('attribute::app.boolean.options.yes')]];
    }
}