<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SEO configuration — defaults used for <head> meta tags.
 * Values here can be overridden at runtime by the platform settings
 * (seo_title, seo_description, seo_keywords) managed from the Admin → System
 * settings page, which take precedence over these defaults.
 */
$config['seo_site_name']    = 'NorthWest Financial';
$config['seo_title']        = 'NorthWest Financial — Secure Online Banking';
$config['seo_description']  = 'Simple, secure online banking. Send money, manage cards, apply for loans and track your finances — all in one protected place with 256-bit encryption.';
$config['seo_keywords']     = 'online banking, secure banking, bank transfers, digital bank, NorthWest, personal accounts, savings, loans';
$config['seo_og_type']      = 'website';
$config['seo_twitter_card'] = 'summary_large_image';
$config['seo_robots']       = 'index, follow';
