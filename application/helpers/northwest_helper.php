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
