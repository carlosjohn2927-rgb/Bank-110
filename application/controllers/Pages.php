<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public marketing / content pages.
 *
 * Standard public-facing bank pages (About, Products, Loans, Cards,
 * Fees/Pricing, Security, Branches & ATMs, Help/FAQ, Contact, Privacy,
 * Terms). All are accessible without authentication.
 */
class Pages extends MY_Controller
{
    /** Whitelist of static content pages -> [view, title, description]. */
    protected $pages = array(
        'about'     => array('about',     'About NorthWest',           'Learn who NorthWest Financial is, our mission and the team building banking that moves with you.'),
        'security'  => array('security',  'How we keep your money safe','Read how NorthWest protects your accounts with encryption, 24/7 monitoring and zero-trust security.'),
        'fees'      => array('fees',      'Fees & pricing',            'Transparent, straightforward fees for everyday banking, transfers, cards and loans.'),
        'branches'  => array('branches',  'Branches & ATMs',           'Find NorthWest branches and surcharge-free ATMs near you.'),
        'help'      => array('help',      'Help center & FAQ',         'Answers to the questions customers ask most about banking with NorthWest.'),
        'contact'   => array('contact',   'Contact us',                'Reach NorthWest support by phone, secure message, email or in branch — 24 hours a day.'),
        'privacy'   => array('privacy',   'Privacy policy',            'How NorthWest collects, uses and protects your personal information.'),
        'terms'     => array('terms',     'Terms & conditions',        'The terms that govern your NorthWest accounts and services.'),
    );

    public function __construct()
    {
        parent::__construct();
        // Logged-in customers/admins hitting a public page stay on the site.
    }

    /** Generic public content page. */
    public function view($slug = NULL)
    {
        if ($slug === NULL || !isset($this->pages[$slug])) {
            show_404();
        }
        $page = $this->pages[$slug];
        $data = array(
            'title'       => $page[1],
            'seo_desc'    => $page[2],
            'slug'        => $slug,
        );
        $this->load->view('home/layout', array(
            'title'      => $page[1],
            'seo_desc'   => $page[2],
            'active'     => $slug,
            'content'    => $this->load->view('home/pages/'.$page[0], $data, TRUE),
        ));
    }

    /** Personal banking products / accounts overview. */
    public function products()
    {
        $this->load->view('home/layout', array(
            'title'    => 'Personal bank accounts',
            'seo_desc' => 'Compare NorthWest checking, high-yield savings and investment accounts and open one online in minutes.',
            'active'   => 'products',
            'content'  => $this->load->view('home/pages/products', array('title' => 'Personal bank accounts'), TRUE),
        ));
    }

    /** Loans & mortgages landing. */
    public function loans()
    {
        $this->load->view('home/layout', array(
            'title'    => 'Loans & mortgages',
            'seo_desc' => 'Personal loans, auto loans and home financing with transparent rates and decisions in minutes.',
            'active'   => 'loans',
            'content'  => $this->load->view('home/pages/loans', array('title' => 'Loans & mortgages'), TRUE),
        ));
    }

    /** Credit & debit cards. */
    public function cards()
    {
        $this->load->view('home/layout', array(
            'title'    => 'Credit & debit cards',
            'seo_desc' => 'Compare NorthWest cards — cash back, no foreign fees, instant freeze and premium metal cards.',
            'active'   => 'cards',
            'content'  => $this->load->view('home/pages/cards', array('title' => 'Credit & debit cards'), TRUE),
        ));
    }

    /** Public loan calculator (no login required). */
    public function calculator()
    {
        $amount   = (float) $this->input->get('amount', TRUE);
        $rate     = (float) $this->input->get('rate', TRUE);
        $term     = (int)   $this->input->get('term', TRUE);
        $type     = $this->input->get('type', TRUE) ?: 'personal';

        $result = NULL;
        if ($amount > 0 && $rate > 0 && $term > 0) {
            $r = $rate / 100 / 12;
            $n = $term;
            $payment = $amount * ($r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
            $total   = $payment * $n;
            $interest = $total - $amount;
            // Amortization for the first 12 months.
            $balance = $amount;
            $schedule = array();
            for ($m = 1; $m <= min($n, 12); $m++) {
                $interest_m = $balance * $r;
                $principal_m = $payment - $interest_m;
                $balance -= $principal_m;
                $schedule[] = array(
                    'month'     => $m,
                    'payment'   => $payment,
                    'principal' => $principal_m,
                    'interest'  => $interest_m,
                    'balance'   => max(0, $balance),
                );
            }
            $result = array(
                'payment'  => $payment,
                'total'    => $total,
                'interest' => $interest,
                'schedule' => $schedule,
            );
        }

        $this->load->view('home/layout', array(
            'title'    => 'Loan calculator',
            'seo_desc' => 'Estimate your monthly payment, total interest and amortization with the free NorthWest loan calculator.',
            'active'   => 'calculator',
            'content'  => $this->load->view('home/pages/calculator', array(
                'title'  => 'Loan calculator',
                'amount' => $amount ?: 10000,
                'rate'   => $rate ?: 7.9,
                'term'   => $term ?: 48,
                'type'   => $type,
                'result' => $result,
            ), TRUE),
        ));
    }
}
