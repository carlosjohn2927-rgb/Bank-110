<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Qr_code — minimal self-contained byte-mode QR code generator.
 *
 * Supports byte (Latin-1/UTF-8) encoding, versions 1-10 (up to 174 chars
 * at L), error-correction level L, and automatic best-mask selection.
 * Renders to an SVG string so no GD/Imagick extension is required.
 *
 * Implemented from the ISO/IEC 18004 standard. Good enough for
 * short otpauth:// URIs used by the TOTP setup flow (max ~120 chars,
 * comfortably within version 10).
 */
class Qr_code {

    // EC level L: total codewords + data codewords per version (1-10).
    private static $ec = array(
        1  => array(26,  19),  2  => array(44,  34),  3  => array(70,  55),
        4  => array(100, 80),  5  => array(134, 108), 6  => array(172, 136),
        7  => array(196, 156), 8  => array(242, 194), 9  => array(292, 232),
        10 => array(346, 274),
    );
    // Alignment-center positions per version.
    private static $align = array(
        1=>array(), 2=>array(6,18), 3=>array(6,22), 4=>array(6,26),
        5=>array(6,30), 6=>array(6,34), 7=>array(6,22,38), 8=>array(6,24,42),
        9=>array(6,26,46), 10=>array(6,28,50),
    );
    private static $exp;
    private static $log;

    public static function svg($text, $scale = 6, $margin = 4)
    {
        $modules = self::build($text);
        $size = count($modules);
        $dim = ($size + $margin * 2) * $scale;
        $rects = '';
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (!empty($modules[$y][$x])) {
                    $rects .= '<rect x="'.(($x + $margin) * $scale).'" y="'.(($y + $margin) * $scale).'" width="'.$scale.'" height="'.$scale.'"/>';
                }
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$dim.'" height="'.$dim.'" viewBox="0 0 '.$dim.' '.$dim.'" shape-rendering="crispEdges" role="img" aria-label="QR code"><rect width="100%" height="100%" fill="#fff"/>'.$rects.'</svg>';
    }

    public static function build($text)
    {
        self::init_tables();
        $version = self::pick_version($text);
        $data = self::encode_data($text, $version);
        $ec = self::error_correction($data, $version);
        $final = self::interleave($data, $ec, $version);
        $matrix = self::place_modules($final, $version);
        $best = self::choose_mask($matrix, $version);
        return $best;
    }

    /* ---- version selection ---- */

    private static function pick_version($text)
    {
        $len = strlen($text);
        // byte mode: 4 mode bits + 8/16 length bits + data bits
        foreach (self::$ec as $v => $c) {
            $bits = 4 + ($v < 10 ? 8 : 16) + $len * 8;
            if ($bits <= $c[1] * 8) return $v;
        }
        throw new RuntimeException('Text too long for QR (versions 1-10, level L).');
    }

    private static function encode_data($text, $version)
    {
        $bits = '0100'; // byte mode indicator
        $lenBits = $version < 10 ? 8 : 16;
        $bits .= str_pad(decbin(strlen($text)), $lenBits, '0', STR_PAD_LEFT);
        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }
        $dataCodewords = self::$ec[$version][1];
        $capacity = $dataCodewords * 8;
        // terminator (up to 4 zeros)
        $bits .= str_repeat('0', min(4, $capacity - strlen($bits)));
        // pad to byte boundary
        while (strlen($bits) % 8 !== 0) $bits .= '0';
        // pad bytes
        $pad = array(0xEC, 0x11);
        $i = 0;
        while (strlen($bits) < $capacity) {
            $bits .= str_pad(decbin($pad[$i % 2]), 8, '0', STR_PAD_LEFT);
            $i++;
        }
        $codewords = array();
        for ($j = 0; $j < strlen($bits); $j += 8) {
            $codewords[] = bindec(substr($bits, $j, 8));
        }
        return $codewords;
    }

    /* ---- Reed-Solomon error correction (GF(256)) ---- */

    private static function init_tables()
    {
        if (self::$exp !== NULL) return;
        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) $x ^= 0x11D;
        }
        for ($i = 255; $i < 512; $i++) self::$exp[$i] = self::$exp[$i - 255];
    }

    private static function gf_mul($a, $b)
    {
        if ($a === 0 || $b === 0) return 0;
        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    private static function rs_generator($degree)
    {
        $poly = array(1);
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            foreach ($poly as $j => $c) {
                $next[$j] ^= self::gf_mul($c, 1);
                $next[$j + 1] ^= self::gf_mul($c, self::$exp[$i]);
            }
            $poly = $next;
        }
        return $poly;
    }

    private static function error_correction($data, $version)
    {
        $ecCount = self::$ec[$version][0] - self::$ec[$version][1];
        $gen = self::rs_generator($ecCount);
        $buf = array_merge($data, array_fill(0, $ecCount, 0));
        for ($i = 0; $i < count($data); $i++) {
            $factor = $buf[$i];
            if ($factor !== 0) {
                for ($j = 0; $j < count($gen); $j++) {
                    $buf[$i + $j] ^= self::gf_mul($gen[$j], $factor);
                }
            }
        }
        return array_slice($buf, count($data));
    }

    private static function interleave($data, $ec, $version)
    {
        // Level L uses a single block for versions 1-10, so no interleaving needed.
        return array_merge($data, $ec);
    }

    /* ---- matrix construction ---- */

    private static function place_modules($codewords, $version)
    {
        $size = 17 + $version * 4;
        $m = array_fill(0, $size, array_fill(0, $size, 0));
        self::place_finders($m, $size);
        self::place_alignment($m, $version);
        self::place_timing($m, $size);
        self::place_format_placeholder($m, $size);
        self::place_data($m, $codewords, $size);
        return $m;
    }

    private static function place_finders(&$m, $size)
    {
        $positions = array(array(0, 0), array(0, $size - 7), array($size - 7, 0));
        foreach ($positions as $p) {
            list($r, $c) = $p;
            for ($y = -1; $y <= 7; $y++) {
                for ($x = -1; $x <= 7; $x++) {
                    $rr = $r + $y; $cc = $c + $x;
                    if ($rr < 0 || $cc < 0 || $rr >= $size || $cc >= $size) continue;
                    $m[$rr][$cc] = ($x >= 0 && $x <= 6 && ($y === 0 || $y === 6))
                        || ($y >= 0 && $y <= 6 && ($x === 0 || $x === 6))
                        || ($x >= 2 && $x <= 4 && $y >= 2 && $y <= 4) ? 1 : 0;
                }
            }
        }
    }

    private static function place_alignment(&$m, $version)
    {
        $centers = self::$align[$version];
        foreach ($centers as $r) {
            foreach ($centers as $c) {
                if (!empty($m[$r][$c])) continue; // skip finder overlap
                for ($y = -2; $y <= 2; $y++) {
                    for ($x = -2; $x <= 2; $x++) {
                        $on = (max(abs($x), abs($y)) === 2) || ($x === 0 && $y === 0);
                        $m[$r + $y][$c + $x] = $on ? 1 : 0;
                    }
                }
            }
        }
    }

    private static function place_timing(&$m, $size)
    {
        for ($i = 8; $i < $size - 8; $i++) {
            if ($m[6][$i] === 0) $m[6][$i] = $i % 2 === 0 ? 1 : 0;
            if ($m[$i][6] === 0) $m[$i][6] = $i % 2 === 0 ? 1 : 0;
        }
    }

    private static function place_format_placeholder(&$m, $size)
    {
        $GLOBALS['__qr_version'] = self::version_for_size($size);
        // Dark module (always present below the top-left finder)
        $m[$size - 8][8] = 1;
        // Reserve format bits around finders (written after masking)
        for ($i = 0; $i <= 8; $i++) {
            if ($i < 6 || $i > 8) { $m[8][$i] = 0; $m[$i][8] = 0; }
        }
        for ($i = 0; $i < 8; $i++) {
            $m[8][$size - 1 - $i] = 0;
            $m[$size - 1 - $i][8] = 0;
        }
    }

    private static function version_for_size($size)
    {
        return (int)(($size - 17) / 4);
    }

    private static function place_data(&$m, $codewords, $size)
    {
        $bits = '';
        foreach ($codewords as $w) $bits .= str_pad(decbin($w), 8, '0', STR_PAD_LEFT);
        $idx = 0;
        $total = strlen($bits);
        $up = TRUE;
        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col = 5; // skip timing column
            for ($row = 0; $row < $size; $row++) {
                $y = $up ? $size - 1 - $row : $row;
                for ($c = 0; $c < 2; $c++) {
                    $x = $col - $c;
                    if ($m[$y][$x] === 0 && $idx < $total) {
                        $m[$y][$x] = (int)$bits[$idx];
                        $idx++;
                    }
                }
            }
            $up = !$up;
        }
    }

    /* ---- masking ---- */

    private static function apply_mask($matrix, $mask)
    {
        $size = count($matrix);
        $m = $matrix;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (self::is_function($y, $x, $size)) continue;
                if (self::mask_bit($mask, $y, $x)) $m[$y][$x] ^= 1;
            }
        }
        self::write_format($m, $mask, $size);
        return $m;
    }

    private static function is_function($y, $x, $size)
    {
        // Finders + separators (9x9 corners)
        if (($x < 9 && $y < 9) || ($x >= $size - 8 && $y < 9) || ($x < 9 && $y >= $size - 8)) return TRUE;
        // Timing row/col
        if ($x === 6 || $y === 6) return TRUE;
        // Alignment patterns
        $centers = self::$align[$GLOBALS['__qr_version'] ?? 1];
        foreach ($centers as $cy) {
            foreach ($centers as $cx) {
                if (abs($x - $cx) <= 2 && abs($y - $cy) <= 2) return TRUE;
            }
        }
        return FALSE;
    }

    private static function mask_bit($mask, $y, $x)
    {
        switch ($mask) {
            case 0: return (($x + $y) % 2) === 0;
            case 1: return ($y % 2) === 0;
            case 2: return ($x % 3) === 0;
            case 3: return (($x + $y) % 3) === 0;
            case 4: return ((floor($y / 2) + floor($x / 3)) % 2) === 0;
            case 5: return (($x * $y) % 2) + (($x * $y) % 3) === 0;
            case 6: return ((($x * $y) % 2) + (($x * $y) % 3)) % 2 === 0;
            case 7: return ((($x + $y) % 2) + (($x * $y) % 3)) % 2 === 0;
        }
        return FALSE;
    }

    private static function write_format(&$m, $mask, $size)
    {
        // EC level L = 01; format = 5 data bits (2 EC + 3 mask) then BCH(15,5)
        $data = (0b01 << 3) | $mask;
        $bch = $this_bch = self::bch_format($data);
        $bits = str_pad(decbin($bch), 15, '0', STR_PAD_LEFT);
        // First copy (around top-left)
        for ($i = 0; $i < 6; $i++) $m[8][$i] = (int)$bits[$i];
        $m[8][7] = (int)$bits[6]; $m[8][8] = (int)$bits[7]; $m[7][8] = (int)$bits[8];
        for ($i = 9; $i < 15; $i++) $m[14 - $i][8] = (int)$bits[$i];
        // Second copy
        for ($i = 0; $i < 8; $i++) $m[$size - 1 - $i][8] = (int)$bits[$i];
        for ($i = 8; $i < 15; $i++) $m[8][$size - 15 + $i] = (int)$bits[$i];
        $m[$size - 8][8] = 1; // dark module
    }

    private static function bch_format($data)
    {
        $g = 0x537;
        $v = $data << 10;
        for ($i = 14; $i >= 10; $i--) {
            if (($v >> $i) & 1) $v ^= $g << ($i - 10);
        }
        return (($data << 10) | $v) ^ 0x5412; // xor mask
    }

    private static function choose_mask($matrix)
    {
        $best = NULL; $bestScore = PHP_FLOAT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::apply_mask($matrix, $mask);
            $score = self::penalty($candidate);
            if ($score < $bestScore) { $bestScore = $score; $best = $candidate; }
        }
        return $best;
    }

    private static function penalty($m)
    {
        $size = count($m);
        $score = 0;
        // Rule 1: runs of 5+ in rows/cols
        for ($y = 0; $y < $size; $y++) {
            $run = 1;
            for ($x = 1; $x < $size; $x++) {
                if ($m[$y][$x] === $m[$y][$x - 1]) { $run++; }
                else { if ($run >= 5) $score += 3 + ($run - 5); $run = 1; }
            }
            if ($run >= 5) $score += 3 + ($run - 5);
        }
        for ($x = 0; $x < $size; $x++) {
            $run = 1;
            for ($y = 1; $y < $size; $y++) {
                if ($m[$y][$x] === $m[$y - 1][$x]) { $run++; }
                else { if ($run >= 5) $score += 3 + ($run - 5); $run = 1; }
            }
            if ($run >= 5) $score += 3 + ($run - 5);
        }
        // Rule 2: 2x2 same-color blocks
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                if ($m[$y][$x] === $m[$y + 1][$x] && $m[$y][$x] === $m[$y][$x + 1] && $m[$y][$x] === $m[$y + 1][$x + 1]) $score += 3;
            }
        }
        // Rule 4: balance 50/50
        $dark = 0;
        foreach ($m as $row) $dark += array_sum($row);
        $ratio = ($dark * 100) / ($size * $size);
        $score += (int)(abs($ratio - 50) / 5) * 10;
        return $score;
    }
}
