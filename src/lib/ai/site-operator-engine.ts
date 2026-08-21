/**
 * site-operator-engine.ts
 * -----------------------------------------------------------------------------
 * NorthWest In-Site Operating AI Assistant — canonical engine definition.
 *
 * This is a 100% local, offline knowledge & operational engine. It requires
 * ZERO external API keys and makes ZERO third-party network calls. Every
 * response is generated from built-in embedded knowledge plus the user's
 * own banking data (balance, transactions, accounts, limits) provided by the
 * host application.
 *
 * The server runtime of the app uses an equivalent PHP implementation
 * (`application/libraries/Site_operator_engine.php`) that mirrors the exact
 * intent table below. Keep both files in sync.
 */

export type ChatUser = {
  id: number;
  role: 'customer' | 'admin';
  first_name: string;
  last_name: string;
  email?: string;
};

export type BankContext = {
  institutionName?: string;
  supportEmail?: string;
  defaultCurrency?: string;
  dailyTransferLimit?: number;
  totalBalance?: number;
  accounts?: Array<{ name: string; account_number: string; available_balance: number; type: string; is_primary: number }>;
  transactions?: Array<{ description: string; category: string; amount: number; type: string; transaction_date: string }>;
};

export type QuickReply = { label: string; value: string };

export type EngineReply = {
  text: string;
  quick: QuickReply[];
  /** Optional navigation action(s) the client can offer, e.g. [{label,url}] */
  actions?: Array<{ label: string; url: string }>;
};

/**
 * intent table: each entry carries keyword aliases, a callback that builds the
 * reply from context, and quick-reply suggestions.
 */
const INTENTS: Array<{
  id: string;
  keywords: RegExp;
  handler: (u: ChatUser | null, c: BankContext) => EngineReply;
}> = [
  {
    id: 'greeting',
    keywords: /^(hi|hello|hey|good (morning|afternoon|evening)|yo|howdy)\b/i,
    handler: (u, c) => ({
      text: u
        ? `Hello ${u.first_name} 👋 Welcome to ${c.institutionName || 'NorthWest'}. I can check your balance, show recent activity, explain transfers, cards, loans and security, and guide you to support. What would you like help with?`
        : `Hello 👋 Welcome to ${c.institutionName || 'NorthWest'}. I can help you learn about our services, transfers, cards, loans, fees and security. To see live account info, please sign in first.`,
      quick: u ? [
        { label: '💰 My balance', value: 'What is my balance?' },
        { label: '📊 Recent activity', value: 'Show my recent transactions' },
        { label: '↗ Send money', value: 'How do I send money?' },
        { label: '🛟 Support', value: 'I need help from support' },
      ] : [
        { label: '🏦 Open an account', value: 'How do I open an account?' },
        { label: '💳 Cards', value: 'How do card controls work?' },
        { label: '🔐 Security', value: 'How is my account secure?' },
      ],
    }),
  },
  {
    id: 'balance',
    keywords: /(balance|how much (money|do i have|is in)|total (balance|funds)|account balance|funds|my money)/i,
    handler: (u, c) => {
      if (!u || c.totalBalance === undefined) {
        return {
          text: `Your current available balance is shown on your dashboard. Please sign in to see your live balance.`,
          quick: [{ label: '🔑 Sign in', value: 'How do I sign in?' }],
        };
      }
      const lines = (c.accounts || []).map(
        (a) => `  • ${a.name} (•••• ${String(a.account_number).slice(-4)}) — ${fmtMoney(a.available_balance, c.defaultCurrency)}`
      );
      return {
        text: `Here is your current financial position:\n\nTotal available balance: ${fmtMoney(c.totalBalance, c.defaultCurrency)}\n\n${lines.join('\n')}\n\nYou can send money or view full details from the dashboard.`,
        quick: [
          { label: '📊 Recent activity', value: 'Show my recent transactions' },
          { label: '↗ Send money', value: 'How do I send money?' },
        ],
        actions: [{ label: 'Open dashboard', url: '/dashboard' }],
      };
    },
  },
  {
    id: 'transactions',
    keywords: /(transaction|activity|history|recent (moves|spending|payments)|statement|where did i spend|what did i buy)/i,
    handler: (u, c) => {
      const tx = c.transactions || [];
      if (!u || tx.length === 0) {
        return {
          text: `Your most recent account activity appears on the dashboard and Transactions page. Please sign in to see it.`,
          quick: [{ label: '📊 Transactions', value: 'Show my recent transactions' }],
        };
      }
      const lines = tx.slice(0, 5).map(
        (t) => `  ${t.type === 'credit' ? '+' : '−'} ${fmtMoney(t.amount, c.defaultCurrency)} — ${t.description} (${t.category})`
      );
      return {
        text: `Here are your ${tx.length} most recent transactions:\n\n${lines.join('\n')}\n\nFor the full list, open the Transactions page.`,
        quick: [
          { label: '↗ Send money', value: 'How do I send money?' },
          { label: '💳 Cards', value: 'How do card controls work?' },
        ],
        actions: [{ label: 'View all transactions', url: '/transactions' }],
      };
    },
  },
  {
    id: 'transfer',
    keywords: /(transfer|send money|pay someone|send (to|money)|recipient|beneficiar|wire|make a payment|how do i send)/i,
    handler: (u) => ({
      text: `You can send money from the "Send money" page.\n\n1. Pick the account to send from.\n2. Choose a saved beneficiary or enter the recipient's name, account number and bank.\n3. Enter the amount and (optionally) a note.\n4. Review and submit — transfers are processed with end-to-end encryption.\n\nThe daily transfer limit is ${c?.dailyTransferLimit ?? '25,000'}.`,
      quick: [
        { label: '↗ Go to Send money', value: 'Take me to Send money' },
        { label: '👥 Beneficiaries', value: 'How do I add a beneficiary?' },
      ],
      actions: [{ label: 'Open Send money', url: '/transfer' }],
    }),
  },
  {
    id: 'beneficiary',
    keywords: /(beneficiar|add a payee|save a recipient|recipient list)/i,
    handler: () => ({
      text: `To add a beneficiary, open the "Beneficiaries" page and use the "Add beneficiary" option. You'll need the recipient's name, account number and bank name. Saved beneficiaries make future transfers faster and safer.`,
      quick: [{ label: '↗ Send money', value: 'How do I send money?' }],
      actions: [{ label: 'Manage beneficiaries', url: '/beneficiaries' }],
    }),
  },
  {
    id: 'card',
    keywords: /(card|freeze|block|unfreeze|limit|cvv|pin|card controls|virtual card)/i,
    handler: () => ({
      text: `From the "Cards" page you can view your cards and toggle card controls such as: freezing a card instantly, enabling online payments, and international use. Freezing a card immediately blocks new transactions while keeping your account intact.`,
      quick: [
        { label: '💳 Manage cards', value: 'Show me my card options' },
        { label: '🔐 Security', value: 'How is my account secure?' },
      ],
      actions: [{ label: 'Open Cards', url: '/cards' }],
    }),
  },
  {
    id: 'loan',
    keywords: /(loan|borrow|credit|mortgage|finance)/i,
    handler: () => ({
      text: `You can apply for a personal loan directly from the "Loans" page. Loans are offered with competitive fixed rates, clear monthly payments and no hidden fees. Open Loans to see your available options and estimated repayments.`,
      quick: [{ label: '▥ View loans', value: 'Tell me more about loans' }],
      actions: [{ label: 'Open Loans', url: '/loans' }],
    }),
  },
  {
    id: 'security',
    keywords: /(secure|security|safe|protect|phishing|fraud|hack|password|otp|2fa|two factor|verification code)/i,
    handler: () => ({
      text: `Your security is our priority. We use 256-bit encryption, automatic session monitoring and secure verification codes. A few tips:\n\n• Never share your password or verification codes.\n• Only sign in through the official NorthWest website.\n• Freeze your card instantly if it's ever lost or stolen.\n• We will never ask for your password by phone, email or chat.\n\nIf you ever suspect fraud, contact our support team immediately.`,
      quick: [{ label: '🛟 Contact support', value: 'I need help from support' }],
      actions: [{ label: 'Open Support', url: '/support' }],
    }),
  },
  {
    id: 'fees',
    keywords: /(fee|charge|cost|limit|how much does it cost|free)/i,
    handler: (u, c) => ({
      text: `Everyday banking at ${c.institutionName || 'NorthWest'} is built around transparency. Sending money between NorthWest accounts is free, and your daily transfer limit is ${c.dailyTransferLimit ?? '25,000'}. There are no hidden monthly fees on standard personal accounts.`,
      quick: [{ label: '↗ Send money', value: 'How do I send money?' }],
    }),
  },
  {
    id: 'open_account',
    keywords: /(open (an|a)? ?account|new account|create account|become a customer|register|join)/i,
    handler: () => ({
      text: `Opening an account is quick. Contact our team through the Support page and we'll guide you through the secure onboarding process, including identity verification (KYC). Once approved you can bank online right away.`,
      quick: [{ label: '🛟 Talk to support', value: 'I need help from support' }],
      actions: [{ label: 'Open Support', url: '/support' }],
    }),
  },
  {
    id: 'signin',
    keywords: /(sign ?in|log ?in|login|password|forgot)/i,
    handler: (u) =>
      u
        ? { text: `You're already signed in as ${u.first_name}. You can use the navigation menu to move around your accounts.`, quick: [{ label: '💰 My balance', value: 'What is my balance?' }] }
        : {
            text: `To sign in, use the "Sign in" button on the welcome page. You'll verify an auto-generated code, then enter your account number or email and your password.`,
            quick: [{ label: '🔑 Sign in', value: 'How do I sign in?' }],
            actions: [{ label: 'Go to sign in', url: '/login' }],
          },
  },
  {
    id: 'support',
    keywords: /(support|help|human|agent|talk to|contact|complaint|reach|representative|phone|email)/i,
    handler: (u, c) => ({
      text: `I can help with most things, and for anything else our support team is one message away. You can open a support ticket from the Support page, or email ${c.supportEmail || 'support@northwest'}.\n\nPlease include your account reference so we can help you faster.`,
      quick: [
        { label: '🛟 Open Support', value: 'Take me to Support' },
        { label: '🔐 Security', value: 'How is my account secure?' },
      ],
      actions: [{ label: 'Open Support', url: u ? '/support' : '/login' }],
    }),
  },
  {
    id: 'thanks',
    keywords: /(thank|thanks|thx|great|awesome|nice|good (job|work)|appreciate)/i,
    handler: (u) => ({
      text: `You're very welcome${u ? `, ${u.first_name}` : ''}! 😊 Is there anything else I can help you with today?`,
      quick: [
        { label: '💰 My balance', value: 'What is my balance?' },
        { label: '🛟 Support', value: 'I need help from support' },
      ],
    }),
  },
];

/** fallback reply used when no intent matches */
const fallbackReply = (): EngineReply => ({
  text: `I'm not sure I understood that. I can help with your balance, transactions, transfers, cards, loans, fees and security — or connect you with the support team.\n\nTry one of the quick options below, or just rephrase your question.`,
  quick: [
    { label: '💰 My balance', value: 'What is my balance?' },
    { label: '↗ Send money', value: 'How do I send money?' },
    { label: '🔐 Security', value: 'How is my account secure?' },
    { label: '🛟 Support', value: 'I need help from support' },
  ],
});

/** money formatter used by replies */
function fmtMoney(n: number, currency = 'USD'): string {
  const symbols: Record<string, string> = { USD: '$', EUR: '€', GBP: '£' };
  const symbol = symbols[currency] ?? `${currency} `;
  return symbol + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * Main entrypoint — given a raw user message, the (optional) signed-in user and
 * the host-provided banking context, returns a fully local reply.
 */
export function siteOperatorReply(message: string, user: ChatUser | null, context: BankContext = {}): EngineReply {
  const text = String(message || '').trim();
  if (!text) return fallbackReply();
  for (const intent of INTENTS) {
    if (intent.keywords.test(text)) {
      try {
        return intent.handler(user, context);
      } catch {
        return fallbackReply();
      }
    }
  }
  return fallbackReply();
}
