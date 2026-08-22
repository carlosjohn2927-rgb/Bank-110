<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$route['default_controller'] = 'home/index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Public marketing homepage.
$route['home'] = 'home/index';

// Public content pages (about, security, fees, branches, help, contact, privacy, terms).
$route['about']    = 'pages/view/about';
$route['security-center'] = 'pages/view/security';
$route['fees']     = 'pages/view/fees';
$route['branches'] = 'pages/view/branches';
$route['help']     = 'pages/view/help';
$route['contact']  = 'pages/view/contact';
$route['privacy']  = 'pages/view/privacy';
$route['terms']    = 'pages/view/terms';
$route['p/(:any)'] = 'pages/view/$1';
$route['products'] = 'pages/products';
$route['personal'] = 'pages/products';
$route['loans-public'] = 'pages/loans';
$route['borrow']   = 'pages/loans';
$route['cards-public'] = 'pages/cards';
$route['calculator'] = 'pages/calculator';

// Administrator sign-in (formerly the customer login at /login).
$route['login'] = 'auth/login';
$route['admin'] = 'auth/login';
$route['admin/login'] = 'auth/login';
$route['admin/logout'] = 'auth/logout';

// Customer sign-in.
$route['user/login'] = 'auth/user_login';
$route['signin'] = 'auth/user_login';
$route['verify'] = 'auth/verify';
$route['auth/customer_login'] = 'auth/customer_login';
$route['logout'] = 'auth/logout';
$route['twofa'] = 'auth/twofa';
$route['twofa/resend'] = 'auth/resend_twofa';
$route['forgot'] = 'auth/forgot';
$route['reset/(:any)'] = 'auth/reset/$1';
$route['register'] = 'register/index';
$route['dashboard'] = 'dashboard/index';
$route['accounts'] = 'accounts/index';
$route['accounts/create'] = 'accounts/create';
$route['accounts/deposit'] = 'accounts/deposit';
$route['accounts/(:num)/status'] = 'accounts/status/$1';
$route['transactions'] = 'transactions/index';
$route['transactions/statement'] = 'transactions/statement';
$route['transactions/(:num)'] = 'transactions/view/$1';
$route['transfer'] = 'transfers/create';
$route['transfers'] = 'transfers/index';
$route['transfers/(:num)/cancel'] = 'transfers/cancel/$1';
$route['bills'] = 'bills/index';
$route['bills/pay'] = 'bills/pay';

// Savings goals
$route['goals'] = 'goals/index';
$route['goals/create'] = 'goals/create';
$route['goals/(:num)/contribute'] = 'goals/contribute/$1';
$route['goals/(:num)/withdraw'] = 'goals/withdraw/$1';
$route['goals/(:num)/delete'] = 'goals/delete/$1';

// Budget insights
$route['budget'] = 'budget/index';
$route['budget/save-limit'] = 'budget/save_limit';
$route['beneficiaries'] = 'transfers/beneficiaries';
$route['beneficiaries/(:num)/update'] = 'transfers/beneficiary_update/$1';
$route['beneficiaries/(:num)/delete'] = 'transfers/beneficiary_delete/$1';
$route['exchange'] = 'exchange/index';
$route['cards'] = 'cards/index';
$route['cards/create'] = 'cards/create';
$route['cards/(:num)/toggle'] = 'cards/toggle/$1';
$route['cards/(:num)/report-lost'] = 'cards/report_lost/$1';
$route['loans'] = 'loans/index';
$route['loans/create'] = 'loans/create';
$route['loans/(:num)/pay'] = 'loans/pay/$1';
$route['support'] = 'support/index';
$route['support/create'] = 'support/create';
$route['support/(:num)'] = 'support/view/$1';
$route['settings'] = 'profile/index';
$route['notifications'] = 'notifications/index';
$route['settings/preferences'] = 'profile/preferences';
$route['settings/password'] = 'profile/password';
$route['settings/twofa'] = 'profile/twofa';
$route['settings/kyc'] = 'profile/kyc';
$route['chat'] = 'chat/index';
$route['language/set'] = 'language/set';
$route['scheduler/run'] = 'scheduler/run';
$route['setup/check'] = 'setup/check';

$route['admin/dashboard'] = 'admin/dashboard';
$route['admin/customers'] = 'admin/customers';
$route['admin/customers/create'] = 'admin/customer_create';
$route['admin/customers/export'] = 'admin/export_customers';
$route['admin/customers/(:num)'] = 'admin/customer/$1';
$route['admin/customers/(:num)/status'] = 'admin/customer_status/$1';
$route['admin/customers/(:num)/adjust'] = 'admin/customer_adjust/$1';
$route['admin/customers/(:num)/kyc'] = 'admin/kyc/$1';
$route['admin/accounts/(:num)/status'] = 'admin/account_status/$1';
$route['admin/transactions'] = 'admin/transactions';
$route['admin/transactions/export'] = 'admin/export_transactions';
$route['admin/transactions/export/(:any)'] = 'admin/export_transactions/$1';
$route['admin/transactions/(:num)'] = 'admin/transaction/$1';
$route['admin/transactions/(:num)/status'] = 'admin/transaction_status/$1';
$route['admin/transfers'] = 'admin/transfers';
$route['admin/deposits'] = 'admin/deposits';
$route['admin/loans'] = 'admin/loans';
$route['admin/cards'] = 'admin/cards';
$route['admin/tickets'] = 'admin/tickets';
$route['admin/tickets/(:num)'] = 'admin/ticket/$1';
$route['admin/tickets/(:num)/reply'] = 'admin/ticket_reply/$1';
$route['admin/tutorial'] = 'admin/tutorial';
$route['admin/audit_logs'] = 'admin/audit_logs';
$route['admin/exchange_rates'] = 'admin/exchange_rates';
$route['admin/settings'] = 'admin/settings';
$route['admin/admin_users'] = 'admin/admin_users';
