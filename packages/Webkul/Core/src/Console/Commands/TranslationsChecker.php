<?php

declare (strict_types=1);
namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
class Translations_Checker extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bagisto:translations:check
                            {--locale= : Check only a specific locale against EN.}
                            {--package= : Check only a specific package.}
                            {--details : Show detailed error information.}';
    /**
     * The console command description.
     */
    protected $description = 'Check translation files consistency across all packages (EN is canonical).';
    /**
     * Base locale for comparison.
     */
    protected const BASE_LOCALE = 'en';
    /**
     * Maximum items to display in detailed output.
     */
    protected const MAX_DISPLAY_ITEMS = 10;
    /**
     * Package directories relative to base path.
     */
    protected const PACKAGE_DIRECTORIES = ['packages/Webkul'];
    /**
     * Root lang folder relative to base path.
     */
    protected const ROOT_LANG_DIRECTORY = 'lang';
    /**
     * Possible lang folder paths within a package (in priority order).
     */
    protected const PACKAGE_LANG_PATHS = ['/src/Resources/lang', '/resources/lang'];
    /**
     * Supported locales that must exist in all packages.
     */
    protected const SUPPORTED_LOCALES = ['ar', 'bn', 'ca', 'de', 'en', 'es', 'fa', 'fr', 'he', 'hi_IN', 'id', 'it', 'ja', 'nl', 'pl', 'pt_BR', 'ru', 'sin', 'tr', 'uk', 'zh_CN'];
    /**
     * Track if errors occurred.
     */
    protected bool $has_error = false;
    /**
     * Collection of errors for detailed output.
     */
    protected Collection $errors;
    /**
     * Results for table display.
     */
    protected Collection $results;
    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->errors = collect();
        $this->results = collect();
    }
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $target_locale = $this->option('locale');
        $target_package = $this->option('package');
        $show_details = $this->option('details');
        $this->display_header($target_locale, $target_package);
        // Check root lang folder first (unless a specific package is requested)
        if (!$target_package) {
            $this->process_lang_folder('Root', base_path(self::ROOT_LANG_DIRECTORY), $target_locale);
        }
        // Check package lang folders
        $this->process_packages($target_package, $target_locale);
        $this->display_results($show_details);
        return $this->has_error ? self::FAILURE : self::SUCCESS;
    }
    /**
     * Display the command header.
     */
    protected function display_header(?string $target_locale, ?string $target_package): void
    {
        $this->new_line();
        $this->info('🔍 Bagisto Translations Checker');
        $this->line('   Canonical Locale: <fg=cyan>' . Str::upper(self::BASE_LOCALE) . '</>');
        if ($target_locale) {
            $this->line("   Filter Locale: <fg=yellow>{$target_locale}</>");
        }
        if ($target_package) {
            $this->line("   Filter Package: <fg=yellow>{$target_package}</>");
        }
        $this->new_line();
    }
    /**
     * Process all packages for translations checking.
     */
    protected function process_packages(?string $target_package, ?string $target_locale): void
    {
        collect(self::PACKAGE_DIRECTORIES)->map(fn($dir) => base_path($dir))->filter(fn($path) => File::is_directory($path))->flat_map(fn($base_path) => File::directories($base_path))->when($target_package, fn($collection) => $collection->filter(fn($path) => Str::lower(basename($path)) === Str::lower($target_package)))->each(function ($package_dir) use ($target_locale) {
            $package_name = basename($package_dir);
            $lang_path = collect(self::PACKAGE_LANG_PATHS)->map(fn($path) => $package_dir . $path)->first(fn($path) => File::is_directory($path));
            if ($lang_path) {
                $this->process_lang_folder($package_name, $lang_path, $target_locale);
            }
        });
    }
    /**
     * Process a lang folder and collect results.
     */
    protected function process_lang_folder(string $name, string $lang_root, ?string $target_locale): void
    {
        $en_path = $lang_root . '/' . self::BASE_LOCALE;
        if (!File::is_directory($en_path)) {
            return;
        }
        $en_files = $this->get_php_files($en_path);
        if ($en_files->is_empty()) {
            return;
        }
        $en_files_rel = $en_files->map(fn($f) => Str::after($f, $en_path . '/'));
        // Get existing locales in this lang folder
        $existing_locales = collect(File::directories($lang_root))->map(fn($d) => basename($d))->sort()->values();
        // Check for missing supported locales
        $supported_locales = collect(self::SUPPORTED_LOCALES);
        $missing_locales = $supported_locales->reject(fn($locale) => $existing_locales->contains($locale))->when($target_locale, fn($collection) => $collection->filter(fn($l) => Str::lower($l) === Str::lower($target_locale)))->sort()->values();
        // Report missing locales as failures
        $missing_locales->each(function ($locale) use ($name) {
            $this->results->push(['package' => $name, 'locale' => $locale, 'status' => 'fail', 'issues' => 'Locale folder missing']);
            $this->errors->push(['package' => $name, 'locale' => $locale, 'type' => 'missing_locale', 'message' => "Locale folder '{$locale}' does not exist"]);
            $this->has_error = true;
        });
        // Get other locales to check (excluding base locale and filtered by target)
        $locales = $existing_locales->reject(fn($d) => $d === self::BASE_LOCALE)->when($target_locale, fn($collection) => $collection->filter(fn($l) => Str::lower($l) === Str::lower($target_locale)))->sort()->values();
        if ($locales->is_empty() && $missing_locales->is_empty()) {
            return;
        }
        $locales->each(function ($locale) use ($name, $lang_root, $en_path, $en_files_rel) {
            $result = $this->check_locale($name, $lang_root, $en_path, $en_files_rel, $locale);
            $this->results->push($result);
            if ($result['status'] !== 'pass') {
                $this->has_error = true;
            }
        });
    }
    /**
     * Check a specific locale against EN and return results.
     */
    protected function check_locale(string $package_name, string $lang_root, string $en_path, Collection $en_files_rel, string $locale): array
    {
        $locale_path = "{$lang_root}/{$locale}";
        $issues = [];
        // Check for missing files
        $missing_files = $en_files_rel->filter(fn($rel_file) => !File::exists("{$locale_path}/{$rel_file}"))->values();
        if ($missing_files->is_not_empty()) {
            $issues['missing_files'] = $missing_files->all();
            $this->errors->push(['package' => $package_name, 'locale' => $locale, 'type' => 'missing_files', 'files' => $missing_files->all()]);
        }
        // Check for extra files
        $locale_files = $this->get_php_files($locale_path);
        $locale_files_rel = $locale_files->map(fn($f) => Str::after($f, $locale_path . '/'));
        $extra_files = $locale_files_rel->diff($en_files_rel)->values();
        if ($extra_files->is_not_empty()) {
            $issues['extra_files'] = $extra_files->all();
            $this->errors->push(['package' => $package_name, 'locale' => $locale, 'type' => 'extra_files', 'files' => $extra_files->all()]);
        }
        // Check keys and structure
        $missing_keys = [];
        $extra_keys = [];
        $structure_issues = [];
        $en_files_rel->each(function ($rel_file) use ($en_path, $locale_path, &$missing_keys, &$extra_keys, &$structure_issues, &$issues) {
            $en_file = "{$en_path}/{$rel_file}";
            $locale_file = "{$locale_path}/{$rel_file}";
            if (!File::exists($locale_file)) {
                return;
            }
            try {
                $loc_array = $this->parse_translation_file($locale_file);
                $en_keys_with_lines = $this->flatten_array_with_lines($en_file);
                $loc_keys = collect($this->flatten_array($loc_array))->keys();
                $en_keys = collect($en_keys_with_lines)->keys();
                $file_missing = $en_keys->diff($loc_keys);
                $file_extra = $loc_keys->diff($en_keys);
                if ($file_missing->is_not_empty()) {
                    $missing_with_lines = $file_missing->map_with_keys(fn($key) => [$key => $en_keys_with_lines[$key] ?? null])->all();
                    $missing_keys[$rel_file] = $missing_with_lines;
                }
                if ($file_extra->is_not_empty()) {
                    $loc_keys_with_lines = $this->flatten_array_with_lines($locale_file);
                    $extra_with_lines = $file_extra->map_with_keys(fn($key) => [$key => $loc_keys_with_lines[$key] ?? null])->all();
                    $extra_keys[$rel_file] = $extra_with_lines;
                }
                // Always check structure (line-by-line comparison)
                $structure_mismatches = $this->get_structure_mismatches($en_file, $locale_file);
                if (!empty($structure_mismatches)) {
                    $structure_issues[$rel_file] = $structure_mismatches;
                }
            } catch (Throwable) {
                $issues['parse_errors'][] = $rel_file;
            }
        });
        if (!empty($missing_keys)) {
            $issues['missing_keys'] = $missing_keys;
            $this->errors->push(['package' => $package_name, 'locale' => $locale, 'type' => 'missing_keys', 'data' => $missing_keys]);
        }
        if (!empty($extra_keys)) {
            $issues['extra_keys'] = $extra_keys;
            $this->errors->push(['package' => $package_name, 'locale' => $locale, 'type' => 'extra_keys', 'data' => $extra_keys]);
        }
        if (!empty($structure_issues)) {
            $issues['structure_issues'] = $structure_issues;
            $this->errors->push(['package' => $package_name, 'locale' => $locale, 'type' => 'structure_issues', 'data' => $structure_issues]);
        }
        return ['package' => $package_name, 'locale' => $locale, 'status' => empty($issues) ? 'pass' : 'fail', 'issues' => $this->build_issue_summary($issues)];
    }
    /**
     * Build a summary string for issues.
     */
    protected function build_issue_summary(array $issues): string
    {
        $summary = collect();
        if (!empty($issues['missing_files'])) {
            $summary->push(count($issues['missing_files']) . ' missing file(s)');
        }
        if (!empty($issues['extra_files'])) {
            $summary->push(count($issues['extra_files']) . ' extra file(s)');
        }
        if (!empty($issues['missing_keys'])) {
            $count = collect($issues['missing_keys'])->map(fn($keys) => count($keys))->sum();
            $summary->push("{$count} missing key(s)");
        }
        if (!empty($issues['extra_keys'])) {
            $count = collect($issues['extra_keys'])->map(fn($keys) => count($keys))->sum();
            $summary->push("{$count} extra key(s)");
        }
        if (!empty($issues['structure_issues'])) {
            $file_count = count($issues['structure_issues']);
            $summary->push("{$file_count} file(s) with structure issue(s)");
        }
        if (!empty($issues['parse_errors'])) {
            $summary->push(count($issues['parse_errors']) . ' parse error(s)');
        }
        return $summary->implode(', ') ?: '-';
    }
    /**
     * Display results in a clean table format.
     */
    protected function display_results(bool $show_details): void
    {
        if ($this->results->is_empty()) {
            $this->warn('No translations found to check.');
            return;
        }
        // Group results by package
        $grouped = $this->results->group_by('package');
        $pass_count = 0;
        $fail_count = 0;
        $grouped->each(function ($locale_results, $package) use (&$pass_count, &$fail_count) {
            $this->info("📦 {$package}");
            $table_rows = $locale_results->map(function ($result) use (&$pass_count, &$fail_count) {
                $is_passing = $result['status'] === 'pass';
                $status_icon = $is_passing ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $issue_text = $is_passing ? '<fg=green>OK</>' : "<fg=red>{$result['issues']}</>";
                $is_passing ? $pass_count++ : $fail_count++;
                return [$result['locale'], $status_icon, $issue_text];
            })->all();
            $this->table(['Locale', 'Status', 'Issues'], $table_rows);
            $this->new_line();
        });
        // Summary
        $this->display_summary($pass_count, $fail_count, $show_details);
    }
    /**
     * Display the summary section.
     */
    protected function display_summary(int $pass_count, int $fail_count, bool $show_details): void
    {
        $this->line(str_repeat('─', 60));
        $total = $pass_count + $fail_count;
        $this->line("📊 <fg=white;options=bold>Summary:</> {$total} locale(s) checked");
        $this->line("   <fg=green>✓ Passed:</> {$pass_count}");
        $this->line("   <fg=red>✗ Failed:</> {$fail_count}");
        $this->line(str_repeat('─', 60));
        if ($this->has_error) {
            $this->new_line();
            $this->error('Translations check failed!');
            if ($show_details) {
                $this->display_detailed_errors();
            } else {
                $this->new_line();
                $this->line('<fg=yellow>💡 Use --details flag to see specific missing/extra keys.</>');
            }
        } else {
            $this->new_line();
            $this->info('✅ All translations are synchronized with EN!');
        }
        $this->new_line();
    }
    /**
     * Display detailed error information.
     */
    protected function display_detailed_errors(): void
    {
        $this->new_line();
        $this->line('<fg=yellow;options=bold>📋 Detailed Issues:</>');
        $this->new_line();
        // Group errors by package and locale
        $grouped = $this->errors->group_by(fn($error) => "{$error['package']}:{$error['locale']}");
        $grouped->each(function ($errors, $key) {
            [$package, $locale] = explode(':', $key);
            $this->line("<fg=cyan>[{$package}]</> <fg=yellow>{$locale}</>");
            $errors->each(fn($error) => $this->display_error($error));
            $this->new_line();
        });
    }
    /**
     * Display a single error based on its type.
     */
    protected function display_error(array $error): void
    {
        match ($error['type']) {
            'missing_locale' => $this->display_missing_locale($error['message']),
            'missing_files' => $this->display_missing_files($error['files']),
            'extra_files' => $this->display_extra_files($error['files']),
            'missing_keys' => $this->display_missing_keys($error['data']),
            'extra_keys' => $this->display_extra_keys($error['data']),
            'structure_issues' => $this->display_structure_issues($error['data']),
            default => null,
        };
    }
    /**
     * Display missing locale error.
     */
    protected function display_missing_locale(string $message): void
    {
        $this->line("  <fg=red>⚠ {$message}</>");
        $this->new_line();
    }
    /**
     * Display missing files error.
     */
    protected function display_missing_files(array $files): void
    {
        $this->line('  <fg=red>Missing files:</>');
        collect($files)->each(fn($file) => $this->line("    - {$file}"));
        $this->new_line();
    }
    /**
     * Display extra files error.
     */
    protected function display_extra_files(array $files): void
    {
        $this->line('  <fg=magenta>Extra files (not in EN):</>');
        collect($files)->each(fn($file) => $this->line("    - {$file}"));
        $this->new_line();
    }
    /**
     * Display missing keys error.
     */
    protected function display_missing_keys(array $data): void
    {
        $this->line('  <fg=red>Missing keys:</>');
        collect($data)->each(function ($keys, $file) {
            $this->line("    <fg=white;options=bold>{$file}:</>");
            $this->display_keys_with_lines($keys, 'Missing key');
        });
        $this->new_line();
    }
    /**
     * Display extra keys error.
     */
    protected function display_extra_keys(array $data): void
    {
        $this->line('  <fg=magenta>Extra keys (not in EN):</>');
        collect($data)->each(function ($keys, $file) {
            $this->line("    <fg=white;options=bold>{$file}:</>");
            $this->display_keys_with_lines($keys, 'Extra key');
        });
        $this->new_line();
    }
    /**
     * Display keys with their line numbers.
     */
    protected function display_keys_with_lines(array $keys, string $label): void
    {
        $keys_list = collect($keys);
        $displayed = 0;
        $keys_list->each(function ($line, $key) use ($label, &$displayed, $keys_list) {
            if ($displayed >= self::MAX_DISPLAY_ITEMS) {
                if ($displayed === self::MAX_DISPLAY_ITEMS) {
                    $remaining = $keys_list->count() - self::MAX_DISPLAY_ITEMS;
                    $this->line("      <fg=gray>... and {$remaining} more</>");
                }
                return false;
            }
            if (is_int($key)) {
                $this->line("      <fg=yellow>⚠</> {$label}: {$line}");
            } else {
                $line_info = $line ? "<fg=cyan>Line {$line}:</>" : '<fg=cyan>Line ?:</>';
                $this->line("      {$line_info} {$label} '{$key}'");
            }
            $displayed++;
        });
    }
    /**
     * Display structure issues error.
     */
    protected function display_structure_issues(array $data): void
    {
        $this->line('  <fg=yellow>Structure differs from EN:</>');
        collect($data)->each(function ($mismatches, $file) {
            $this->line("    <fg=white;options=bold>{$file}:</>");
            collect($mismatches)->each(function ($mismatch) {
                if (isset($mismatch['line'])) {
                    $this->line("      <fg=cyan>Line {$mismatch['line']}:</> {$mismatch['message']}");
                    collect(['en_structure', 'locale_structure', 'en_content', 'locale_content'])->each(function ($field) use ($mismatch) {
                        if (isset($mismatch[$field])) {
                            $label = Str::starts_with($field, 'en_') ? 'EN' : 'Locale';
                            $color = Str::starts_with($field, 'en_') ? 'green' : 'red';
                            $this->line("        <fg={$color}>{$label}:</> {$mismatch[$field]}");
                        }
                    });
                } else {
                    $this->line("      <fg=yellow>⚠</> {$mismatch['message']}");
                    if ($mismatch['type'] === 'line_count') {
                        $this->new_line();
                    }
                }
            });
        });
        $this->new_line();
    }
    /**
     * Check if locale file has structure mismatch with EN file.
     *
     * Compares line-by-line structure (keys position, indentation, blank lines).
     */
    protected function get_structure_mismatches(string $en_file, string $locale_file): array
    {
        $en_lines = collect(File::lines($en_file)->to_array());
        $loc_lines = collect(File::lines($locale_file)->to_array());
        $mismatches = [];
        $en_line_count = $en_lines->count();
        $loc_line_count = $loc_lines->count();
        // Different number of lines means structure mismatch
        if ($en_line_count !== $loc_line_count) {
            $mismatches[] = ['type' => 'line_count', 'message' => "Line count differs: EN has {$en_line_count} lines, locale has {$loc_line_count} lines"];
        }
        // Check line-by-line for structure differences
        $max_lines = max($en_line_count, $loc_line_count);
        $detailed_mismatches = collect();
        for ($line_num = 0; $line_num < $max_lines; $line_num++) {
            $en_line = $en_lines->get($line_num);
            $loc_line = $loc_lines->get($line_num);
            $display_line_num = $line_num + 1;
            if ($en_line === null) {
                $detailed_mismatches->push(['line' => $display_line_num, 'type' => 'extra_line', 'message' => 'Extra line in locale', 'locale_content' => $this->truncate_line($loc_line)]);
                continue;
            }
            if ($loc_line === null) {
                $detailed_mismatches->push(['line' => $display_line_num, 'type' => 'missing_line', 'message' => 'Missing line in locale', 'en_content' => $this->truncate_line($en_line)]);
                continue;
            }
            $en_structure = $this->extract_line_structure($en_line);
            $loc_structure = $this->extract_line_structure($loc_line);
            if ($en_structure !== $loc_structure) {
                $detailed_mismatches->push(['line' => $display_line_num, 'type' => 'structure_diff', 'message' => $this->describe_structure_difference($en_structure, $loc_structure, $en_line, $loc_line), 'en_structure' => $this->truncate_line($en_structure), 'locale_structure' => $this->truncate_line($loc_structure)]);
            }
        }
        if ($detailed_mismatches->is_not_empty()) {
            $mismatches = array_merge($mismatches, $detailed_mismatches->take(self::MAX_DISPLAY_ITEMS)->all());
            if ($detailed_mismatches->count() > self::MAX_DISPLAY_ITEMS) {
                $remaining = $detailed_mismatches->count() - self::MAX_DISPLAY_ITEMS;
                $mismatches[] = ['type' => 'truncated', 'message' => "... and {$remaining} more structure differences"];
            }
        }
        return $mismatches;
    }
    /**
     * Describe what kind of structure difference exists between two lines.
     */
    protected function describe_structure_difference(string $en_struct, string $loc_struct, string $en_line, string $loc_line): string
    {
        // Check for key difference
        $en_key = $this->extract_key($en_line);
        $loc_key = $this->extract_key($loc_line);
        if ($en_key !== null && $loc_key !== null && $en_key !== $loc_key) {
            return "Key mismatch: EN has '{$en_key}', locale has '{$loc_key}'";
        }
        // Check for indentation difference
        $en_indent = Str::length($en_line) - Str::length(ltrim($en_line));
        $loc_indent = Str::length($loc_line) - Str::length(ltrim($loc_line));
        if ($en_indent !== $loc_indent) {
            return "Indentation mismatch: EN has {$en_indent} spaces, locale has {$loc_indent} spaces";
        }
        // Check for different line types
        $en_trimmed = trim($en_line);
        $loc_trimmed = trim($loc_line);
        if ($en_trimmed === '' && $loc_trimmed !== '') {
            return 'EN has blank line, locale has content';
        }
        if ($en_trimmed !== '' && $loc_trimmed === '') {
            return 'EN has content, locale has blank line';
        }
        return 'Structure pattern differs';
    }
    /**
     * Extract key from a translation line.
     */
    protected function extract_key(string $line): ?string
    {
        if (preg_match('/[\'"]([^\'"]+)[\'"]\s*=>/', $line, $matches)) {
            return $matches[1];
        }
        return null;
    }
    /**
     * Truncate a line for display.
     */
    protected function truncate_line(string $line, int $max_len = 60): string
    {
        $line = trim($line);
        return Str::length($line) > $max_len ? Str::substr($line, 0, $max_len - 3) . '...' : $line;
    }
    /**
     * Extract the structural part of a line (indentation, key, array markers).
     */
    protected function extract_line_structure(string $line): string
    {
        $trimmed = trim($line);
        // Preserve blank lines and comments as-is
        if ($trimmed === '' || Str::starts_with($trimmed, ['//', '/*', '*'])) {
            return $line;
        }
        // For lines with => (key-value pairs), extract just the key part and indentation
        if (preg_match('/^(\s*)([\'"][^\'"]+[\'"])\s*=>\s*/', $line, $matches)) {
            return $matches[1] . $matches[2] . ' =>';
        }
        // For array opening/closing brackets and other structural elements, return as-is
        if (preg_match('/^(\s*)([\[\],\];]+)/', $line, $matches)) {
            return $line;
        }
        // For 'return [' or similar
        if (Str::contains($line, 'return')) {
            return $line;
        }
        // For <?php tag
        if (Str::contains($line, '<?php')) {
            return $line;
        }
        return $line;
    }
    /**
     * Flatten nested arrays into dot notation keys.
     */
    protected function flatten_array(array $array, string $prefix = ''): array
    {
        return collect($array)->flat_map(function ($value, $key) use ($prefix) {
            $full_key = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            return is_array($value) ? $this->flatten_array($value, $full_key) : [$full_key => true];
        })->all();
    }
    /**
     * Flatten array keys with their line numbers from the source file.
     */
    protected function flatten_array_with_lines(string $file): array
    {
        $lines = collect(explode("\n", File::get($file)));
        $result = [];
        $key_stack = [];
        $lines->each(function ($line, $line_num) use (&$result, &$key_stack) {
            $display_line = $line_num + 1;
            // Match array key definitions like 'key' => or "key" =>
            if (preg_match('/^(\s*)[\'"]([^\'"]+)[\'"]\s*=>/', $line, $matches)) {
                $indent = Str::length($matches[1]);
                $key = $matches[2];
                $indent_level = (int) ($indent / 4);
                // Adjust key stack based on indent level
                while (count($key_stack) > $indent_level) {
                    array_pop($key_stack);
                }
                $key_stack[$indent_level] = $key;
                // Check if this is a leaf node (has a value, not an array)
                if (!preg_match('/=>\s*\[/', $line)) {
                    $full_key = implode('.', array_slice($key_stack, 0, $indent_level + 1));
                    $result[$full_key] = $display_line;
                }
            }
        });
        return $result;
    }
    /**
     * Parse a PHP translation file and extract the array.
     */
    protected function parse_translation_file(string $file): array
    {
        ob_start();
        try {
            $result = include $file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Failed to include file: {$file} - " . $e->get_message());
        }
        ob_end_clean();
        if (!is_array($result)) {
            throw new RuntimeException("Translation file does not return an array: {$file}");
        }
        return $result;
    }
    /**
     * Get all PHP files in a directory recursively.
     */
    protected function get_php_files(string $dir): Collection
    {
        if (!File::is_directory($dir)) {
            return collect();
        }
        return collect(File::all_files($dir))->filter(fn($file) => $file->get_extension() === 'php')->map(fn($file) => $file->get_pathname())->sort()->values();
    }
}