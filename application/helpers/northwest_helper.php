<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function money($amount, $currency = 'USD')
{
    $symbols = array('USD' => '$', 'EUR' => '€', 'GBP' => '£');
    return ($symbols[$currency] ?? $currency.' ').number_format((float) $amount, 2);
}

function initials($name)
{
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? 'N', 0, 1).substr(end($parts) ?: 'W', 0, 1));
}

function status_class($status)
{
    return 'status status-'.preg_replace('/[^a-z0-9]+/', '-', strtolower($status));
}

function transaction_icon($type)
{
    $icons = array('credit' => '↓', 'debit' => '↑', 'transfer' => '↗', 'deposit' => '+');
    return $icons[$type] ?? '•';
}

/**
 * Collect SEO meta data. Runtime platform settings (seo_title, seo_description,
 * seo_keywords, seo_site_name) take precedence over the defaults in
 * application/config/seo.php.
 */
function site_seo()
{
    $CI =& get_instance();
    $settings = array();
    if (isset($CI->Bank_model)) {
        try { $settings = $CI->Bank_model->settings(); } catch (Exception $e) {}
    }
    $CI->config->load('seo', TRUE);
    $seo = $CI->config->item('seo') ?: array();

    $get = function ($key, $fallback) use ($settings, $seo) {
        if (isset($settings[$key]) && $settings[$key] !== '') return $settings[$key];
        return $seo[$fallback] ?? '';
    };

    return array(
        'site_name'    => $get('seo_site_name', 'seo_site_name'),
        'title'        => $get('seo_title', 'seo_title'),
        'description'  => $get('seo_description', 'seo_description'),
        'keywords'     => $get('seo_keywords', 'seo_keywords'),
        'og_type'      => $seo['seo_og_type'] ?? 'website',
        'twitter_card' => $seo['seo_twitter_card'] ?? 'summary_large_image',
        'robots'       => $seo['seo_robots'] ?? 'index, follow',
        'url'          => current_url(),
    );
}

/**
 * Echo <head> SEO meta tags. Pass $title_override (e.g. the page title) to build
 * a per-page <title> like "Dashboard · NorthWest Financial".
 */
function render_seo_meta($title_override = NULL)
{
    $seo = site_seo();
    $site = html_escape($seo['site_name']);
    $title = $title_override !== NULL && $title_override !== ''
        ? html_escape($title_override.' · '.$seo['site_name'])
        : html_escape($seo['title']);
    $desc = html_escape($seo['description']);
    $kw   = html_escape($seo['keywords']);
    $url  = html_escape($seo['url']);
    $og   = html_escape($seo['og_type']);
    $tw   = html_escape($seo['twitter_card']);
    $rob  = html_escape($seo['robots']);

    echo '<meta name="description" content="'.$desc.'">'."\n";
    echo '<meta name="keywords" content="'.$kw.'">'."\n";
    echo '<meta name="robots" content="'.$rob.'">'."\n";
    echo '<link rel="canonical" href="'.$url.'">'."\n";
    echo '<meta property="og:site_name" content="'.$site.'">'."\n";
    echo '<meta property="og:type" content="'.$og.'">'."\n";
    echo '<meta property="og:title" content="'.$title.'">'."\n";
    echo '<meta property="og:description" content="'.$desc.'">'."\n";
    echo '<meta property="og:url" content="'.$url.'">'."\n";
    echo '<meta name="twitter:card" content="'.$tw.'">'."\n";
    echo '<meta name="twitter:title" content="'.$title.'">'."\n";
    echo '<meta name="twitter:description" content="'.$desc.'">'."\n";
}

/**
 * Announcement bar text, editable from Admin → System settings.
 */
function site_announcement()
{
    $CI =& get_instance();
    $settings = array();
    if (isset($CI->Bank_model)) {
        try { $settings = $CI->Bank_model->settings(); } catch (Exception $e) {}
    }
    if (isset($settings['announcement_text']) && $settings['announcement_text'] !== '') {
        return $settings['announcement_text'];
    }
    return 'Welcome to NorthWest — Secure online banking with 256-bit encryption · Free NorthWest-to-NorthWest transfers · 24/7 support';
}

/**
 * Render the moving announcement bar partial (safe to call from any view).
 */
function render_announcement($message = NULL)
{
    $CI =& get_instance();
    $CI->load->view('layouts/partials/announcement', array('message' => $message));
}

/**
 * Render the In-Site AI chat widget partial (safe to call from any view).
 */
function render_chat_widget()
{
    $CI =& get_instance();
    $CI->load->view('layouts/partials/chat_widget');
}

/* ---- Multi-language helpers ---- */

function available_languages()
{
    $CI =& get_instance();
    $CI->config->load('languages', TRUE);
    return $CI->config->item('available_languages') ?: array('english' => 'English');
}

function current_language()
{
    $lang = get_instance()->session->userdata('language');
    if (!$lang) $lang = get_instance()->config->item('default_language') ?: 'english';
    if (!array_key_exists($lang, available_languages())) $lang = 'english';
    return $lang;
}

/**
 * A short language switcher (dropdown) usable in any layout or auth page.
 */
function render_language_switcher($form_class = 'lang-switch')
{
    $langs = available_languages();
    $current = current_language();
    $out = '<form method="post" action="'.site_url('language/set').'" class="'.html_escape($form_class).'">';
    $out .= '<label class="lang-switch-label" for="nw-lang">'.$current.'</label>';
    $out .= '<select id="nw-lang" name="language" onchange="this.form.submit()">';
    foreach ($langs as $key => $label) {
        $out .= '<option value="'.html_escape($key).'" '.($key === $current ? 'selected' : '').'>'.html_escape($label).'</option>';
    }
    $out .= '</select></form>';
    return $out;
}

function tl($key)
{
    return get_instance()->lang->line($key) ?: $key;
}

/**
 * Human-readable "time ago" string, e.g. "3 minutes ago".
 */
function time_ago($datetime)
{
    $time = is_numeric($datetime) ? (int) $datetime : strtotime($datetime);
    if (!$time) return '';
    $diff = time() - $time;
    if ($diff < 0) return date('M j, Y', $time);
    $units = array(
        31536000 => 'year', 2592000 => 'month', 604800 => 'week',
        86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second',
    );
    foreach ($units as $secs => $name) {
        if ($diff >= $secs) {
            $n = floor($diff / $secs);
            return $n.' '.$name.($n === 1 ? '' : 's').' ago';
        }
    }
    return 'just now';
}
