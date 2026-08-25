<?php

return [

    /*
    |----------------------------------------------------------------------
    | AI auto-approval
    |----------------------------------------------------------------------
    |
    | What VerifyCatalogSuggestions is allowed to promote into the shared
    | pool without a human. Everything below a threshold still lands in the
    | admin Suggestions queue exactly as before — these settings only decide
    | how much of that queue never needs opening.
    |
    | The pool is read by every hospital, so the thresholds are deliberately
    | high: a confidently wrong record propagates to all of them.
    |
    */

    'auto_approve' => [

        // Master switch. Off = the previous behaviour, every promotion is manual.
        'enabled' => env('CATALOG_AUTO_APPROVE', true),

        // A "new" verdict at or above this confidence becomes a pool record.
        'new_min_confidence' => (float) env('CATALOG_AUTO_APPROVE_CONFIDENCE', 0.95),

        // How many stores must have independently asked for it first. 1 = the
        // first store to type it is enough; 2 makes a second store corroborate.
        'new_min_requests' => (int) env('CATALOG_AUTO_APPROVE_MIN_REQUESTS', 1),

        // A "duplicate" verdict at or above this confidence is filed against the
        // row the model matched. Safer than creating: it links to a curated
        // record rather than inventing one, and a wrong merge is reversible
        // while the store row is still the only thing pointing at it.
        'merge_min_confidence' => (float) env('CATALOG_AUTO_MERGE_CONFIDENCE', 0.92),

        // Never auto-create a record with no dosage form — "Paracetamol" with no
        // Tablet/Syrup is exactly the vague entry a human should look at.
        'require_form' => env('CATALOG_AUTO_APPROVE_REQUIRE_FORM', true),
    ],

];
