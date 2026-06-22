<?php

/*
 * When a vendor holds module key X, they also implicitly hold all modules
 * listed under X's equivalences. Used by the planwise middleware so that
 * e.g. a laundry-plan vendor can access routes gated by planwise:hr_manage
 * or planwise:inventory_manage without requiring a separate subscription.
 */
return [
    /*
     * Premium addon modules that require a purchase/licence.
     * Core modules (banner, category, zone, etc.) are always available.
     * active_addon_modules in business_settings controls which are enabled.
     * If active_addon_modules is not set in DB, ALL addon modules are shown
     * (backward-compatible default).
     */
    'addon_modules' => [
        'billing', 'service_billing', 'quotaiton_manage',
        'support_ticket', 'sales_crm', 'ai_agent',
        'task_manage', 'projects_manage', 'hr_manage',
        'account_manage', 'attendance', 'inventory_manage',
        'leads_manage', 'client_manage', 'analytics', 'logs', 'pos',
        'pos_retail',
    ],

    'equivalences' => [
        'laundry' => [
            'hr_manage',
            'inventory_manage',
            'account_manage',
        ],
        'hospital_manage' => [
            'hr_manage',
        ],
        // A Retail POS plan unlocks billing + inventory + accounts as one bundle.
        'pos_retail' => [
            'pos',
            'inventory_manage',
            'account_manage',
        ],
    ],

    /*
     * Modules that are always free (no subscription required) for a given business_type.
     * Key = business_type value (lowercase), value = list of module keys.
     */
    'free_by_business_type' => [
        'laundry' => [
            'laundry',
            'inventory_manage',
        ],
        'hospital' => [
            'hospital',
            'hospital_manage',
            'leads_manage',
        ],
        // Retail POS (`pos_retail`) is a PAID plan, so `pos_retail` itself is NOT free here — stores
        // must hold an active subscription to use the Retail POS module. But basic Inventory is free
        // for pos_retail stores (manage stock without a separate inventory subscription).
        'pos_retail' => [
            'inventory_manage',
        ],
    ],
];
