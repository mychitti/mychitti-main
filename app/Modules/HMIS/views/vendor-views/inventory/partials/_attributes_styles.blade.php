{{-- Styling for the item form's Attributes tab: the Base Unit box, Custom Attributes and
     Description Attributes.

     Written as one block, included by every item add/edit view, because these three panels had
     drifted into three different looks — one blue dashed, one cream dashed, one plain — with rows
     that hugged the left edge of a half-empty panel.

     Kept in the view rather than inventory_management.css on purpose: that stylesheet is not
     loaded on the item form at all, so rules added there never applied. --}}
<style>
    /* ── Panels ──
       Backgrounds and dashed borders are left exactly as they were (set inline on each panel):
       the tint is how the two are told apart at a glance. Only the padding is evened up. */
    .product_elem.custom_attributes,
    .product_elem.desc-attr-panel {
        padding: 16px 18px !important;
    }

    /* A real gutter between the two panels. They are col-md-6 (50% each), so the width has to
       come down by the same amount the margin adds or the second one wraps. */
    @media (min-width: 768px) {
        .product_elem.custom_attributes,
        .product_elem.desc-attr-panel {
            flex: 0 0 calc(50% - 9px) !important;
            max-width: calc(50% - 9px) !important;
        }
        .product_elem.custom_attributes { margin-right: 18px !important; }
    }
    .attr-panel-title,
    .custom_attributes > .mb-2 > label,
    .custom_attributes > .mb-3 > label,
    .desc-attr-panel label.font-weight-bold {
        font-size: 12.5px !important;
        font-weight: 700 !important;
        color: #344054 !important;
        letter-spacing: .01em;
        margin-bottom: 8px !important;
    }
    .attr-panel-hint { font-size: 11.5px; color: #98a2b3; margin: 0 0 10px; line-height: 1.45; }

    /* ── Chips (+ Serial No, + Expiry Date …) ── */
    .custom_attributes .custom-header-btn {
        border-radius: 999px !important;
        font-size: 11.5px !important;
        font-weight: 600 !important;
        padding: 3px 12px !important;
        margin: 0 6px 6px 0 !important;
    }

    /* ── Attribute rows: fill the panel, label column then value ──
       The row markup varies (a label + flex div for a named chip, two inputs for "Other"), so
       both shapes are handled rather than assuming one. */
    #custom-fields .form-group,
    #custom-fields .custom-field {
        display: flex !important;
        align-items: center !important;
        width: 100% !important;
        max-width: 100% !important;
        gap: 12px;
        background: rgba(255,255,255,.72) !important;
        border: 1px solid rgba(0,0,0,.06) !important;
        border-radius: 9px !important;
        padding: 7px 10px !important;
        margin-bottom: 12px !important;
    }
    /* Label and value share the row evenly, so a named row lines up with an "Other" row (two
       inputs) and with the Attribute/Value pairs in the Description panel beside it. */
    #custom-fields .form-group > label,
    #custom-fields .custom-field > label {
        flex: 1 1 50%;
        width: 50% !important;
        margin: 0 !important;
        font-size: 12.5px;
        font-weight: 600;
        color: #56606e;
    }
    #custom-fields .form-group > div,
    #custom-fields .custom-field > div {
        display: flex !important;
        align-items: center;
        gap: 12px;
        flex: 1 1 50%;
        min-width: 0;
        margin: 0 !important;
    }
    #custom-fields input.form-control {
        flex: 1 1 auto;
        width: auto !important;
        min-width: 0;
        height: 34px !important;
        font-size: 12.5px;
        border-radius: 7px;
        border-color: #e3e7ef;
        margin: 0 !important;
        padding: 4px 10px !important;
    }
    #custom-fields .remove-field { flex: 0 0 auto; color: #cfd6e0; font-size: 15px; }
    #custom-fields .remove-field:hover { color: #dc3545; }
</style>
<style>
    /* ── Description Attributes rows ── same rhythm as the Custom Attributes beside them. */
    .desc-attr-panel .desc-attr-row { margin-bottom: 12px !important; }
    .desc-attr-panel .desc-attr-row > div { display: flex; align-items: center; gap: 12px; }
    .desc-attr-panel .desc-attr-row input.form-control {
        flex: 1 1 50%;
        min-width: 0;
        height: 34px;
        font-size: 12.5px;
        border-radius: 7px;
        border-color: #e3e7ef;
        margin-right: 0 !important;
    }
    .desc-attr-panel .desc-attr-row .remove-desc-attr,
    .desc-attr-panel .desc-attr-row a { flex: 0 0 auto; }
</style>
