<?php

declare (strict_types=1);
namespace Webkul\Core\Enums;

enum Currency_Position_Enum : string
{
    /**
     * Left.
     */
    case LEFT = 'left';
    /**
     * Left with space.
     */
    case LEFT_WITH_SPACE = 'left_with_space';
    /**
     * Right.
     */
    case RIGHT = 'right';
    /**
     * Right with space.
     */
    case RIGHT_WITH_SPACE = 'right_with_space';
    /**
     * Options.
     *
     * @return void
     */
    public static function options()
    {
        return [Currency_Position_Enum::LEFT->value => trans('core::app.currency-position.options.left'), Currency_Position_Enum::LEFT_WITH_SPACE->value => trans('core::app.currency-position.options.left-with-space'), Currency_Position_Enum::RIGHT->value => trans('core::app.currency-position.options.right'), Currency_Position_Enum::RIGHT_WITH_SPACE->value => trans('core::app.currency-position.options.right-with-space')];
    }
}