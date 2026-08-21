<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$config['protocol'] = getenv('VP_MAIL_PROTOCOL') ?: 'smtp';
$config['smtp_host'] = getenv('VP_MAIL_HOST') ?: '';
$config['smtp_port'] = (int) (getenv('VP_MAIL_PORT') ?: 587);
$config['smtp_user'] = getenv('VP_MAIL_USER') ?: '';
$config['smtp_pass'] = getenv('VP_MAIL_PASS') ?: '';
$config['smtp_crypto'] = getenv('VP_MAIL_CRYPTO') ?: 'tls';
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['crlf'] = "\r\n";
$config['wordwrap'] = TRUE;
$config['validate'] = TRUE;
