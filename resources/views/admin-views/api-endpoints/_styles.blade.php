<style>
    .api-help {
        font-size: 12px;
        color: #6c757d;
    }

    .api-stat {
        border-radius: 8px;
        padding: 14px 16px;
    }

    .api-stat h3 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
    }

    .api-method {
        display: inline-block;
        min-width: 62px;
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
        color: #fff;
    }

    .m-GET {
        background: #1a73e8;
    }

    .m-POST {
        background: #00b894;
    }

    .m-PUT {
        background: #f39c12;
    }

    .m-PATCH {
        background: #8e44ad;
    }

    .m-DELETE {
        background: #e74c3c;
    }

    .m-HEAD,
    .m-OPTIONS {
        background: #7f8c8d;
    }

    .api-path {
        font-family: monospace;
        font-size: 13px;
        word-break: break-all;
    }

    .api-code {
        background: #f8f9fb;
        border: 1px solid #e7eaf3;
        border-radius: 6px;
        padding: 10px 12px;
        font-family: monospace;
        font-size: 12px;
        white-space: pre-wrap;
        word-break: break-word;
        max-height: 280px;
        overflow: auto;
        margin: 0;
    }

    .api-kv-table {
        font-size: 12px;
        width: 100%;
        margin-bottom: 0;
    }

    .api-kv-table th {
        font-weight: 600;
        color: #6c757d;
        border-top: 0;
        padding: 4px 8px;
    }

    .api-kv-table td {
        padding: 4px 8px;
        border-top: 1px solid #f1f3f9;
        vertical-align: top;
    }

    .api-kv-table td:first-child {
        font-family: monospace;
        white-space: nowrap;
    }

    .api-shot {
        width: 84px;
        height: 64px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e7eaf3;
    }

    .api-note {
        background: #fff8e6;
        border-left: 3px solid #f39c12;
        padding: 8px 12px;
        border-radius: 0 6px 6px 0;
        font-size: 13px;
    }

    .api-project-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .kv-row {
        display: flex;
        gap: 4px;
        margin-bottom: 4px;
    }

    /* Compact endpoint form: tighter rows, smaller labels, no oversized controls. */
    .api-form .form-group {
        margin-bottom: 10px;
    }

    .api-form label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: #6c757d;
        margin-bottom: 3px;
    }

    .api-form .form-control-sm {
        font-size: 12.5px;
        padding: 4px 8px;
        height: auto;
        min-height: 30px;
    }

    .api-form textarea.form-control-sm {
        min-height: 0;
    }

    .api-form .api-mono {
        font-family: monospace;
        font-size: 11.5px;
    }

    .btn-xs {
        padding: 1px 6px;
        font-size: 11px;
        line-height: 1.5;
        border-radius: 4px;
    }

    .api-shot-sm {
        width: 46px;
        height: 36px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e7eaf3;
    }
</style>
