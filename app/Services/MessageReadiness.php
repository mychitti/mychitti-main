<?php

namespace App\Services;

/**
 * Whether an automatic message can actually reach a customer right now, and if not, the one thing
 * that would fix it.
 *
 * There were four implementations of this before: the settings page computed part of it inline,
 * NotificationSettingController::templateWarning() computed it again on toggle, and both
 * HmisWhatsAppShare::auto() and InvoiceShare::auto() computed it a third and fourth time at the
 * moment of sending. Four answers to one question is how a screen ends up saying a message is on
 * while the send path quietly disagrees.
 *
 * The order of the checks is the order the send paths already used, so nothing changes about what
 * sends — only about who gets told why it didn't.
 */
class MessageReadiness
{
    const LIVE              = 'live';
    const OFF               = 'off';
    const NOT_CONNECTED     = 'not_connected';
    const NO_SUBSCRIPTION   = 'no_subscription';
    const TEMPLATE_MISSING  = 'template_missing';
    const TEMPLATE_PENDING  = 'template_pending';
    const TEMPLATE_REJECTED = 'template_rejected';

    /** Per-request memo — a settings page asks this twenty times for one store. */
    protected static array $storeCache = [];

    /**
     * The store-wide preconditions, resolved once.
     *
     * An unreadable template list means "unknown", never "not approved" — a Graph blip must not
     * make every message on the page look broken.
     */
    public static function store(int $storeId): array
    {
        if (isset(static::$storeCache[$storeId])) {
            return static::$storeCache[$storeId];
        }

        $connected = false;
        $active    = false;
        $statuses  = [];

        try {
            $connected = WhatsAppService::make($storeId)->source() === 'vendor';
        } catch (\Throwable $e) {
            $connected = false;
        }

        if ($connected) {
            try {
                $active = WhatsAppBilling::isActive($storeId);
            } catch (\Throwable $e) {
                $active = false;
            }

            try {
                $statuses = WhatsAppService::templateStatuses($storeId);
            } catch (\Throwable $e) {
                $statuses = [];
            }
        }

        return static::$storeCache[$storeId] = [
            'connected'          => $connected,
            'subscription'       => $active,
            'statuses'           => $statuses,
            'statuses_readable'  => $statuses !== [],
        ];
    }

    /** True when the store is set up enough for any automatic message to go out at all. */
    public static function storeReady(int $storeId): bool
    {
        $store = static::store($storeId);
        return $store['connected'] && $store['subscription'];
    }

    /**
     * Full state for one message.
     *
     * $key is the NotificationPrefs item key ('hmis_prescription_pdf'), $group its group
     * ('whatsapp_send'). Returns everything a row needs to render itself, including the single
     * action that resolves whatever is wrong.
     */
    public static function for(int $storeId, string $group, string $key): array
    {
        $item = NotificationPrefs::GROUPS[$group]['items'][$key] ?? [];
        $label = $item['label'] ?? $key;
        $suggested = $item['template'] ?? null;

        $enabled = NotificationPrefs::enabled($storeId, $group, $key);
        $store   = static::store($storeId);

        $role     = $suggested ? static::roleFor($suggested) : null;
        $template = $suggested;
        $bound    = false;

        if ($suggested && $store['connected']) {
            $template = WhatsAppService::effectiveTemplateName($storeId, $suggested);
            $bound    = strtolower($template) !== strtolower($suggested);
        }

        $base = [
            'key'       => $key,
            'group'     => $group,
            'label'     => $label,
            'enabled'   => $enabled,
            'suggested' => $suggested,
            'template'  => $template,
            'role'      => $role,
            'bound'     => $bound,
        ];

        // Store-wide problems come first: they are true of every message, and telling a vendor to
        // fix a template on an account they have not connected sends them down the wrong path.
        if (!$store['connected']) {
            return $base + static::state(self::NOT_CONNECTED, 'Not connected',
                'Connect your own WhatsApp number before this can send.',
                'Connect WhatsApp', static::url('vendor.whatsapp.connect')) + ['warning' => null];
        }

        if (!$store['subscription']) {
            return $base + static::state(self::NO_SUBSCRIPTION, 'No subscription',
                'Your WhatsApp subscription is not active, so nothing is sending.',
                'Activate', static::url('vendor.whatsapp.billing')) + ['warning' => null];
        }

        // A template problem is worth showing even while the toggle is off — it is what would bite
        // the moment they turn it on. But it does not become the row's headline: a vendor scanning
        // the list needs "Off" to mean off, or they cannot tell what their store is actually doing.
        $templateState = static::templateState($store, $template);

        if (!$enabled) {
            return $base + static::state(self::OFF, 'Off',
                'Turned off — this message is not being sent.', null, null)
                + ['warning' => $templateState];
        }

        if ($templateState) {
            return $base + $templateState + ['warning' => null];
        }

        return $base + static::state(self::LIVE, 'Live',
            'Sending automatically.', null, null) + ['warning' => null];
    }

    /** Why this template cannot carry a message, or null when it can (or cannot be judged). */
    protected static function templateState(array $store, ?string $template): ?array
    {
        if (!$template || !$store['statuses_readable']) {
            return null;
        }

        $status = $store['statuses'][strtolower($template)] ?? null;

        if ($status === 'APPROVED') {
            return null;
        }

        if ($status === null) {
            return static::state(self::TEMPLATE_MISSING, 'Needs a template',
                'The "' . $template . '" template is not on your WhatsApp account yet.',
                'Create it', static::url('vendor.whatsapp.templates') . '#tplPresets');
        }

        if ($status === 'PENDING') {
            return static::state(self::TEMPLATE_PENDING, 'In review',
                '"' . $template . '" is with Meta for review — sending starts by itself once it is approved.',
                'View', static::url('vendor.whatsapp.templates'));
        }

        return static::state(self::TEMPLATE_REJECTED, 'Template ' . strtolower($status),
            '"' . $template . '" is ' . $status . ' at Meta, so this cannot send.',
            'Fix it', static::url('vendor.whatsapp.templates'));
    }

    /**
     * Can this message go out right now? The gate the send paths use, so a row that reads "Live"
     * and a send that actually happens can never disagree.
     */
    public static function canSend(int $storeId, string $group, string $key): bool
    {
        return static::for($storeId, $group, $key)['state'] === self::LIVE;
    }

    /** The automation role that owns a suggested template, so a row can offer to swap it. */
    public static function roleFor(string $template): ?string
    {
        foreach (WhatsAppService::TEMPLATE_ROLES as $role => $meta) {
            if (strtolower($meta['default'] ?? '') === strtolower($template)) {
                return $role;
            }
        }

        return null;
    }

    /** Chip colour for a state, so every screen paints them the same. */
    public static function tone(string $state): string
    {
        return [
            self::LIVE              => 'success',
            self::OFF               => 'secondary',
            self::TEMPLATE_PENDING  => 'info',
            self::NOT_CONNECTED     => 'danger',
            self::NO_SUBSCRIPTION   => 'danger',
            self::TEMPLATE_MISSING  => 'warning',
            self::TEMPLATE_REJECTED => 'danger',
        ][$state] ?? 'secondary';
    }

    /** Forget the memo — for a request that changes a binding and then re-reads the state. */
    public static function forget(?int $storeId = null): void
    {
        if ($storeId === null) {
            static::$storeCache = [];
            return;
        }
        unset(static::$storeCache[$storeId]);
    }

    protected static function state(string $state, string $chip, string $reason, ?string $action, ?string $url): array
    {
        return [
            'state'  => $state,
            'chip'   => $chip,
            'tone'   => static::tone($state),
            'reason' => $reason,
            'action' => $action,
            'url'    => $url,
        ];
    }

    /** Routes differ between panels and a missing one must never take a settings page down. */
    protected static function url(string $name): ?string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
