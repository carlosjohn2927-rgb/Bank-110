<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kb_model — in-app help center / knowledge base.
 *
 * Articles are defined as a portable PHP array (no database migration
 * required) so the help center works on every install. Each article carries
 * a category, slug, title, summary, body, keywords and related-article ids.
 * The search() method does weighted, stop-word-free matching across title,
 * keywords and body and returns results ranked by score.
 */
class Kb_model extends CI_Model {

    private $articles;

    public function __construct()
    {
        parent::__construct();
        $this->articles = $this->define();
    }

    /** All articles, optionally filtered by category. */
    public function articles($category = NULL)
    {
        if ($category) {
            return array_values(array_filter($this->articles, function($a) use ($category) {
                return $a['category_slug'] === $category;
            }));
        }
        return $this->articles;
    }

    /** A single article by slug. */
    public function article($slug)
    {
        foreach ($this->articles as $a) {
            if ($a['slug'] === $slug) return $a;
        }
        return NULL;
    }

    /** Category list with counts, ordered for display. */
    public function categories()
    {
        $order = array('getting-started','accounts','cards','transfers','deposits','loans','savings','budget','security','mobile','support');
        $counts = array();
        foreach ($this->articles as $a) {
            $key = $a['category_slug'];
            if (!isset($counts[$key])) $counts[$key] = array('slug' => $key, 'name' => $a['category'], 'count' => 0);
            $counts[$key]['count']++;
        }
        $out = array();
        foreach ($order as $slug) {
            if (isset($counts[$slug])) { $out[] = $counts[$slug]; unset($counts[$slug]); }
        }
        return array_merge($out, array_values($counts));
    }

    /**
     * Weighted search. Returns matching articles with a score, highest first.
     * Matches whole-word stems in title (weight 5), keywords (3), summary (2)
     * and body (1). Multi-word queries must all appear somewhere.
     */
    public function search($q, $limit = 10)
    {
        $q = trim((string)$q);
        if ($q === '' || mb_strlen($q) < 2) return array();

        $terms = array_values(array_filter(preg_split('/[\s,.;:!?&\/\\\\-]+/u', mb_strtolower($q)), function($t) {
            return mb_strlen($t) >= 2 && !in_array($t, $this->stop_words());
        }));
        if (empty($terms)) return array();

        $results = array();
        foreach ($this->articles as $a) {
            $hay_title   = mb_strtolower($a['title']);
            $hay_keyw    = mb_strtolower(implode(' ', $a['keywords']));
            $hay_summary = mb_strtolower($a['summary']);
            $hay_body    = mb_strtolower(strip_tags($a['body']));

            $score = 0; $matched = 0;
            foreach ($terms as $term) {
                $found = FALSE;
                if ($this->term_in($term, $hay_title))   { $score += 5; $found = TRUE; }
                if ($this->term_in($term, $hay_keyw))    { $score += 3; $found = TRUE; }
                if ($this->term_in($term, $hay_summary)) { $score += 2; $found = TRUE; }
                if ($this->term_in($term, $hay_body))    { $score += 1; $found = TRUE; }
                if ($found) $matched++;
            }
            // Require every term to appear somewhere (AND semantics).
            if ($matched === count($terms) && $score > 0) {
                $results[] = array_merge($a, array('score' => $score));
            }
        }
        usort($results, function($x, $y) {
            return $y['score'] <=> $x['score']
                ?: strcasecmp($x['title'], $y['title']);
        });
        return array_slice($results, 0, $limit);
    }

    private function term_in($term, $haystack)
    {
        // Word-boundary aware for latin text; substring fallback for CJK etc.
        if (preg_match('/\w/u', $term)) {
            return preg_match('/\b'.preg_quote($term, '/').'/u', $haystack) === 1;
        }
        return mb_strpos($haystack, $term) !== FALSE;
    }

    private function stop_words()
    {
        return array('the','a','an','and','or','but','to','of','in','on','at','for','is','are','was','were','be','been','how','what','when','where','why','do','does','i','my','me','we','you','your','can','could','would','should','with','about','that','this','it','if','from','by','as','get','got');
    }

    /* -------------------- Article catalog -------------------- */

    private function define()
    {
        $a = array();

        $a[] = array(
            'id' => 1, 'category' => 'Getting started', 'category_slug' => 'getting-started', 'slug' => 'create-account',
            'title' => 'How to open a NorthWest account',
            'summary' => 'Open an account online in about five minutes — all you need is a photo ID.',
            'keywords' => array('open','signup','sign up','register','join','new account','onboarding','kyc','identity'),
            'body' => "<p>Opening a NorthWest account takes about five minutes:</p>".
                "<ol><li>Tap <b>Open free account</b> on the homepage.</li>".
                "<li>Enter your name, email and choose a password.</li>".
                "<li>Verify your identity by photographing a government-issued photo ID (passport, driver's licence or national ID).</li>".
                "<li>Once approved you'll get an instant checking account, a virtual card you can use online immediately, and access to all features.</li></ol>".
                "<p>There is no minimum balance and no monthly fee on the standard account.</p>",
        );
        $a[] = array(
            'id' => 2, 'category' => 'Getting started', 'category_slug' => 'getting-started', 'slug' => 'sign-in',
            'title' => 'Signing in to online banking',
            'summary' => 'How to sign in with your account number or email and password, plus two-factor authentication.',
            'keywords' => array('signin','login','log in','password','account number','username','access','2fa','code'),
            'body' => "<p>Use <b>Sign in</b> from any page. Enter your account number, username or email, then your password.</p>".
                "<p>For your security you'll also complete a quick verification: a code we email to you, or — if you've set one up — a 6-digit code from your authenticator app.</p>".
                "<p>If you forget your password, use the <b>Forgot password</b> link on the sign-in page. We'll email you a secure reset link that expires in 30 minutes.</p>",
        );
        $a[] = array(
            'id' => 3, 'category' => 'Getting started', 'category_slug' => 'getting-started', 'slug' => 'mobile-app',
            'title' => 'The NorthWest mobile app',
            'summary' => 'Bank on iOS and Android: check balances, send money, deposit checks and freeze your card.',
            'keywords' => array('app','mobile','iphone','android','phone','download','ios','play store'),
            'body' => "<p>The NorthWest app runs on iOS 15 and later and Android 10 and later. You can also bank from any modern web browser.</p>".
                "<p>From the app you can view balances and transactions, send money, pay bills, deposit checks by photographing them, freeze or control your cards, set savings goals, and message support. Sign in with Face ID, Touch ID or fingerprint on supported devices.</p>",
        );

        $a[] = array(
            'id' => 10, 'category' => 'Accounts', 'category_slug' => 'accounts', 'slug' => 'account-types',
            'title' => 'Account types: checking, savings, investment',
            'summary' => 'Understand the three account types and what each is designed for.',
            'keywords' => array('checking','savings','investment','account type','interest','apy'),
            'body' => "<p>Every NorthWest customer can hold multiple accounts:</p>".
                "<ul><li><b>Checking</b> — everyday spending, direct debits, bills and your debit card.</li>".
                "<li><b>Savings</b> — earns interest; ideal for an emergency fund or planned purchases.</li>".
                "<li><b>Investment</b> — commission-free investing in stocks, ETFs and managed portfolios. Investment accounts are not FDIC insured and may lose value.</li></ul>".
                "<p>Open additional any time from <b>Accounts → Open an account</b>. Transfers between your own accounts are instant and free.</p>",
        );
        $a[] = array(
            'id' => 11, 'category' => 'Accounts', 'category_slug' => 'accounts', 'slug' => 'routing-number',
            'title' => 'Where to find your account and routing number',
            'summary' => 'Your account and routing numbers are on the Accounts page and in your statements.',
            'keywords' => array('routing','account number','sort code','iban','direct deposit','wire'),
            'body' => "<p>Open <b>Accounts</b>, choose the account, and you'll see the full account number and routing number displayed. You'll need these to set up direct deposit, receive a wire, or pay a bill by ACH.</p>",
        );
        $a[] = array(
            'id' => 12, 'category' => 'Accounts', 'category_slug' => 'accounts', 'slug' => 'close-account',
            'title' => 'Closing or freezing an account',
            'summary' => 'You can close an account yourself, with no fee, as long as it has a zero balance.',
            'keywords' => array('close','freeze','closed','deactivate','delete account'),
            'body' => "<p>From <b>Accounts</b>, open the account and choose <b>Set status</b>. You can freeze an account (temporarily block all activity) or close it permanently. An account must have a zero balance to be closed; primary checking accounts cannot be closed while other accounts rely on them.</p>",
        );
        $a[] = array(
            'id' => 13, 'category' => 'Accounts', 'category_slug' => 'accounts', 'slug' => 'download-statement',
            'title' => 'Downloading statements and history',
            'summary' => 'Export transactions as CSV or download a PDF statement for any month.',
            'keywords' => array('statement','csv','export','download','pdf','history','tax'),
            'body' => "<p>From <b>Transactions</b> use <b>Download CSV</b> for a spreadsheet of your activity. Monthly PDF statements are available from <b>Transactions → Statements</b>. You can also filter by type or search before exporting.</p>",
        );

        $a[] = array(
            'id' => 20, 'category' => 'Cards', 'category_slug' => 'cards', 'slug' => 'freeze-card',
            'title' => 'Freeze or unfreeze your card',
            'summary' => 'Freeze a misplaced card instantly in one tap; purchases are blocked until you unfreeze.',
            'keywords' => array('freeze','block','lost card','stolen','misplaced','disable','toggle'),
            'body' => "<p>Open <b>Cards</b>, select the card and tap <b>Freeze</b>. New purchases, ATM withdrawals and online transactions are blocked instantly. Recurring subscriptions and credits may still post. Tap again to unfreeze.</p>",
        );
        $a[] = array(
            'id' => 21, 'category' => 'Cards', 'category_slug' => 'cards', 'slug' => 'lost-stolen-card',
            'title' => 'Reporting a card lost or stolen',
            'summary' => 'Block the card permanently and we will send a replacement, usually within 3–5 days.',
            'keywords' => array('lost','stolen','replacement','reissue','fraud','new card'),
            'body' => "<p>If your card is gone for good, use <b>Report lost card</b> in <b>Cards</b>. The card is blocked immediately and a replacement is sent to your registered address. While you wait you can continue using a virtual card from the app.</p>",
        );
        $a[] = array(
            'id' => 22, 'category' => 'Cards', 'category_slug' => 'cards', 'slug' => 'card-controls',
            'title' => 'Card controls and spending limits',
            'summary' => 'Toggle online, contactless and international use and set a daily spending limit.',
            'keywords' => array('control','limit','online','contactless','international','atm','spending'),
            'body' => "<p>Every card has independent toggles for online payments, contactless, international use and ATM withdrawals. Set a daily spending cap that fits your habits — you can change it instantly any time.</p>",
        );
        $a[] = array(
            'id' => 23, 'category' => 'Cards', 'category_slug' => 'cards', 'slug' => 'virtual-card',
            'title' => 'Virtual cards',
            'summary' => 'A virtual card is ready to use online the moment your account is approved.',
            'keywords' => array('virtual','digital','online card','instant card','card number'),
            'body' => "<p>Virtual cards live in the app and can be used for online purchases, subscriptions and digital wallets right away. You can create additional virtual cards for specific merchants and freeze them independently, which is great for limiting subscription charges.</p>",
        );

        $a[] = array(
            'id' => 30, 'category' => 'Transfers & payments', 'category_slug' => 'transfers', 'slug' => 'send-money',
            'title' => 'Send money to someone',
            'summary' => 'Send money to any saved beneficiary, or add a new recipient in seconds.',
            'keywords' => array('transfer','send','payment','pay','beneficiary','recipient','wire','ach'),
            'body' => "<p>Open <b>Send money</b>, choose the account to pay from, pick a saved beneficiary or enter their name, account number and bank, then enter the amount and an optional note. Domestic transfers settle the same business day; NorthWest-to-NorthWest transfers are instant.</p>",
        );
        $a[] = array(
            'id' => 31, 'category' => 'Transfers & payments', 'category_slug' => 'transfers', 'slug' => 'beneficiaries',
            'title' => 'Adding and managing beneficiaries',
            'summary' => 'Save payees so future transfers are faster and safer.',
            'keywords' => array('beneficiary','payee','save recipient','add payee'),
            'body' => "<p>From <b>Beneficiaries</b> choose <b>Add beneficiary</b> and enter the recipient's name, account number and bank. You can edit or remove saved payees at any time. Saving a payee does not give them access to your account — it only auto-fills future transfer forms.</p>",
        );
        $a[] = array(
            'id' => 32, 'category' => 'Transfers & payments', 'category_slug' => 'transfers', 'slug' => 'schedule-transfer',
            'title' => 'Scheduling and cancelling a transfer',
            'summary' => 'Set a future date for a payment and cancel it any time before it processes.',
            'keywords' => array('schedule','future','recurring','cancel','pending','date'),
            'body' => "<p>Choose a future date when creating a transfer. Scheduled transfers appear in <b>Send money</b> under <b>Upcoming</b> and can be cancelled there until 23:59 on the day before they're due.</p>",
        );
        $a[] = array(
            'id' => 33, 'category' => 'Transfers & payments', 'category_slug' => 'transfers', 'slug' => 'transfer-fees',
            'title' => 'Transfer limits and fees',
            'summary' => 'Standard domestic transfers are free; international wires carry a disclosed fee.',
            'keywords' => array('fee','limit','charge','daily','international','wire'),
            'body' => "<p>NorthWest-to-NorthWest and standard domestic transfers are free. International wires carry a percentage fee (see the <a href='/fees'>Fees page</a> for current rates). The default daily transfer limit is shown on the Send money page and can be raised on request.</p>",
        );

        $a[] = array(
            'id' => 40, 'category' => 'Check deposit', 'category_slug' => 'deposits', 'slug' => 'mobile-check-deposit',
            'title' => 'Deposit a check with your phone',
            'summary' => 'Photograph the front and endorsed back of a check and submit it for review.',
            'keywords' => array('check','cheque','deposit','photo','mobile deposit','remote deposit'),
            'body' => "<p>Open <b>Deposit a check</b>, choose the account, enter the amount, then take a clear photo of the front and back. Endorse the back with your signature before photographing it.</p>".
                "<p>Deposits are reviewed by our team and typically approved within one business day. Funds are available once approved. Hold onto the paper check for 14 days, then destroy it.</p>",
        );
        $a[] = array(
            'id' => 41, 'category' => 'Check deposit', 'category_slug' => 'deposits', 'slug' => 'check-deposit-limits',
            'title' => 'Check deposit limits and holds',
            'summary' => 'Mobile deposits are limited to $25,000 per day; some checks may be held for review.',
            'keywords' => array('limit','hold','pending','availability','max','rejected'),
            'body' => "<p>The standard mobile check deposit limit is $25,000 per customer per day. Most deposits are approved within a business day, but larger or first-time deposits may be held for additional verification. You'll get a notification as soon as the deposit is approved or rejected, with the reason if applicable.</p>",
        );

        $a[] = array(
            'id' => 50, 'category' => 'Loans & credit', 'category_slug' => 'loans', 'slug' => 'apply-loan',
            'title' => 'Applying for a loan',
            'summary' => 'Personal, auto and home loans with decisions in minutes and no prepayment penalty.',
            'keywords' => array('loan','borrow','apply','credit','apr','rate','mortgage','auto'),
            'body' => "<p>Open <b>Loans</b> and choose the loan type. Enter the amount and term; you'll see an estimated monthly payment before applying. Most personal loans are decided in minutes. If approved, funds are in your account the same day.</p>".
                "<p>Use the <a href='/calculator'>loan calculator</a> to estimate payments before you apply.</p>",
        );
        $a[] = array(
            'id' => 51, 'category' => 'Loans & credit', 'category_slug' => 'loans', 'slug' => 'loan-payment',
            'title' => 'Making a loan payment',
            'summary' => 'Pay from any of your accounts; the loan balance updates immediately.',
            'keywords' => array('loan payment','pay loan','installment','due','repay'),
            'body' => "<p>From <b>Loans</b> open the loan and choose <b>Make a payment</b>. Pick the account to pay from and confirm. The minimum due is the monthly installment; you can pay more to reduce the balance faster, and there is never a prepayment penalty.</p>",
        );

        $a[] = array(
            'id' => 60, 'category' => 'Savings goals', 'category_slug' => 'savings', 'slug' => 'create-goal',
            'title' => 'Creating a savings goal',
            'summary' => 'Set a target amount and date, then add money whenever you like.',
            'keywords' => array('goal','savings target','save up','target','sinking fund'),
            'body' => "<p>From <b>Savings goals</b> tap <b>New goal</b>. Give it a name, a target amount and an optional target date, then pick an icon and color. Add money with the <b>Add money</b> button; the progress bar updates instantly.</p>",
        );
        $a[] = array(
            'id' => 61, 'category' => 'Savings goals', 'category_slug' => 'savings', 'slug' => 'goal-withdraw',
            'title' => 'Withdrawing from a goal',
            'summary' => 'Move money back out of a goal at any time without a fee.',
            'keywords' => array('withdraw goal','take money out','goal money','cancel goal'),
            'body' => "<p>Open the goal and choose <b>Withdraw</b>. Goal money is ring-fenced from your main balance but always withdrawable on demand. You can delete a goal you no longer need.</p>",
        );

        $a[] = array(
            'id' => 70, 'category' => 'Budget & insights', 'category_slug' => 'budget', 'slug' => 'budget-overview',
            'title' => 'Understanding your budget',
            'summary' => 'See income vs. spending, where your money goes, and set category limits.',
            'keywords' => array('budget','spending','insights','category','analytics','where money goes'),
            'body' => "<p><b>Budget & insights</b> summarises the last six months: income, expenses, net saved and your savings rate, plus a breakdown of spending by category. Set a monthly limit per category and you'll see when you're approaching or over it.</p>",
        );
        $a[] = array(
            'id' => 71, 'category' => 'Budget & insights', 'category_slug' => 'budget', 'slug' => 'set-budget-limit',
            'title' => 'Setting a category spending limit',
            'summary' => 'Enter a monthly cap for any category; progress is shown on the budget page.',
            'keywords' => array('budget limit','category limit','cap','spending target','alert'),
            'body' => "<p>On <b>Budget</b>, find the category in <b>Monthly budget limits</b>, enter an amount and save. The bar fills as you spend; it turns amber near the limit and red once exceeded. Clear the field to remove a limit.</p>",
        );

        $a[] = array(
            'id' => 80, 'category' => 'Security', 'category_slug' => 'security', 'slug' => 'two-factor',
            'title' => 'Two-factor authentication (2FA)',
            'summary' => 'Protect sign-in with an authenticator app or emailed codes.',
            'keywords' => array('2fa','two factor','authentication','authenticator','totp','google authenticator','otp'),
            'body' => "<p>From <b>Settings → Security</b> choose <b>Set up authenticator app</b>. Scan the QR code with Google Authenticator, Authy or 1Password, then confirm the 6-digit code. We'll show you 8 one-time backup codes — save them somewhere safe; each can be used once if you lose your phone.</p>".
                "<p>You can still receive an emailed code as a fallback. Never share a code with anyone — NorthWest will never ask for it.</p>",
        );
        $a[] = array(
            'id' => 81, 'category' => 'Security', 'category_slug' => 'security', 'slug' => 'change-password',
            'title' => 'Changing your password',
            'summary' => 'Update your password from Settings → Security at any time.',
            'keywords' => array('password','change password','reset','credentials'),
            'body' => "<p>Open <b>Settings → Security</b>, enter your current password and choose a new one of at least 8 characters. If you can't remember your current password, sign out and use <b>Forgot password</b>.</p>",
        );
        $a[] = array(
            'id' => 82, 'category' => 'Security', 'category_slug' => 'security', 'slug' => 'phishing-fraud',
            'title' => 'Spotting phishing and fraud',
            'summary' => 'How to recognize scams and what to do if you suspect fraud.',
            'keywords' => array('phishing','scam','fraud','suspicious','impersonation','spam'),
            'body' => "<p>NorthWest will never ask for your password, PIN or one-time code by email, phone or message. Treat any urgent request for those details as fraud. Forward suspicious emails to <b>fraud@northwest.example</b>.</p>".
                "<p>If you spot a transaction you don't recognise, freeze your card immediately and call our 24/7 fraud line. Confirmed fraud is reversed quickly.</p>",
        );

        $a[] = array(
            'id' => 90, 'category' => 'Support', 'category_slug' => 'support', 'slug' => 'contact-support',
            'title' => 'Contacting support',
            'summary' => 'Reach a human 24/7 by phone, secure message or in a branch.',
            'keywords' => array('support','contact','help','phone','email','chat','branch'),
            'body' => "<p>Open <b>Support</b> in online banking to start a secure message, or use the <a href='/contact'>Contact page</a> for phone numbers and branch details. Phone support is available around the clock every day of the year.</p>",
        );
        $a[] = array(
            'id' => 91, 'category' => 'Support', 'category_slug' => 'support', 'slug' => 'support-ticket',
            'title' => 'Opening and tracking a support request',
            'summary' => 'Create a ticket, add replies, and track its status from the Support page.',
            'keywords' => array('ticket','case','issue','request','reply','track'),
            'body' => "<p>From <b>Support → New request</b>, choose a category and describe the issue. You'll get a reference number and can add replies. We reply within one business day, usually much sooner.</p>",
        );

        return $a;
    }
}
