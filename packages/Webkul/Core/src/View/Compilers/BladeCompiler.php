<?php

declare (strict_types=1);
namespace Webkul\Core\View\Compilers;

use Illuminate\View\Compilers\Blade_Compiler as BaseBladeCompiler;
class Blade_Compiler extends Base_Blade_Compiler
{
    /**
     * Append the file path to the compiled string.
     *
     * @param  string  $contents
     * @return string
     */
    protected function append_file_path($contents)
    {
        $tokens = $this->get_open_and_closing_php_tokens($contents);
        if (config('view.tracer') && strpos($this->get_path(), 'tracer/style.blade.php') === false) {
            $final_path = str_replace('/Providers/..', '', str_replace(base_path(), '', $this->get_path()));
            $escaped_path = htmlspecialchars($final_path, ENT_QUOTES, 'UTF-8');
            $contents = preg_replace_callback('/^(\s*)<([a-zA-Z][a-zA-Z0-9-]*)([\s>])/m', function ($matches) use ($escaped_path) {
                return $matches[1] . '<' . $matches[2] . ' data-blade-path="' . $escaped_path . '"' . $matches[3];
            }, $contents, 1);
            if (strpos($contents, 'data-blade-path=') === false) {
                $contents = '<span data-blade-path="' . $escaped_path . '">' . $contents . '</span>';
            }
        }
        if ($tokens->is_not_empty() && $tokens->last() !== T_CLOSE_TAG) {
            $contents .= ' ?>';
        }
        return $contents . "<?php /**PATH {$this->get_path()} ENDPATH**/ ?>";
    }
}