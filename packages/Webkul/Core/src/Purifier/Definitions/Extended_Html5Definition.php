<?php

declare (strict_types=1);
namespace Webkul\Core\Purifier\Definitions;

use Html_Purifier_html_Definition;
use Stevebauman\Purify\Definitions\Definition;
use Stevebauman\Purify\Definitions\Html5Definition;
class Extended_Html5definition implements Definition
{
    /**
     * Apply rules to the HTML Purifier definition.
     */
    public static function apply(Html_Purifier_html_Definition $definition)
    {
        Html5Definition::apply($definition);
        $definition->add_element('div', 'Block', 'Flow', 'Common', ['class' => 'Class', 'id' => 'Text']);
        $definition->add_element('button', 'Inline', 'Flow', 'Common', ['class' => 'Class', 'type' => 'Enum#button,submit,reset']);
        $definition->add_attribute('img', 'data-src', 'URI');
        $definition->add_attribute('img', 'loading', 'Enum#lazy,eager');
        $definition->add_attribute('span', 'data-custom', 'Text');
        $definition->add_attribute('div', 'data-custom', 'Text');
    }
}