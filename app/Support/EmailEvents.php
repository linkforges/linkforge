<?php

namespace App\Support;

/**
 * The catalogue of transactional emails the app can send. Each event ships a
 * default subject/body (with {{ placeholder }} tokens) and is on by default.
 * Operators override the copy and toggle events from admin → Settings → Email,
 * which persists to the email_templates table; absent rows use these defaults.
 */
class EmailEvents
{
    /** Always available in every template. */
    public const GLOBAL_PLACEHOLDERS = ['app_name', 'app_url'];

    /**
     * event => [label, description, audience, subject, body, action_text, placeholders].
     * `action_text` (when set) renders a button to the {{ action_url }} supplied at send time.
     */
    public const EVENTS = [
        'welcome' => [
            'label' => 'Welcome',
            'description' => 'Sent to a new customer right after they sign up.',
            'audience' => 'Customer',
            'subject' => 'Welcome to {{ app_name }}',
            'body' => "Hi {{ name }},\n\nThanks for joining {{ app_name }}. Your account is ready to go.\n\nYou can start shortening links, building bio pages, generating QR codes and tracking analytics right away.\n\nIf you ever need a hand, just open a support ticket from your dashboard.",
            'action_text' => 'Go to your dashboard',
            'placeholders' => ['name', 'email'],
        ],
        'ticket_opened' => [
            'label' => 'Ticket opened',
            'description' => 'Confirms to a customer that their support ticket was received.',
            'audience' => 'Customer',
            'subject' => 'We received your ticket #{{ ticket_id }}',
            'body' => "Hi {{ name }},\n\nThanks for getting in touch. We've received your ticket and our team will reply as soon as possible.\n\nTicket: {{ ticket_subject }}\nReference: #{{ ticket_id }}",
            'action_text' => 'View ticket',
            'placeholders' => ['name', 'ticket_id', 'ticket_subject'],
        ],
        'ticket_reply' => [
            'label' => 'New reply from support',
            'description' => 'Notifies a customer when a staff member replies to their ticket.',
            'audience' => 'Customer',
            'subject' => 'New reply on your ticket #{{ ticket_id }}',
            'body' => "Hi {{ name }},\n\nOur support team has replied to your ticket \"{{ ticket_subject }}\".\n\nOpen the ticket to read the reply and respond.",
            'action_text' => 'View reply',
            'placeholders' => ['name', 'ticket_id', 'ticket_subject'],
        ],
        'subscription_activated' => [
            'label' => 'Subscription activated',
            'description' => 'Sent when a customer starts or upgrades a paid plan.',
            'audience' => 'Customer',
            'subject' => "You're now on the {{ plan_name }} plan",
            'body' => "Hi {{ name }},\n\nYour subscription to the {{ plan_name }} plan is now active. Thank you for your support!\n\nAmount: {{ amount }}\n\nYou now have access to everything included in {{ plan_name }}.",
            'action_text' => 'Manage billing',
            'placeholders' => ['name', 'plan_name', 'amount'],
        ],
        'subscription_canceled' => [
            'label' => 'Subscription canceled',
            'description' => 'Sent when a subscription is canceled and the account returns to Free.',
            'audience' => 'Customer',
            'subject' => 'Your {{ app_name }} subscription was canceled',
            'body' => "Hi {{ name }},\n\nYour subscription has been canceled and your account has moved to the Free plan.\n\nWe're sorry to see you go. You can re-subscribe any time from your billing page.",
            'action_text' => 'View plans',
            'placeholders' => ['name', 'plan_name'],
        ],
        'payment_refunded' => [
            'label' => 'Payment refunded',
            'description' => 'Sent when a payment is refunded.',
            'audience' => 'Customer',
            'subject' => 'Your refund has been processed',
            'body' => "Hi {{ name }},\n\nA refund of {{ amount }} has been processed for your {{ app_name }} account.\n\nPlease allow a few business days for it to appear on your statement.",
            'placeholders' => ['name', 'amount'],
        ],
        'admin_new_ticket' => [
            'label' => 'New ticket (staff alert)',
            'description' => 'Notifies your team when a customer opens a new ticket.',
            'audience' => 'Staff',
            'subject' => 'New support ticket: {{ ticket_subject }}',
            'body' => "A new support ticket has been opened.\n\nFrom: {{ customer_name }} ({{ customer_email }})\nSubject: {{ ticket_subject }}\nPriority: {{ priority }}\nReference: #{{ ticket_id }}",
            'action_text' => 'Open in admin',
            'placeholders' => ['customer_name', 'customer_email', 'ticket_id', 'ticket_subject', 'priority'],
        ],
        'admin_new_user' => [
            'label' => 'New signup (staff alert)',
            'description' => 'Notifies your team when a new customer registers.',
            'audience' => 'Staff',
            'subject' => 'New signup: {{ customer_email }}',
            'body' => "A new customer just signed up for {{ app_name }}.\n\nName: {{ customer_name }}\nEmail: {{ customer_email }}",
            'action_text' => 'View customer',
            'placeholders' => ['customer_name', 'customer_email'],
        ],
    ];

    /** All placeholder tokens available to an event (global + event-specific). */
    public static function placeholders(string $event): array
    {
        return array_merge(self::GLOBAL_PLACEHOLDERS, self::EVENTS[$event]['placeholders'] ?? []);
    }
}
