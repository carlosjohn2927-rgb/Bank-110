<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pdf — minimal dependency-free PDF writer (core fonts only).
 *
 * Supports multiple pages, Helvetica family, text, cells with borders,
 * word-wrapped multi-cell text, automatic page breaks, and a simple
 * running header/footer. Renders valid PDF 1.4 to a string or file.
 *
 * This is intentionally compact — enough for account statements, not a
 * general-purpose typesetting engine. No images or Unicode beyond WinAnsi.
 */
class Pdf {

    const VERSION = '1.4';

    protected $w = 595.28;   // A4 portrait width in points
    protected $h = 841.89;   // A4 portrait height in points
    protected $marginL = 40, $marginR = 40, $marginT = 42, $marginB = 42;
    protected $autoBreak = TRUE;

    protected $pages = array();
    protected $state = array();
    protected $buffer = '';

    protected $font = 'Helvetica';
    protected $size = 11;
    protected $style = '';
    protected $color = array(0, 0, 0);
    protected $x = 0, $y = 0;
    protected $pageNumber = 0;
    protected $headerHeight = 22;
    protected $footerHeight = 18;

    protected $title = 'Statement';
    protected $headerLine = '';
    protected $footerLine = '';

    public function __construct($title = 'NorthWest statement')
    {
        $this->title = $title;
    }

    public function set_margins($left, $top, $right = NULL, $bottom = NULL)
    {
        $this->marginL = (float)$left;
        $this->marginT = (float)$top;
        if ($right !== NULL) $this->marginR = (float)$right;
        if ($bottom !== NULL) $this->marginB = (float)($bottom !== NULL ? $bottom : $top);
    }

    public function set_header($text) { $this->headerLine = $text; }
    public function set_footer($text) { $this->footerLine = $text; }
    public function set_title($t) { $this->title = $t; }

    public function add_page()
    {
        if ($this->buffer !== '') {
            $this->pages[] = array('id' => count($this->pages) + 3, 'body' => $this->buffer);
        }
        $this->buffer = '';
        $this->pageNumber++;
        $this->x = $this->marginL;
        $this->y = $this->marginT + $this->headerHeight;
        if ($this->headerLine !== '' || $this->pageNumber > 1) $this->render_header();
        $this->y += 4;
    }

    public function set_font($family = 'Helvetica', $style = '', $size = 11)
    {
        $family = in_array($family, array('Helvetica','Courier','Times'), TRUE) ? $family : 'Helvetica';
        $this->font = $family;
        $this->style = in_array($style, array('B','I','BI','U',''), TRUE) ? $style : '';
        $this->size = max(6, (float)$size);
    }

    public function set_text_color($r, $g, $b)
    {
        $this->color = array(max(0,min(255,(int)$r)), max(0,min(255,(int)$g)), max(0,min(255,(int)$b)));
    }

    public function ln($h = 6) { $this->x = $this->marginL; $this->y += $h; }

    public function get_x() { return $this->x; }
    public function get_y() { return $this->y; }
    public function set_y($y) { $this->y = (float)$y; }
    public function page_width() { return $this->w - $this->marginL - $this->marginR; }

    /**
     * A single-line cell with optional border and alignment.
     * $border: 0 none, 1 full box, or string of L/R/T/B
     */
    public function cell($w, $h, $text = '', $border = 0, $ln = 0, $align = 'L', $fill = FALSE)
    {
        $txt = $this->escape((string)$text);
        $this->state[] = array('op' => 'cell', 'x' => $this->x, 'y' => $this->y, 'w' => $w, 'h' => $h,
            'text' => $txt, 'border' => $border, 'align' => $align, 'fill' => $fill,
            'font' => $this->font, 'style' => $this->style, 'size' => $this->size, 'color' => $this->color);
        if ($ln) { $this->x = $this->marginL; $this->y += $h; }
        else { $this->x += $w; }
        $this->maybe_page_break($h);
    }

    /** Word-wrapped text block at current position with given width; returns height used. */
    public function multi_cell($w, $text, $border = 0, $align = 'L', $lineH = 5.2)
    {
        $text = (string)$text;
        $sizeH = $this->size * 1.25;
        $words = preg_split('/\s+/', trim($text));
        $line = ''; $lines = array();
        foreach ($words as $word) {
            $test = $line === '' ? $word : $line.' '.$word;
            if ($this->text_width($test) > $w && $line !== '') {
                $lines[] = $line; $line = $word;
            } else {
                $line = $test;
            }
        }
        if ($line !== '') $lines[] = $line;
        if (empty($lines)) $lines[] = '';

        foreach ($lines as $i => $l) {
            $this->cell($w, $sizeH, $l, $border, 1, $align);
        }
        return count($lines) * $sizeH;
    }

    public function text_width($text)
    {
        // Approximate AFM widths for Helvetica; Courier fixed; Times similar.
        static $widths = NULL;
        if ($widths === NULL) {
            // Widths as fractions of 1000 units/em (Helvetica core widths)
            $widths = array(
                ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,'\''=>191,'('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,
                '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,'8'=>556,'9'=>556,
                ':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,'@'=>1015,'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,'P'=>667,'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,'Y'=>667,'Z'=>611,
                '['=>278,'\\'=>278,']'=>278,'^'=>469,'_'=>556,'`'=>333,'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,'h'=>556,'i'=>278,'j'=>278,'k'=>556,'l'=>278,'m'=>833,'n'=>556,'o'=>556,'p'=>556,'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,'y'=>500,'z'=>500,
                '{'=>334,'|'=>260,'}'=>334,'~'=>584
            );
        }
        if ($this->font === 'Courier') {
            return strlen($text) * 0.600 * $this->size;
        }
        if ($this->font === 'Times') {
            // Times is slightly narrower; reuse helvetica with 0.92 factor as approximation.
            $factor = 0.92;
        } else {
            $factor = 1.0;
        }
        $w = 0;
        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $ch = $text[$i];
            $cw = isset($widths[$ch]) ? $widths[$ch] : 556;
            $w += $cw;
        }
        return ($w / 1000) * $this->size * $factor;
    }

    /** Draw a 1-pt horizontal rule across the content width. */
    public function hr($color = array(210, 218, 230))
    {
        $this->state[] = array('op' => 'hr', 'x' => $this->x, 'y' => $this->y, 'w' => $this->page_width(), 'color' => $color);
        $this->y += 4;
    }

    /** Accessors for layout code. */
    public function marginL() { return $this->marginL; }
    public function marginR() { return $this->marginR; }
    public function marginT() { return $this->marginT; }
    public function marginB() { return $this->marginB; }
    public function set_x($x) { $this->x = (float)$x; }

    /** Draw a filled rectangle (for table row backgrounds, summary cards). */
    public function rect($x, $y, $w, $h, $fill = array(240,240,240))
    {
        $this->state[] = array('op' => 'rectbox', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'fill' => $fill);
    }

    /** Draw text at an absolute position (does not advance the cursor). */
    public function text_at($x, $y, $text, $align = 'L')
    {
        $this->state[] = array('op' => 'text', 'x' => $x, 'y' => $y,
            'text' => $this->escape((string)$text), 'font' => $this->font, 'style' => $this->style,
            'size' => $this->size, 'color' => $this->color, 'align' => $align);
    }

    protected function maybe_page_break($h)
    {
        if (!$this->autoBreak) return;
        if ($this->y + $h > $this->h - $this->marginB) {
            $this->add_page();
        }
    }

    protected function render_header()
    {
        $y = $this->marginT;
        $this->state[] = array('op' => 'rect', 'x' => 0, 'y' => 0, 'w' => $this->w, 'h' => 6, 'fill' => array(20, 104, 229));
        $this->state[] = array('op' => 'text', 'x' => $this->marginL, 'y' => $y + 10, 'text' => 'NorthWest',
            'font' => 'Helvetica', 'style' => 'B', 'size' => 16, 'color' => array(20,104,229));
        $this->state[] = array('op' => 'text', 'x' => $this->marginL, 'y' => $y + 26, 'text' => 'Financial Ltd.',
            'font' => 'Helvetica', 'style' => '', 'size' => 9, 'color' => array(110,124,145));
        $this->state[] = array('op' => 'text', 'x' => $this->w - $this->marginR, 'y' => $y + 14,
            'text' => $this->escape($this->headerLine), 'font' => 'Helvetica', 'style' => 'B', 'size' => 11,
            'color' => array(26, 45, 68), 'align' => 'R');
        if ($this->pageNumber > 1) {
            $this->state[] = array('op' => 'text', 'x' => $this->w - $this->marginR, 'y' => $y + 28,
                'text' => 'Page '.$this->pageNumber, 'font' => 'Helvetica', 'style' => '', 'size' => 9,
                'color' => array(110,124,145), 'align' => 'R');
        }
        $this->state[] = array('op' => 'line', 'x' => $this->marginL, 'y' => $y + 36, 'x2' => $this->w - $this->marginR, 'y2' => $y + 36, 'color' => array(220,226,236));
    }

    protected function render_footer()
    {
        $y = $this->h - $this->marginB + 10;
        $this->state[] = array('op' => 'line', 'x' => $this->marginL, 'y' => $y - 4, 'x2' => $this->w - $this->marginR, 'y2' => $y - 4, 'color' => array(220,226,236));
        $this->state[] = array('op' => 'text', 'x' => $this->marginL, 'y' => $y, 'text' => $this->escape($this->footerLine),
            'font' => 'Helvetica', 'style' => '', 'size' => 8, 'color' => array(130,142,162));
        $this->state[] = array('op' => 'text', 'x' => $this->w - $this->marginR, 'y' => $y, 'text' => 'Page '.$this->pageNumber,
            'font' => 'Helvetica', 'style' => '', 'size' => 8, 'color' => array(130,142,162), 'align' => 'R');
    }

    protected function escape($s)
    {
        // Encode to WinAnsi; strip anything that can't be represented safely.
        $s = str_replace(array('\\', '(', ')', "\r", "\n"), array('\\\\', '\(', '\)', ' ', ' '), (string)$s);
        return utf8_decode($s);
    }

    public function output($filename = 'document.pdf', $dest = 'D', $filepath = NULL)
    {
        // flush last page
        if ($this->buffer !== '' || $this->pageNumber === 0) {
            if ($this->pageNumber === 0) $this->add_page();
            $this->pages[] = array('id' => count($this->pages) + 3, 'body' => $this->build_stream());
        }

        // Assemble PDF — assign sequential object ids deterministically.
        $pdf = "%PDF-".self::VERSION."\n%\xE2\xE3\xCF\xD3\n";
        $offsets = array();
        $nextId = 1;

        // Object 1: catalog
        $catalogId = $nextId++;
        // Object 2: pages (placeholder, filled after we know kids)
        $pagesId = $nextId++;

        // Page + content objects per page
        $pageIds = array(); $contentIds = array();
        foreach ($this->pages as $p) {
            $pageIds[] = $nextId; $contentIds[] = $nextId + 1;
            $nextId += 2;
        }
        // Fonts
        $fontIds = array('F1' => $nextId++, 'F2' => $nextId++, 'F3' => $nextId++);
        $infoId = $nextId++;

        // Catalog
        $offsets[$catalogId] = strlen($pdf);
        $pdf .= $catalogId." 0 obj\n<< /Type /Catalog /Pages ".$pagesId." 0 R >>\nendobj\n";

        // Pages
        $kids = '';
        foreach ($pageIds as $pid) $kids .= $pid.' 0 R ';
        $offsets[$pagesId] = strlen($pdf);
        $pdf .= $pagesId." 0 obj\n<< /Type /Pages /Kids [".trim($kids)."] /Count ".count($this->pages)." >>\nendobj\n";

        foreach ($this->pages as $i => $p) {
            $pid = $pageIds[$i]; $cid = $contentIds[$i];
            $offsets[$pid] = strlen($pdf);
            $pdf .= $pid." 0 obj\n<< /Type /Page /Parent ".$pagesId." 0 R /MediaBox [0 0 ".$this->w." ".$this->h."] /Contents ".$cid." 0 R /Resources << /Font << /F1 ".$fontIds['F1']." 0 R /F2 ".$fontIds['F2']." 0 R /F3 ".$fontIds['F3']." 0 R >> >> >>\nendobj\n";
            $stream = $p['body'];
            $offsets[$cid] = strlen($pdf);
            $pdf .= $cid." 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream\nendobj\n";
        }

        // Fonts
        $fontDefs = array(
            $fontIds['F1'] => 'Helvetica',
            $fontIds['F2'] => 'Helvetica-Bold',
            $fontIds['F3'] => 'Helvetica-Oblique',
        );
        foreach ($fontDefs as $id => $name) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /".$name." /Encoding /WinAnsiEncoding >>\nendobj\n";
        }

        // Info
        $offsets[$infoId] = strlen($pdf);
        $pdf .= $infoId." 0 obj\n<< /Title (".$this->escape($this->title).") /Producer (NorthWest in-app) /CreationDate (D:".date('YmdHis').") >>\nendobj\n";

        // xref
        $xrefOffset = strlen($pdf);
        $maxId = $nextId;
        $pdf .= "xref\n0 ".$maxId."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $maxId; $i++) {
            $off = isset($offsets[$i]) ? $offsets[$i] : 0;
            $pdf .= str_pad($off, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }
        $pdf .= "trailer\n<< /Size ".$maxId." /Root 1 0 R /Info ".$infoId." 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        if ($dest === 'F') {
            $target = $filepath !== NULL ? $filepath : $filename;
            return file_put_contents($target, $pdf) !== FALSE;
        }
        // Download
        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Length: '.strlen($pdf));
            header('Cache-Control: private, max-age=0, must-revalidate');
        }
        echo $pdf;
        return strlen($pdf);
    }

    /**
     * Build the content stream for the current page (called just before flushing).
     */
    protected function build_stream()
    {
        // Render footer first so it draws behind content in z-order; actually draw after content visually via positions.
        $this->render_footer();

        $s = "BT /F1 11 Tf 1 1 1 rg 0 G ET\n";
        foreach ($this->state as $op) {
            $s .= $this->render_op($op);
        }
        return $s;
    }

    protected function render_op($op)
    {
        switch ($op['op']) {
            case 'rect':
                return sprintf("%.2f %.2f %.2f %.2f re %s f\n",
                    $op['x'], $this->h - $op['y'] - 6, $op['w'], 6,
                    $this->fill_color($op['fill']));
            case 'rectbox':
                // In PDF coords, y is measured from the bottom; our $op['y']
                // is the distance from the top of the page to the rectangle's
                // top edge. Convert: PDF_y = pageH - topEdge - height.
                return sprintf("%.2f %.2f %.2f %.2f re %s f\n",
                    $op['x'], $this->h - $op['y'] - $op['h'], $op['w'], $op['h'],
                    $this->fill_color($op['fill']));
            case 'line':
            case 'hr':
                return sprintf("%.2f %.2f m %.2f %.2f l 0.6 w %s S\n",
                    $op['x'], $this->h - $op['y'], $op['x2'] ?? ($op['x'] + ($op['w'] ?? 0)),
                    $this->h - ($op['y2'] ?? $op['y']), $this->stroke_color($op['color']));
            case 'text':
                return $this->text_op($op);
            case 'cell':
                $out = $this->cell_border($op);
                $out .= $this->text_op(array(
                    'x' => $op['x'] + 3, 'y' => $op['y'] + $op['h'] - $op['size'] - 1.5,
                    'text' => $op['text'], 'font' => $op['font'], 'style' => $op['style'],
                    'size' => $op['size'], 'color' => $op['color'], 'align' => $op['align'],
                    'maxw' => $op['w'] - 6,
                ));
                return $out;
        }
        return '';
    }

    protected function cell_border($op)
    {
        $b = $op['border'];
        if (!$b || $b === 0) return '';
        $x = $op['x']; $y = $this->h - $op['y'];
        $w = $op['w']; $h = -$op['h'];
        $out = '';
        $draw = function($x1,$y1,$x2,$y2) use (&$out) {
            $out .= sprintf("%.2f %.2f m %.2f %.2f l 0.4 w 0.8 0.82 0.86 RG S\n",$x1,$y1,$x2,$y2);
        };
        if ($b === 1 || is_string($b) && strpos($b,'T')!==FALSE) $draw($x, $y, $x+$w, $y);
        if ($b === 1 || is_string($b) && strpos($b,'B')!==FALSE) $draw($x, $y+$h, $x+$w, $y+$h);
        if ($b === 1 || is_string($b) && strpos($b,'L')!==FALSE) $draw($x, $y, $x, $y+$h);
        if ($b === 1 || is_string($b) && strpos($b,'R')!==FALSE) $draw($x+$w, $y, $x+$w, $y+$h);
        return $out;
    }

    protected function text_op($op)
    {
        $fontKey = '/F1';
        if ($op['font'] === 'Helvetica' && strpos($op['style'] ?? '', 'B') !== FALSE) $fontKey = '/F2';
        elseif ($op['font'] === 'Helvetica' && strpos($op['style'] ?? '', 'I') !== FALSE) $fontKey = '/F3';
        $size = $op['size'];
        $x = $op['x'];
        $y = $this->h - $op['y'] - $size;
        $align = $op['align'] ?? 'L';
        if ($align === 'R') {
            $tw = $this->width_for($op['text'], $size, $op['font'] ?? 'Helvetica');
            $max = $op['maxw'] ?? $this->page_width();
            $x = $this->w - $this->marginR - min($tw, $max);
        } elseif ($align === 'C') {
            $tw = $this->width_for($op['text'], $size, $op['font'] ?? 'Helvetica');
            $max = $op['maxw'] ?? $this->page_width();
            $x = $op['x'] + ($max - min($tw, $max)) / 2;
        }
        $c = $op['color'];
        return sprintf("BT %s %.2f Tf %.2f %.2f Td %.3f %.3f %.3f rg (%s) Tj ET\n",
            $fontKey, $size, $x, $y, $c[0]/255, $c[1]/255, $c[2]/255, $op['text']);
    }

    protected function width_for($text, $size, $font = 'Helvetica')
    {
        $old = array($this->font, $this->size);
        $this->font = $font; $this->size = $size;
        $w = $this->text_width($text);
        $this->font = $old[0]; $this->size = $old[1];
        return $w;
    }

    protected function fill_color($c) { return sprintf("%.3f %.3f %.3f", $c[0]/255, $c[1]/255, $c[2]/255); }
    protected function stroke_color($c) { return sprintf("%.3f %.3f %.3f RG", $c[0]/255, $c[1]/255, $c[2]/255); }
}
