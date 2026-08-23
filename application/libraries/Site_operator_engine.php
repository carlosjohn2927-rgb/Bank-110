<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Site_operator_engine
 * ----------------------------------------------------------------------------
 * NorthWest In-Site Operating AI Assistant — server runtime.
 *
 * 100% local, offline knowledge & operational engine. It requires ZERO
 * external API keys and makes ZERO third-party network calls. Every response
 * is generated from the embedded intent table below plus the signed-in user's
 * own banking data (balance, accounts, transactions, limits) fetched from the
 * local database.
 *
 * Mirrors the canonical engine definition in `src/lib/ai/site-operator-engine.ts`.
 * Keep both files in sync.
 */
class Site_operator_engine
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Build a reply for a raw user message.
     *
     * @param string      $message
     * @param array|null  $user    signed-in user row (or NULL for guests)
     * @param array       $context optional pre-built banking context
     * @return array{text:string, quick:array, actions:array}
     */
    public function reply($message, $user = NULL, $context = array())
    {
        $text = trim((string) $message);
        if ($text === '') return $this->fallbackReply();

        $context = $this->buildContext($context, $user);
        $reply   = $this->matchIntent($text, $user, $context);

        if ($reply === NULL) $reply = $this->fallbackReply();

        $reply['quick']   = isset($reply['quick']) ? $reply['quick'] : array();
        $reply['actions'] = isset($reply['actions']) ? $reply['actions'] : array();
        return $reply;
    }

    /**
     * Assemble banking context used by intents (balance, accounts, txn, limits).
     */
    protected function buildContext($context, $user)
    {
        $settings = array();
        try { $settings = $this->CI->Bank_model->settings(); } catch (\Throwable $e) {}

        if (!isset($context['institutionName']))    $context['institutionName']    = $settings['institution_name'] ?? 'NorthWest Financial Ltd.';
        if (!isset($context['supportEmail']))       $context['supportEmail']       = $settings['support_email'] ?? 'support@northwest';
        if (!isset($context['defaultCurrency']))    $context['defaultCurrency']    = $settings['default_currency'] ?? 'USD';
        if (!isset($context['dailyTransferLimit'])) $context['dailyTransferLimit'] = $settings['daily_transfer_limit'] ?? '25000';

        if ($user && $user['role'] === 'customer') {
            try {
                $uid = (int) $user['id'];
                if (!isset($context['totalBalance'])) {
                    $context['totalBalance'] = (float) $this->CI->Bank_model->total_balance($uid);
                }
                if (!isset($context['accounts'])) {
                    $context['accounts'] = $this->CI->Bank_model->accounts($uid);
                }
                if (!isset($context['transactions'])) {
                    $context['transactions'] = $this->CI->Bank_model->transactions_for_user($uid, 6);
                }
                // Savings goals + budget insights (used by the goals/budget intents).
                if (!isset($context['goals'])) {
                    $context['goals'] = method_exists($this->CI->Bank_model, 'goals')
                        ? $this->CI->Bank_model->goals($uid) : array();
                }
                if (!isset($context['monthlySummary'])) {
                    $context['monthlySummary'] = method_exists($this->CI->Bank_model, 'monthly_summary')
                        ? $this->CI->Bank_model->monthly_summary($uid) : array('income' => 0, 'expenses' => 0);
                }
                if (!isset($context['spendingBreakdown'])) {
                    $context['spendingBreakdown'] = method_exists($this->CI->Bank_model, 'spending_breakdown')
                        ? $this->CI->Bank_model->spending_breakdown($uid, 5) : array();
                }
            } catch (\Throwable $e) {}
        } else {
            $context['accounts']     = isset($context['accounts']) ? $context['accounts'] : array();
            $context['transactions'] = isset($context['transactions']) ? $context['transactions'] : array();
            $context['goals']        = array();
            $context['monthlySummary'] = array('income' => 0, 'expenses' => 0);
            $context['spendingBreakdown'] = array();
        }
        return $context;
    }

    /**
     * Keyword / pattern matching across the intent table.
     */
    protected function matchIntent($text, $user, $context)
    {
        $intents = array(
            'greeting'      => '/^(hi|hello|hey|good (morning|afternoon|evening)|yo|howdy)\b/i',
            'balance'       => '/(balance|how much (money|do i have|is in)|total (balance|funds)|account balance|funds|my money)/i',
            'transactions'  => '/(transaction|activity|history|recent (moves|spending|payments)|statement|where did i spend|what did i buy)/i',
            'transfer'      => '/(transfer|send money|pay someone|send (to|money)|recipient|beneficiar|wire|make a payment|how do i send)/i',
            'beneficiary'   => '/(beneficiar|add a payee|save a recipient|recipient list)/i',
            'card'          => '/(card|freeze|block|unfreeze|limit|cvv|pin|card controls|virtual card)/i',
            'loan'          => '/(loan|borrow|credit|mortgage|finance)/i',
            'goals'         => '/(savings goal|saving goal|my goal|goal(s| progress)?|save up|save for|target (amount|saving)|how (close|much).*(goal|saved))/i',
            'budget'        => '/(budget|spending|spent|expense|what did i spend|where does my money|category|categories|savings rate|how much did i (spend|save))/i',
            'security'      => '/(secure|security|safe|protect|phishing|fraud|hack|password|otp|2fa|two factor|verification code)/i',
            'fees'          => '/(fee|charge|cost|limit|how much does it cost|free)/i',
            'open_account'  => '/(open (an|a)? ?account|new account|create account|become a customer|register|join)/i',
            'signin'        => '/(sign ?in|log ?in|login|password|forgot)/i',
            'support'       => '/(support|help|human|agent|talk to|contact|complaint|reach|representative|phone|email)/i',
            'thanks'        => '/(thank|thanks|thx|great|awesome|nice|good (job|work)|appreciate)/i',
        );

        foreach ($intents as $id => $pattern) {
            if (preg_match($pattern, $text)) {
                return $this->handleIntent($id, $user, $context);
            }
        }
        return NULL;
    }

    protected function handleIntent($id, $user, $context)
    {
        $institution = $context['institutionName'];
        $currency    = $context['defaultCurrency'];
        $limit       = $context['dailyTransferLimit'];
        $support     = $context['supportEmail'];
        $first       = $user['first_name'] ?? '';

        switch ($id) {
            case 'greeting':
                if ($user) {
                    return array('text' => "Hello {$first} 👋 Welcome to {$institution}. I can check your balance, show recent activity, track savings goals, analyze your budget, and explain transfers, cards, loans and security. What would you like help with?",
                        'quick' => array(
                            array('label' => '💰 My balance', 'value' => 'What is my balance?'),
                            array('label' => '🎯 Savings goals', 'value' => 'How are my savings goals?'),
                            array('label' => '📊 My budget', 'value' => 'How is my spending this month?'),
                            array('label' => '↗ Send money', 'value' => 'How do I send money?'),
                        ));
                }
                return array('text' => "Hello 👋 Welcome to {$institution}. I can help you learn about our services, transfers, cards, loans, fees and security. To see live account info, please sign in first.",
                    'quick' => array(
                        array('label' => '🏦 Open an account', 'value' => 'How do I open an account?'),
                        array('label' => '💳 Cards', 'value' => 'How do card controls work?'),
                        array('label' => '🔐 Security', 'value' => 'How is my account secure?'),
                    ));

            case 'balance':
                if (!$user || !array_key_exists('totalBalance', $context)) {
                    return array('text' => 'Your current available balance is shown on your dashboard. Please sign in to see your live balance.',
                        'quick' => array(array('label' => '🔑 Sign in', 'value' => 'How do I sign in?')));
                }
                $lines = array();
                foreach ((array) $context['accounts'] as $a) {
                    $last4 = substr($a['account_number'], -4);
                    $lines[] = "  • {$a['name']} (•••• {$last4}) — ".$this->money($a['available_balance'], $currency);
                }
                return array('text' => "Here is your current financial position:\n\nTotal available balance: ".$this->money($context['totalBalance'], $currency)."\n\n".implode("\n", $lines)."\n\nYou can send money or view full details from the dashboard.",
                    'quick' => array(
                        array('label' => '📊 Recent activity', 'value' => 'Show my recent transactions'),
                        array('label' => '↗ Send money', 'value' => 'How do I send money?'),
                    ),
                    'actions' => array(array('label' => 'Open dashboard', 'url' => '/dashboard')));

            case 'transactions':
                $tx = (array) $context['transactions'];
                if (!$user || empty($tx)) {
                    return array('text' => 'Your most recent account activity appears on the dashboard and Transactions page. Please sign in to see it.',
                        'quick' => array(array('label' => '📊 Transactions', 'value' => 'Show my recent transactions')));
                }
                $lines = array();
                foreach (array_slice($tx, 0, 5) as $t) {
                    $sign = $t['type'] === 'credit' ? '+' : '−';
                    $lines[] = "  {$sign} ".$this->money($t['amount'], $currency)." — {$t['description']} ({$t['category']})";
                }
                return array('text' => "Here are your ".count($tx)." most recent transactions:\n\n".implode("\n", $lines)."\n\nFor the full list, open the Transactions page.",
                    'quick' => array(
                        array('label' => '↗ Send money', 'value' => 'How do I send money?'),
                        array('label' => '💳 Cards', 'value' => 'How do card controls work?'),
                    ),
                    'actions' => array(array('label' => 'View all transactions', 'url' => '/transactions')));

            case 'transfer':
                return array('text' => "You can send money from the \"Send money\" page.\n\n1. Pick the account to send from.\n2. Choose a saved beneficiary or enter the recipient's name, account number and bank.\n3. Enter the amount and (optionally) a note.\n4. Review and submit — transfers are processed with end-to-end encryption.\n\nThe daily transfer limit is ".number_format((float) $limit).".",
                    'quick' => array(
                        array('label' => '↗ Go to Send money', 'value' => 'Take me to Send money'),
                        array('label' => '👥 Beneficiaries', 'value' => 'How do I add a beneficiary?'),
                    ),
                    'actions' => array(array('label' => 'Open Send money', 'url' => '/transfer')));

            case 'beneficiary':
                return array('text' => "To add a beneficiary, open the \"Beneficiaries\" page and use the \"Add beneficiary\" option. You'll need the recipient's name, account number and bank name. Saved beneficiaries make future transfers faster and safer.",
                    'quick' => array(array('label' => '↗ Send money', 'value' => 'How do I send money?')),
                    'actions' => array(array('label' => 'Manage beneficiaries', 'url' => '/beneficiaries')));

            case 'card':
                return array('text' => "From the \"Cards\" page you can view your cards and toggle card controls such as: freezing a card instantly, enabling online payments, and international use. Freezing a card immediately blocks new transactions while keeping your account intact.",
                    'quick' => array(
                        array('label' => '💳 Manage cards', 'value' => 'Show me my card options'),
                        array('label' => '🔐 Security', 'value' => 'How is my account secure?'),
                    ),
                    'actions' => array(array('label' => 'Open Cards', 'url' => '/cards')));

            case 'loan':
                return array('text' => "You can apply for a personal loan directly from the \"Loans\" page. Loans are offered with competitive fixed rates, clear monthly payments and no hidden fees. Open Loans to see your available options and estimated repayments.",
                    'quick' => array(array('label' => '▥ View loans', 'value' => 'Tell me more about loans')),
                    'actions' => array(array('label' => 'Open Loans', 'url' => '/loans')));

            case 'goals':
                if (!$user) {
                    return array('text' => "Savings goals let you set a target, add money as you go, and watch your progress grow. Once you sign in you can create goals for a holiday, a home or anything else — it's completely free.",
                        'quick' => array(array('label' => '🔑 Sign in', 'value' => 'How do I sign in?')),
                        'actions' => array(array('label' => 'Go to sign in', 'url' => '/user/login')));
                }
                $goals = (array) $context['goals'];
                if (empty($goals)) {
                    return array('text' => "You don't have any savings goals yet. 🎯\n\nCreate one on the Savings goals page — give it a name, a target amount and an optional date, then add money whenever you like. You can track every goal's progress at a glance.",
                        'quick' => array(
                            array('label' => '💰 My balance', 'value' => 'What is my balance?'),
                            array('label' => '📊 My budget', 'value' => 'How is my spending this month?'),
                        ),
                        'actions' => array(array('label' => 'Create a goal', 'url' => '/goals')));
                }
                $totalSaved = 0; $totalTarget = 0; $lines = array(); $completed = 0;
                foreach ($goals as $g) {
                    $saved = (float) $g['saved_amount']; $target = (float) $g['target_amount'];
                    $totalSaved += $saved; $totalTarget += $target;
                    if ($g['status'] === 'completed') $completed++;
                    $pct = $target > 0 ? min(100, round(($saved / $target) * 100)) : 0;
                    $lines[] = "  • {$g['name']} — ".$this->money($saved, $currency).' of '.$this->money($target, $currency)." ({$pct}%)";
                }
                $overall = $totalTarget > 0 ? round(($totalSaved / $totalTarget) * 100) : 0;
                $summary = "Across ".count($goals)." goal".(count($goals) === 1 ? '' : 's')." you've saved ".$this->money($totalSaved, $currency)." of ".$this->money($totalTarget, $currency)." — that's {$overall}% of the way";
                if ($completed > 0) $summary .= ", and {$completed} goal".($completed === 1 ? ' is' : 's are')." already complete 🎉";
                return array('text' => $summary.".\n\n".implode("\n", $lines)."\n\nOpen Savings goals to add money, create a new goal or adjust a target.",
                    'quick' => array(
                        array('label' => '➕ Add money to a goal', 'value' => 'I want to add money to a goal'),
                        array('label' => '📊 My budget', 'value' => 'How is my spending this month?'),
                        array('label' => '💰 My balance', 'value' => 'What is my balance?'),
                    ),
                    'actions' => array(array('label' => 'Open Savings goals', 'url' => '/goals')));

            case 'budget':
                if (!$user) {
                    return array('text' => "Budget & Insights shows your income versus spending, breaks down where your money goes by category, and lets you set monthly limits — once you're signed in.",
                        'quick' => array(array('label' => '🔑 Sign in', 'value' => 'How do I sign in?')),
                        'actions' => array(array('label' => 'Go to sign in', 'url' => '/user/login')));
                }
                $m = $context['monthlySummary'];
                $income = (float) ($m['income'] ?? 0);
                $expenses = (float) ($m['expenses'] ?? 0);
                $net = $income - $expenses;
                $rate = $income > 0 ? round(($net / $income) * 100) : 0;
                $lines = array();
                foreach ((array) $context['spendingBreakdown'] as $row) {
                    $lines[] = "  • {$row['category']} — ".$this->money($row['total'], $currency);
                }
                $text = "Here's your money picture for ".date('F').":\n\n";
                $text .= "  Income:    ".$this->money($income, $currency)."\n";
                $text .= "  Spent:     ".$this->money($expenses, $currency)."\n";
                $text .= "  Net saved: ".$this->money(max(0, $net), $currency)." ({$rate}% savings rate)\n";
                if (!empty($lines)) {
                    $text .= "\nTop spending categories:\n".implode("\n", array_slice($lines, 0, 4));
                }
                if ($income > 0 && $net < 0) {
                    $text .= "\n\n⚠️ You're spending more than you've earned this month — consider setting a category limit on the Budget page.";
                } elseif ($rate >= 20) {
                    $text .= "\n\n💪 Great job — a savings rate of 20% or more puts you well ahead.";
                }
                return array('text' => $text,
                    'quick' => array(
                        array('label' => '🎯 My goals', 'value' => 'How are my savings goals?'),
                        array('label' => '📊 Recent activity', 'value' => 'Show my recent transactions'),
                        array('label' => '↗ Send money', 'value' => 'How do I send money?'),
                    ),
                    'actions' => array(array('label' => 'Open Budget', 'url' => '/budget')));

            case 'security':
                return array('text' => "Your security is our priority. We use 256-bit encryption, automatic session monitoring and secure verification codes. A few tips:\n\n• Never share your password or verification codes.\n• Only sign in through the official NorthWest website.\n• Freeze your card instantly if it's ever lost or stolen.\n• We will never ask for your password by phone, email or chat.\n\nIf you ever suspect fraud, contact our support team immediately.",
                    'quick' => array(array('label' => '🛟 Contact support', 'value' => 'I need help from support')),
                    'actions' => array(array('label' => 'Open Support', 'url' => '/support')));

            case 'fees':
                return array('text' => "Everyday banking at {$institution} is built around transparency. Sending money between NorthWest accounts is free, and your daily transfer limit is ".number_format((float) $limit).". There are no hidden monthly fees on standard personal accounts.",
                    'quick' => array(array('label' => '↗ Send money', 'value' => 'How do I send money?')));

            case 'open_account':
                return array('text' => "Opening an account is quick. Contact our team through the Support page and we'll guide you through the secure onboarding process, including identity verification (KYC). Once approved you can bank online right away.",
                    'quick' => array(array('label' => '🛟 Talk to support', 'value' => 'I need help from support')),
                    'actions' => array(array('label' => 'Open Support', 'url' => '/support')));

            case 'signin':
                if ($user) {
                    return array('text' => "You're already signed in as {$first}. You can use the navigation menu to move around your accounts.",
                        'quick' => array(array('label' => '💰 My balance', 'value' => 'What is my balance?')));
                }
                return array('text' => "To sign in, use the \"Sign in\" button on the welcome page. You'll verify an auto-generated code, then enter your account number or email and your password.",
                    'quick' => array(array('label' => '🔑 Sign in', 'value' => 'How do I sign in?')),
                    'actions' => array(array('label' => 'Go to sign in', 'url' => '/login')));

            case 'support':
                return array('text' => "I can help with most things, and for anything else our support team is one message away. You can open a support ticket from the Support page, or email {$support}.\n\nPlease include your account reference so we can help you faster.",
                    'quick' => array(
                        array('label' => '🛟 Open Support', 'value' => 'Take me to Support'),
                        array('label' => '🔐 Security', 'value' => 'How is my account secure?'),
                    ),
                    'actions' => array(array('label' => 'Open Support', 'url' => $user ? '/support' : '/login')));

            case 'thanks':
                return array('text' => "You're very welcome".($user ? ", {$first}" : '')."! 😊 Is there anything else I can help you with today?",
                    'quick' => array(
                        array('label' => '💰 My balance', 'value' => 'What is my balance?'),
                        array('label' => '🛟 Support', 'value' => 'I need help from support'),
                    ));
        }
        return NULL;
    }

    protected function fallbackReply()
    {
        return array(
            'text' => "I'm not sure I understood that. I can help with your balance, savings goals, budget and spending, transactions, transfers, cards, loans, fees and security — or connect you with the support team.\n\nTry one of the quick options below, or just rephrase your question.",
            'quick' => array(
                array('label' => '💰 My balance', 'value' => 'What is my balance?'),
                array('label' => '🎯 Savings goals', 'value' => 'How are my savings goals?'),
                array('label' => '📊 My budget', 'value' => 'How is my spending this month?'),
                array('label' => '↗ Send money', 'value' => 'How do I send money?'),
            ),
            'actions' => array(),
        );
    }

    protected function money($amount, $currency = 'USD')
    {
        $symbols = array('USD' => '$', 'EUR' => '€', 'GBP' => '£');
        return ($symbols[$currency] ?? $currency.' ').number_format((float) $amount, 2);
    }
}
