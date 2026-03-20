<?php

declare (strict_types=1);
namespace Webkul\Core\Traits;

use Ar_Php\I18N\Arabic;
use Barryvdh\Dom_Pdf\Facade\Pdf;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
trait Pdf_Handler
{
    /**
     * Download PDF as a streamed response.
     */
    public function download_pdf(string $html, ?string $file_name = null)
    {
        $file_name = $this->resolve_pdf_file_name($file_name);
        $html = $this->prepare_pdf_html($html);
        if ($this->is_rtl_locale()) {
            $mpdf = $this->build_mpdf($html);
            return response()->stream_download(fn() => print $mpdf->Output('', 'S'), $file_name . '.pdf');
        }
        return PDF::load_html($html)->set_paper('A4', 'portrait')->set_option('defaultFont', 'Courier')->download($file_name . '.pdf');
    }
    /**
     * Generate pdf content.
     */
    public function generate_pdf(string $html): string
    {
        $html = $this->prepare_pdf_html($html);
        if ($this->is_rtl_locale()) {
            return $this->build_mpdf($html)->Output('', 'S');
        }
        return PDF::load_html($html)->set_paper('A4', 'portrait')->set_option('defaultFont', 'Courier')->output();
    }
    /**
     * Build and configure an mPDF instance with the given HTML.
     */
    private function build_mpdf(string $html): Mpdf
    {
        $mpdf = new Mpdf(['margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0]);
        $mpdf->set_directionality('rtl');
        $mpdf->set_display_mode('fullpage');
        $mpdf->write_html($html);
        return $mpdf;
    }
    /**
     * Prepare HTML for PDF rendering: encode and adjust Arabic/Persian glyphs.
     */
    private function prepare_pdf_html(string $html): string
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        return $this->adjust_arabic_and_persian_content($html);
    }
    /**
     * Determine if the current locale uses RTL direction.
     */
    private function is_rtl_locale(): bool
    {
        return core()->get_current_locale()->direction === 'rtl';
    }
    /**
     * Resolve a PDF file name, generating a random one if not provided.
     */
    private function resolve_pdf_file_name(?string $file_name): string
    {
        return $file_name ?? Str::random(32);
    }
    /**
     * Adjust Arabic and Persian glyph rendering for PDF output.
     */
    private function adjust_arabic_and_persian_content(string $html): string
    {
        $arabic = new Arabic();
        $positions = $arabic->ar_identify($html);
        for ($i = count($positions) - 1; $i >= 0; $i -= 2) {
            $segment = substr($html, $positions[$i - 1], $positions[$i] - $positions[$i - 1]);
            $converted = $arabic->utf8Glyphs($segment);
            $html = substr_replace($html, $converted, $positions[$i - 1], $positions[$i] - $positions[$i - 1]);
        }
        return $html;
    }
}