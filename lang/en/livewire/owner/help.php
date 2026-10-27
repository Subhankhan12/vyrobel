<?php

// resources/views/livewire/owner/help.blade.php — the owner Help & support page (Ask Vyrobel hero, contact cards, FAQ).

return [
    'title' => 'Help & support',
    'subtitle' => 'Answers, and how to reach your management team',
    'ask_heading' => 'Ask Vyrobel',
    'ask_intro' => 'Ask about using Vyrobel — grounded in our help. For anything specific to your account, message your management team.',
    'ask_placeholder' => 'Ask about payouts, statements, tax…',
    'ask_aria' => 'Ask Vyrobel a question',
    'ask_button' => 'Ask',
    // suggestion pills: clicking one fills the question box with its text
    'suggest_paid' => 'When will I get paid?',
    'suggest_statement' => 'How do I read my statement?',
    'suggest_tax' => 'What are my tax obligations?',
    'suggest_block' => 'Can I block dates?',
    'ai_note' => 'Ask Vyrobel is an AI assistant — it answers from Vyrobel’s help. Verify important things, and message your team for anything account-specific.',
    // the answer card, after a question was asked
    'fallback_cost_capped' => 'You’ve reached today’s question limit. Here’s the relevant help — or message your team.',
    'fallback_unavailable' => 'I couldn’t generate a summary just now. Here’s the relevant help from Vyrobel — or message your team.',
    'answer_eyebrow' => 'Ask Vyrobel',
    'you_asked' => 'You asked: “:question”',
    'escalate' => 'I don’t have that in Vyrobel’s help material. Your management team can help with this — message them below and they’ll pick it up in your inbox.',
    'sources' => 'Sources',
    // the subject of the message this button starts in the owner↔operator inbox
    'message_subject' => 'Question from Help',
    'message_team_button' => 'Message your team about this',
    'ai_note_short' => 'AI assistant — verify important things; message the team for account help.',
    // contact cards
    'message_manager' => 'Message your manager',
    'message_manager_sub' => 'In-app — your team replies in your inbox, with full history',
    'open_messages_arrow' => 'Open messages →',
    'phone_heading' => 'Prefer phone or email?',
    'phone_body' => 'Your management team’s direct phone and email are on your welcome pack and your latest statement. For anything account-specific, messaging keeps it in one place with your history.',
    'support_eyebrow' => 'How support works',
    'support_line' => 'Your operator is your first line of support',
    'support_sub' => 'Message them from your inbox and the thread keeps its full history — no ticket number to track.',
    // FAQ
    'faq_heading' => 'Owner FAQs',
    'faq_go_to' => 'Go to :page →',
    'faq_no_match' => 'No FAQ matches “<span x-text="q"></span>”. <a href=":url" style="color:var(--vy-accent);font-weight:600">Message your manager →</a>',
    // right rail
    'still_stuck' => 'Still stuck?',
    'still_stuck_body' => 'Messaging is your tracked channel — every request stays in your inbox with your management team’s replies and full history. There’s no separate ticket queue.',
    'open_messages' => 'Open messages',
    'jump_to' => 'Jump to',
    'jump_payouts' => 'Payouts',
    'jump_statements' => 'Statements',
    'jump_tax' => 'Tax center',
    'jump_payout_method' => 'Payout method',
    'jump_agreement' => 'Agreement',
];
