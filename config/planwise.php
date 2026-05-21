<?php

/*
 * When a vendor holds module key X, they also implicitly hold all modules
 * listed under X's equivalences. Used by the planwise middleware so that
 * e.g. a laundry-plan vendor can access routes gated by planwise:hr_manage
 * or planwise:inventory_manage without requiring a separate subscription.
 */
return [
    'equivalences' => [
        'laundry' => [
            'hr_manage',
            'inventory_manage',
            'account_manage',
        ],
        'hospital_manage' => [
            'hr_manage',
        ],
    ],

    /*
     * Modules that are always free (no subscription required) for a given business_type.
     * Key = business_type value (lowercase), value = list of module keys.
     */
    'free_by_business_type' => [
        'laundry' => [
            'inventory_manage',
        ],
    ],
];
