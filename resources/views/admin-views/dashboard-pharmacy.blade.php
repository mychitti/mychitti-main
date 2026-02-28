@extends('layouts.admin.app')

@section('title', 'Agent Design — AI Module')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        /* ── TWO-COLUMN LAYOUT ── */
        .ai-agent-wrap {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            gap: 0;
            height: calc(100vh - 70px);
            overflow: hidden;
            margin: -20px -15px 0;
            /* bleed to edge of content container */
        }

        /* Left skill panel */
        .ai-lp {
            width: 262px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid #e5e8ef;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        /* Tabs */
        .ai-tabs {
            display: flex;
            padding: 11px 11px 0;
            gap: 3px;
            border-bottom: 1px solid #e5e8ef;
        }

        .ai-tab {
            flex: 1;
            text-align: center;
            padding: 7px 2px;
            font-size: 11.5px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            border-radius: 7px 7px 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-bottom: 2px solid transparent;
            transition: all .18s;
            position: relative;
            bottom: -1px;
        }

        .ai-tab.on {
            color: #e3342f;
            border-bottom: 2px solid #e3342f;
            background: #fff8f8;
        }

        .ai-tab:hover:not(.on) {
            color: #374151;
            background: #f9fafb;
        }

        .ai-bdg {
            border-radius: 20px;
            font-size: 9.5px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            background: #e3342f;
            color: #fff;
        }

        .ai-bdg.g {
            background: #d1d5db;
            color: #374151;
        }

        .ai-bdg.b {
            background: #3b82f6;
        }

        /* Section header */
        .ai-sh {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 13px 5px;
        }

        .ai-sh-l {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        .ai-sh-r {
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
        }

        /* Skill items */
        .ai-si {
            margin: 3px 7px;
            padding: 10px 11px;
            border-radius: 9px;
            cursor: pointer;
            transition: all .18s;
            border: 1.5px solid transparent;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ai-si:hover {
            background: #f9fafb;
        }

        .ai-si.on {
            background: linear-gradient(135deg, #e3342f, #c0392b);
            box-shadow: 0 4px 14px rgba(227, 52, 47, .28);
        }

        .ai-sdot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .ai-sdot.dr {
            background: #f59e0b;
        }

        .ai-sdot.ac {
            background: #10b981;
        }

        .ai-sinfo {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .ai-sn {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
            display: block;
        }

        .ai-si.on .ai-sn {
            color: #fff;
        }

        .ai-ss {
            font-size: 11px;
            color: #6b7280;
            display: block;
            margin-top: 1px;
        }

        .ai-si.on .ai-ss {
            color: rgba(255, 255, 255, .7);
        }

        .ai-stag {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .5px;
            padding: 2px 6px;
            border-radius: 20px;
        }

        .ai-si.on .ai-stag {
            background: rgba(255, 255, 255, .2);
            color: #fff;
        }

        .ai-stag.dt {
            background: #fef3c7;
            color: #d97706;
        }

        .ai-stag.at {
            background: #d1fae5;
            color: #059669;
        }

        .ai-add-s {
            margin: 7px 9px 11px;
            padding: 8px;
            border: 1.5px dashed #d1d5db;
            border-radius: 9px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all .18s;
        }

        .ai-add-s:hover {
            border-color: #e3342f;
            color: #e3342f;
            background: #fff5f5;
        }

        /* Right panel */
        .ai-rp {
            flex: 1;
            overflow-y: auto;
            {{-- padding: 11px; --}}
            background: #f6f6f6;
        }

        /* ── CARDS ── */
        .ai-agent-wrap .card {
            background: #fff;
            border-radius: 13px;
            padding: 20px 22px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            border: 1px solid #e5e8ef;
            margin-bottom: 15px;
        }

        /* Page header actions */
        .env-select {
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            padding: 6px 28px 6px 10px;
            font-size: 12px;
            font-weight: 700;
            font-family: inherit;
            outline: none;
            cursor: pointer;
            appearance: none;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 8px center;
        }

        .env-select.prod {
            color: #ef4444;
            border-color: #fca5a5;
            background-color: #fff5f5;
        }

        .env-select.staging {
            color: #d97706;
            border-color: #fcd34d;
            background-color: #fffbeb;
        }

        .env-select.sandbox {
            color: #059669;
            border-color: #6ee7b7;
            background-color: #f0fdf4;
        }

        .btn-t {
            padding: 7px 14px;
            background: #f0fdf4;
            color: #059669;
            border: 1.5px solid #a7f3d0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-t:hover {
            background: #dcfce7;
        }

        .btn-n {
            background: linear-gradient(135deg, #e3342f, #c0392b);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 8px rgba(227, 52, 47, .25);
        }

        /* Card header */
        .ch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f3f4f6;
        }

        .ch-l {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .av {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: linear-gradient(135deg, #e3342f, #c0392b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .ct {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            letter-spacing: -.3px;
        }

        .cm {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 2px;
        }

        .ca {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .sbig {
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
        }

        .sbig.dr {
            background: #fef3c7;
            color: #d97706;
        }

        .sbig.ac {
            background: #d1fae5;
            color: #059669;
        }

        .bdel {
            padding: 6px 13px;
            border: 1.5px solid #fca5a5;
            background: #fff;
            color: #ef4444;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .bdel:hover {
            background: #fef2f2;
        }

        .bsav {
            padding: 6px 16px;
            background: linear-gradient(135deg, #e3342f, #c0392b);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(227, 52, 47, .22);
        }

        .bsav:hover {
            transform: translateY(-1px);
        }

        /* Section header */
        .sch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .sch-l {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ico {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
        }

        .st {
            font-size: 13.5px;
            font-weight: 700;
            color: #111827;
        }

        .ss2 {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 1px;
        }

        /* Buttons */
        .badd {
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            border: 1.5px solid;
        }

        .badd.ind {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }

        .badd.ind:hover {
            background: #e0e7ff;
        }

        .badd.grn {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .badd.grn:hover {
            background: #d1fae5;
        }

        .badd.red {
            background: #fff5f5;
            color: #ef4444;
            border-color: #fca5a5;
        }

        .badd.red:hover {
            background: #fee2e2;
        }

        .badd.pur {
            background: #faf5ff;
            color: #7c3aed;
            border-color: #ddd6fe;
        }

        .badd.pur:hover {
            background: #ede9fe;
        }

        /* Form */
        .fg {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 14px;
        }

        .fr {
            display: grid;
            gap: 14px;
            margin-bottom: 14px;
        }

        .fr.c3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .fr.c2 {
            grid-template-columns: 1fr 1fr;
        }

        .fr.c4 {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }

        .fl {
            font-size: 10.5px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .fh {
            font-size: 10.5px;
            color: #9ca3af;
            margin-top: 2px;
        }

        .fc {
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            padding: 8px 11px;
            font-size: 12.5px;
            font-family: inherit;
            color: #111827;
            background: #fff;
            outline: none;
            transition: border-color .18s;
            width: 100%;
        }

        .fc:focus {
            border-color: #e3342f;
            box-shadow: 0 0 0 3px rgba(227, 52, 47, .07);
        }

        select.fc {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 9px center;
            padding-right: 28px;
        }

        textarea.fc {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }

        /* Chips */
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .chip {
            padding: 3px 11px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            border: 1.5px solid #e5e8ef;
            color: #6b7280;
            cursor: pointer;
            transition: all .14s;
        }

        .chip:hover {
            border-color: #e3342f;
            color: #e3342f;
        }

        .chip.on {
            background: #e3342f;
            color: #fff;
            border-color: #e3342f;
        }

        /* Info banners */
        .ib {
            border-radius: 8px;
            padding: 9px 13px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.5;
        }

        .ib.blue {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .ib.red {
            background: #fff5f5;
            border: 1px solid #fca5a5;
            color: #dc2626;
        }

        .ib.amber {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #d97706;
        }

        .ib.green {
            background: #f0fdf4;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        /* Table */
        .tw {
            overflow-x: auto;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .tbl thead tr {
            background: #f9fafb;
        }

        .tbl th {
            padding: 8px 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .6px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .tbl td {
            padding: 7px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .tbl tr:last-child td {
            border-bottom: none;
        }

        .tbl tr:hover td {
            background: #fafafa;
        }

        .rn {
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            text-align: center;
            width: 26px;
        }

        .ti {
            border: 1.5px solid #e5e8ef;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 12px;
            font-family: inherit;
            color: #111827;
            width: 100%;
            outline: none;
            background: #fff;
        }

        .ti:focus {
            border-color: #e3342f;
        }

        .ts {
            border: 1.5px solid #e5e8ef;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 11.5px;
            font-family: inherit;
            color: #374151;
            background: #fff;
            outline: none;
            cursor: pointer;
            width: 100%;
            appearance: none;
        }

        /* Pills */
        .pill {
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        .pill.ac {
            background: #d1fae5;
            color: #059669;
        }

        .pill.dr {
            background: #fef3c7;
            color: #d97706;
        }

        .pill.er {
            background: #fee2e2;
            color: #ef4444;
        }

        .pill.in {
            background: #f3f4f6;
            color: #6b7280;
        }

        .pill.bl {
            background: #dbeafe;
            color: #1d4ed8;
        }

        /* Action type pills */
        .at-r {
            background: #dbeafe;
            color: #1d4ed8;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .at-w {
            background: #fef3c7;
            color: #d97706;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .at-d {
            background: #fee2e2;
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        /* Toggle */
        .tog {
            width: 36px;
            height: 20px;
            border-radius: 10px;
            background: #d1d5db;
            position: relative;
            cursor: pointer;
            transition: background .2s;
            flex-shrink: 0;
            display: inline-block;
        }

        .tog.on {
            background: #e3342f;
        }

        .tog.gr.on {
            background: #059669;
        }

        .tog::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            transition: left .2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
        }

        .tog.on::after {
            left: 18px;
        }

        /* Memory toggles */
        .mt-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 13px;
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .mt-l {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
        }

        .mt-h {
            font-size: 11px;
            color: #9ca3af;
        }

        /* Permissions */
        .pg {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 9px;
        }

        .pi {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 11px;
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            cursor: pointer;
            transition: all .14s;
        }

        .pi.on {
            border-color: #4f46e5;
            background: #eef2ff;
        }

        .pi:hover:not(.on) {
            border-color: #a5b4fc;
        }

        .pck {
            width: 17px;
            height: 17px;
            border-radius: 4px;
            border: 2px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .14s;
        }

        .pi.on .pck {
            background: #4f46e5;
            border-color: #4f46e5;
        }

        .pl {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .pi.on .pl {
            color: #4f46e5;
        }

        /* Notifications */
        .ng {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .ni {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 11px 13px;
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
        }

        .nico {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .nb {
            flex: 1;
        }

        .nl {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
        }

        .nh {
            font-size: 10.5px;
            color: #9ca3af;
        }

        /* Slider */
        .slider {
            -webkit-appearance: none;
            width: 100%;
            height: 4px;
            border-radius: 3px;
            background: #e5e8ef;
            outline: none;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #e3342f;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
        }

        .sl-lab {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 4px;
        }

        /* Task rows */
        .tklist {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .tkrow {
            background: #f9fafb;
            border: 1.5px solid #e5e8ef;
            border-radius: 10px;
            padding: 10px 12px;
            transition: all .15s;
        }

        .tkrow:hover {
            background: #fff;
            border-color: #d1d5db;
        }

        .tkrow.destructive {
            border-left: 3px solid #ef4444;
        }

        .tkrow.destructive .tk-warn {
            display: flex;
        }

        .tk-warn {
            display: none;
            align-items: center;
            gap: 6px;
            background: #fff5f5;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            padding: 6px 10px;
            margin-top: 7px;
            font-size: 11.5px;
            color: #dc2626;
            font-weight: 600;
        }

        .tk-top {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .tk-drag {
            color: #d1d5db;
            font-size: 15px;
            cursor: grab;
            user-select: none;
            flex-shrink: 0;
        }

        .tk-name {
            flex: 1;
            min-width: 150px;
            font-weight: 600;
        }

        .tk-conf-row {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-top: 7px;
            padding: 7px 10px;
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 7px;
        }

        .tk-conf-row.hidden {
            display: none;
        }

        .tk-conf-label {
            font-size: 11.5px;
            font-weight: 600;
            color: #d97706;
            flex: 1;
        }

        /* JSON schema editor */
        .schema-box {
            background: #1a1a2e;
            border-radius: 9px;
            padding: 14px;
            position: relative;
        }

        .schema-lang {
            position: absolute;
            top: 10px;
            right: 12px;
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            letter-spacing: .5px;
        }

        .schema-ta {
            width: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: #a3e635;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.7;
            resize: vertical;
            min-height: 160px;
        }

        .schema-ta::selection {
            background: #3f3f5a;
        }

        .schema-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .schema-btn {
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid;
        }

        .schema-btn.validate {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .schema-btn.format {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
        }

        .schema-result {
            margin-top: 8px;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 11.5px;
            font-weight: 600;
            display: none;
        }

        .schema-result.ok {
            background: #d1fae5;
            color: #059669;
            display: block;
        }

        .schema-result.err {
            background: #fee2e2;
            color: #ef4444;
            display: block;
        }

        /* Error handling */
        .eh-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 14px;
        }

        .eh-item {
            padding: 12px 14px;
            border: 1.5px solid #e5e8ef;
            border-radius: 9px;
        }

        .eh-label {
            font-size: 11px;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 7px;
        }

        .fallback-ta {
            width: 100%;
            border: 1.5px solid #e5e8ef;
            border-radius: 7px;
            padding: 8px 10px;
            font-size: 12px;
            font-family: inherit;
            color: #374151;
            resize: none;
            min-height: 55px;
            outline: none;
        }

        .fallback-ta:focus {
            border-color: #e3342f;
        }

        .esc-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .esc-icon {
            font-size: 16px;
            flex-shrink: 0;
        }

        .esc-body {
            flex: 1;
        }

        .esc-title {
            font-size: 12.5px;
            font-weight: 600;
            color: #111827;
        }

        .esc-sub {
            font-size: 11px;
            color: #9ca3af;
        }

        /* Versioning */
        .ver-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: #f9fafb;
            border: 1.5px solid #e5e8ef;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .ver-num {
            font-size: 22px;
            font-weight: 800;
            color: #e3342f;
            letter-spacing: -1px;
            line-height: 1;
        }

        .ver-info {
            flex: 1;
        }

        .ver-label {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .ver-val {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-top: 1px;
        }

        .changelog-ta {
            width: 100%;
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            padding: 9px 11px;
            font-size: 12px;
            font-family: inherit;
            color: #374151;
            resize: none;
            min-height: 75px;
            outline: none;
            line-height: 1.6;
        }

        .changelog-ta:focus {
            border-color: #e3342f;
        }

        .ver-history {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-top: 12px;
        }

        .vh-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 9px 12px;
            border: 1px solid #f3f4f6;
            border-radius: 8px;
            background: #fafafa;
        }

        .vh-v {
            font-size: 11.5px;
            font-weight: 700;
            color: #e3342f;
            min-width: 36px;
        }

        .vh-body {
            flex: 1;
        }

        .vh-msg {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .vh-time {
            font-size: 10.5px;
            color: #9ca3af;
        }

        .vh-pill {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            background: #d1fae5;
            color: #059669;
            font-weight: 700;
        }

        /* Role grid */
        .role-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .role-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 11px;
            border: 1.5px solid #e5e8ef;
            border-radius: 8px;
            cursor: pointer;
            transition: all .14s;
        }

        .role-item.on {
            border-color: #059669;
            background: #f0fdf4;
        }

        .role-item:hover:not(.on) {
            border-color: #a7f3d0;
        }

        .role-check {
            width: 17px;
            height: 17px;
            border-radius: 4px;
            border: 2px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .role-item.on .role-check {
            background: #059669;
            border-color: #059669;
        }

        .role-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .role-item.on .role-label {
            color: #059669;
        }

        /* Test console */
        .console {
            background: #1e1e2e;
            border-radius: 10px;
            padding: 14px;
        }

        .con-label {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .7px;
            margin-bottom: 7px;
        }

        .con-in {
            width: 100%;
            background: #2a2a3e;
            border: 1px solid #3f3f5a;
            border-radius: 7px;
            padding: 9px 11px;
            color: #e2e8f0;
            font-family: inherit;
            font-size: 12.5px;
            resize: none;
            outline: none;
            min-height: 60px;
        }

        .con-in::placeholder {
            color: #4b5563;
        }

        .con-out {
            background: #2a2a3e;
            border-radius: 7px;
            padding: 11px;
            color: #a3e635;
            font-family: 'Courier New', monospace;
            font-size: 11.5px;
            min-height: 80px;
            line-height: 1.7;
            margin-top: 9px;
            white-space: pre-wrap;
        }

        .con-run {
            margin-top: 9px;
            padding: 7px 18px;
            background: linear-gradient(135deg, #059669, #047857);
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .con-run:hover {
            transform: translateY(-1px);
        }

        /* Logs */
        .log-list {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .log-i {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 13px;
            border: 1px solid #f3f4f6;
            border-radius: 8px;
            background: #fafafa;
        }

        .log-ic {
            width: 26px;
            height: 26px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex-shrink: 0;
        }

        .log-ic.ok {
            background: #d1fae5;
        }

        .log-ic.fa {
            background: #fee2e2;
        }

        .log-ic.wa {
            background: #fef3c7;
        }

        .log-b {
            flex: 1;
        }

        .log-t {
            font-size: 12px;
            font-weight: 600;
            color: #111827;
        }

        .log-m {
            font-size: 10.5px;
            color: #9ca3af;
        }

        /* Remove btn */
        .brm {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 11px;
            padding: 3px 5px;
            border-radius: 4px;
        }

        .brm:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        /* Bottom bar */
        .ai-bb {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            padding: 0 0 28px;
            margin-top: 4px;
        }

        /* ── RIGHT-PANEL SECTION TABS ── */
        .rp-tabs {
            display: flex;
            gap: 4px;
            padding: 16px 18px 0;
            border-bottom: 1px solid #e5e8ef;
            background: #fff;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .rp-tab-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            background: transparent;
            border: none;
            border-bottom: 2.5px solid transparent;
            border-radius: 0;
            cursor: pointer;
            transition: all .17s;
            position: relative;
            bottom: -1px;
            white-space: nowrap;
        }

        .rp-tab-btn:hover:not(.active) {
            color: #374151;
            background: #f9fafb;
        }

        .rp-tab-btn.active {
            color: #e3342f;
            border-bottom-color: #e3342f;
            background: transparent;
        }

        .rp-tab-btn .rp-tab-icon {
            font-size: 13px;
        }
    </style>
@endpush


@section('content')
    @php
        $a = $activeAgent; 
        $allowedRoles = $a?->allowed_roles ?? [];
        $notifSettings = $a?->notification_settings ?? [];
        $escalation = $a?->escalation_rules ?? [];
        $env = $a?->environment ?? 'prod';
    @endphp
    <div class="content container-fluid">
        <div class="page-header mb-3">
            <div class="row align-items-center py-2" style="border-bottom: 1px solid #e4e4e4;">
                <div class="col-sm mb-2 mb-sm-0"> 
                    @php 
                     $mod = \App\Models\Module::find(Config::get('module.current_module_id')) @endphp
                    <div class="d-flex align-items-center">
                        <img src="{{ \App\CentralLogics\Helpers::onerror_image_helper($mod?->icon, asset('storage/app/public/' . ($mod->thumbnail_path ?? 'module/')) . '/' . $mod?->icon, asset('public/assets/admin/img/new-img/module/e-shop.svg'), $mod->thumbnail_path ?? 'module/') }}"
                            data-onerror-image="{{ asset('public/assets/admin/img/new-img/module/e-shop.svg') }}"
                            alt="new-img" class="onerror-image" width="38" alt="img">
                        <div class="w-0 flex-grow pl-2">
                            <h1 class="page-header-title mb-0">{{ translate($mod->module_name) }}
                                {{ translate('messages.Dashboard') }}.</h1>
                            <p class="page-header-text m-0">{{ translate('Hello, Here You Can Manage Your') }}
                                {{ translate($mod->module_name) }} {{ translate('orders by Zone.') }}</p>
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:9px;">
                    <select class="env-select {{ $env }}" id="env-sel" onchange="changeEnv(this)">
                        <option value="prod" @selected($env === 'prod')>🔴 Production</option>
                        <option value="staging" @selected($env === 'staging')>🟡 Staging</option>
                        <option value="sandbox" @selected($env === 'sandbox')>🟢 Sandbox</option>
                    </select>
                    <button class="btn-t" onclick="openTest()">▶ Test Agent</button>
                    <button class="btn-n" onclick="openNewSkillModal()">+ New Skill</button>
                </div>
            </div>
        </div>

        <div class="ai-agent-wrap">

            <!-- ═══ LEFT SKILL PANEL ═══ -->
            <div class="ai-lp">
                <div class="ai-tabs">
                    <a class="ai-tab {{ $userType === 'admin' ? 'on' : '' }}" style="text-decoration:none"
                        href="{{ route('admin.agent.index', ['user_type' => 'admin']) }}">Admin <span
                            class="ai-bdg g">{{ $counts['admin'] }}</span></a>
                    <a class="ai-tab {{ $userType === 'user' ? 'on' : '' }}" style="text-decoration:none"
                        href="{{ route('admin.agent.index', ['user_type' => 'user']) }}">User <span
                            class="ai-bdg b">{{ $counts['user'] }}</span></a>
                    <a class="ai-tab {{ $userType === 'vendor' ? 'on' : '' }}" style="text-decoration:none"
                        href="{{ route('admin.agent.index', ['user_type' => 'vendor']) }}">Vendor <span
                            class="ai-bdg">{{ $counts['vendor'] }}</span></a>
                </div>
                <div class="ai-sh">
                    <span class="ai-sh-l" id="skill-section-label">{{ ucfirst($userType) }} Skills</span>
                    <span class="ai-sh-r" id="skill-count-label">{{ $agents->count() }}
                        {{ Str::plural('skill', $agents->count()) }}</span>
                </div>
                <div id="skill-list">
                    @forelse($agents as $agent)
                        @php
                            $sDot = match ($agent->status) {
                                'active' => 'ac',
                                'inactive' => 'er',
                                default => 'dr',
                            };
                            $sTagClass = match ($agent->status) {
                                'active' => 'at',
                                'inactive' => 'er',
                                default => 'dt',
                            };
                            $sTagLabel = match ($agent->status) {
                                'active' => 'ACTIVE',
                                'inactive' => 'INACTIVE',
                                default => 'DRAFT',
                            };
                        @endphp
                        <a class="ai-si {{ $a && $a->id == $agent->id ? 'on' : '' }}" style="text-decoration:none"
                            href="{{ route('admin.agent.index', ['user_type' => $userType, 'agent_id' => $agent->id]) }}">
                            <div class="ai-sinfo">
                                <span class="ai-sdot {{ $sDot }}"></span>
                                <div>
                                    <span class="ai-sn">{{ $agent->name }}</span>
                                    <span class="ai-ss">{{ $agent->skill_type }}</span>
                                </div>
                            </div>
                            <span class="ai-stag {{ $sTagClass }}">{{ $sTagLabel }}</span>
                        </a>
                    @empty
                        <div style="padding:24px;text-align:center;color:#9ca3af;font-size:12px">No skills yet. Create your
                            first one!</div>
                    @endforelse
                </div>
                <div class="ai-add-s" onclick="openNewSkillModal()">+ Add Skill</div>
            </div>

            <!-- ═══ RIGHT CONTENT PANEL ═══ -->
            <div class="ai-rp">
                @if ($a)
                 {{-- 'webhook' => ['🔗', 'Webhook Callback', 'POST result to external URL', '#fef9c3'],
                            'telegram' => ['📱', 'Telegram Bot', "Send to vendor's Telegram", '#e0f2fe'], --}}
                    @php
                        $statusClass = match ($a->status ?? 'draft') {
                            'active' => 'ac',
                            'inactive' => 'er',
                            default => 'dr',
                        };
                        $envWarnClass = $env === 'prod' ? 'red' : ($env === 'staging' ? 'amber' : 'green');
                        $envWarnMsg =
                            $env === 'prod'
                                ? '🔴 <strong>Production mode active.</strong> This agent will run on LIVE data. Switch to Sandbox for testing.'
                                : ($env === 'staging'
                                    ? '🟡 <strong>Staging mode.</strong> Uses staging database — safe but not real data.'
                                    : '🟢 <strong>Sandbox mode.</strong> Safe to test — no real data will be affected.');
                        $liveVer = $a->versions->firstWhere('is_live', 1);
                        $allRoles = [
                            'admin'           => '🛡️ Admin',
                            'vendor'          => '🏪 Vendor Owner',
                            'vendor_employee' => '👷 Vendor Employee',
                            'user'            => '👤 Customer',
                        ];
                        $allModules = [
                            'analytics' => '📊 Analytics',
                            'billing' => '🧾 Billing',
                            'inventory' => '📦 Inventory',
                            'hrm' => '👥 HRM',
                            'documents' => '📄 Documents',
                            'orders' => '🛒 Orders',
                            'reviews' => '⭐ Reviews',
                            'messages' => '💬 Messages',
                            'vendor_profile' => '🏪 Vendor Profile',
                        ];
                           
                        $allNotifs = [
                            'email' => ['📧', 'Email', 'Send result via email', '#fef3c7'],
                            'in_app' => ['💬', 'In-App Notification', 'Show in vendor dashboard', '#d1fae5'],
                            'sms' => ['📲', 'SMS / WhatsApp', 'Send SMS alert', '#fce7f3'],
                            'report' => ['📊', 'Save to Report', 'Auto-save output as report', '#ede9fe'],
                        ];
                        $allEscalations = [
                            'auth_error' => [
                                '🔑',
                                'Auth Error → Ask vendor to re-login',
                                'Trigger: 401 / 403 response',
                            ],
                            'write_error' => [
                                '👑',
                                'Write Error → Notify Admin',
                                'Trigger: 500 on write/delete operations',
                            ],
                            'timeout' => [
                                '⏱️',
                                'Timeout → Log and skip silently',
                                'Trigger: Request exceeds timeout limit',
                            ],
                            'rate_limit' => ['🚦', 'Rate Limit Hit → Queue & retry', 'Trigger: 429 Too Many Requests'],
                        ];
                    @endphp

                    <!-- RIGHT PANEL SECTION TABS -->
                    <div class="rp-tabs">
                        <button class="rp-tab-btn active" data-tab="basic" onclick="switchRpTab('basic')">
                            <span class="rp-tab-icon">📋</span> Basic Info
                        </button>
                        <button class="rp-tab-btn" data-tab="versioning" onclick="switchRpTab('versioning')">
                            <span class="rp-tab-icon">📦</span> Versioning
                        </button>
                        <button class="rp-tab-btn" data-tab="api" onclick="switchRpTab('api')">
                            <span class="rp-tab-icon">🔌</span> API Tools
                        </button>
                        <button class="rp-tab-btn" data-tab="schema" onclick="switchRpTab('schema')">
                            <span class="rp-tab-icon">🧩</span> Functions
                        </button>
                        <button class="rp-tab-btn" data-tab="tasks" onclick="switchRpTab('tasks')">
                            <span class="rp-tab-icon">✅</span> Tasks
                        </button>
                        <button class="rp-tab-btn" data-tab="test" onclick="switchRpTab('test')">
                            <span class="rp-tab-icon">⌨️</span> Test & Logs
                        </button>
                    </div>

                    <!-- ① AGENT IDENTITY -->
                    <div class="card m-2" data-tab="basic">
                        <div class="ch">
                            <div class="ch-l">
                                <div class="av">🤖</div>
                                <div>
                                    <div class="ct" id="agent-title">{{ $a->name }}</div>
                                    <div class="cm">{{ $a->user_type }} · {{ $a->skill_type }} · Last saved:
                                        {{ $a->updated_at?->diffForHumans() ?? 'Never' }}</div>
                                </div>
                            </div>
                            <div class="ca">
                                <span class="sbig {{ $statusClass }}"
                                    id="agent-status-badge">{{ strtoupper($a->status ?? 'draft') }}</span>
                                <button class="bdel" onclick="deleteAgent()">Delete</button>
                                <button class="bsav" onclick="saveAgent()">Save Changes</button>
                            </div>
                        </div>

                        <div class="ib {{ $envWarnClass }}" id="env-warn">{!! $envWarnMsg !!}</div>

                        <div class="fr c3">
                            <div class="fg">
                                <label class="fl">Agent User Type</label>
                                <select class="fc" id="ag-utype">
                                    <option value="vendor" @selected($a->user_type === 'vendor')>vendor</option>
                                    <option value="admin" @selected($a->user_type === 'admin')>admin</option>
                                    <option value="user" @selected($a->user_type === 'user')>user</option>
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">Skill Type</label>
                                <select class="fc" id="ag-stype">
                                    @foreach (['analytics', 'chatbot', 'seo', 'pdf_analysis', 'excel_generator', 'inventory', 'billing', 'hr_assistant'] as $st)
                                        <option value="{{ $st }}" @selected($a->skill_type === $st)>
                                            {{ ucwords(str_replace('_', ' ', $st)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">Status</label>
                                <select class="fc" id="ag-status">
                                    <option value="draft" @selected(($a->status ?? 'draft') === 'draft')>Draft</option>
                                    <option value="active" @selected($a->status === 'active')>Active</option>
                                    <option value="inactive" @selected($a->status === 'inactive')>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="fr c2">
                            <div class="fg">
                                <label class="fl">Skill Name</label>
                                <input class="fc" type="text" id="ag-name" value="{{ $a->name }}">
                            </div>
                            <div class="fg">
                                <label class="fl">Description</label>
                                <input class="fc" type="text" id="ag-desc" value="{{ $a->description }}">
                            </div>
                        </div>
                        <div class="fg">
                            <label class="fl">System Prompt</label>
                            <textarea class="fc" id="ag-prompt">{{ $a->prompt }}</textarea>
                        </div>
                        <label class="fl">Quick Skill Types</label>
                        <div class="chips">
                            @foreach (['Analytics' => 'analytics', 'Chatbot' => 'chatbot', 'SEO' => 'seo', 'PDF Analysis' => 'pdf_analysis', 'Excel Generator' => 'excel_generator', 'Billing' => 'billing', 'HR Assistant' => 'hr_assistant', 'Inventory' => 'inventory'] as $label => $val)
                                <span class="chip {{ $a->skill_type === $val ? 'on' : '' }}"
                                    onclick="setSkillType('{{ $val }}', this)">{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>

                    <!-- ② AGENT VERSIONING -->
                    <div class="card m-2" data-tab="versioning">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#0ea5e9,#0284c7)">📦</div>
                                <div>
                                    <div class="st">Agent Versioning</div>
                                    <div class="ss2">Track prompt changes — important for billing, pricing, legal
                                        compliance</div>
                                </div>
                            </div>
                            <button class="badd ind" onclick="openVersionModal()">+ New Version</button>
                        </div>
                        <div class="ver-header">
                            <div class="ver-num" id="ver-display">{{ $a->current_version ?? 'v1.0' }}</div>
                            <div class="ver-info">
                                <div class="ver-label">Current Version</div>
                                <div class="ver-val" id="ver-updated">Last updated:
                                    {{ $liveVer?->created_at?->format('M d, Y') ?? '—' }} · by
                                    {{ $liveVer?->updated_by ?? 'Admin' }}</div>
                            </div>
                            <span class="pill ac">Live</span>
                        </div>
                        @if ($a->versions->isNotEmpty())
                            <div class="ver-history">
                                @foreach ($a->versions->sortByDesc('created_at') as $ver)
                                    <div class="vh-item">
                                        <span class="vh-v">{{ $ver->version_tag }}</span>
                                        <div class="vh-body">
                                            <div class="vh-msg">{{ $ver->changelog }}</div>
                                            <div class="vh-time">{{ $ver->created_at?->format('M d, Y') }} ·
                                                {{ $ver->updated_by }}</div>
                                        </div>
                                        <span class="vh-pill"
                                            style="{{ $ver->is_live ? '' : 'background:#f3f4f6;color:#6b7280' }}">{{ $ver->is_live ? 'Live' : 'Archived' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- ③ AI MODEL CONFIG -->
                    <div class="card m-2" data-tab="basic">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">🧠</div>
                                <div>
                                    <div class="st">AI Model Configuration</div>
                                    <div class="ss2">Choose AI model and tune response behavior</div>
                                </div>
                            </div>
                        </div>
                        <div class="fr c3">
                            <div class="fg">
                                <label class="fl">AI Provider</label>
                                <select class="fc" id="ag-provider">
                                    @foreach ($aiProviders as $key => $value)
                                        <option value="{{ $value->key }}" @selected($a->ai_provider === $value->key)>
                                            {{ $value->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">Model</label>
                                <select class="fc" id="ag-model">
                                    {{-- populated by updateModelSelect() JS below --}}
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">Max Tokens</label>
                                <input class="fc" type="number" id="ag-tokens"
                                    value="{{ $a->max_tokens ?? 1024 }}">
                                <span class="fh">Max response length</span>
                            </div>
                        </div>
                        <button type="button"
                            onclick="const p=document.getElementById('adv-params');const arrow=document.getElementById('adv-arrow');const open=p.style.display==='grid';p.style.display=open?'none':'grid';arrow.textContent=open?'▾':'▴';"
                            style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1.5px solid #e5e8ef;border-radius:8px;padding:8px 14px;font-size:12px;font-weight:600;color:#374151;cursor:pointer;width:100%;text-align:left;margin-bottom:10px">
                            <span>⚙️</span>
                            Advanced Settings
                            <span style="font-size:10.5px;color:#9ca3af;font-weight:400">(temperature, top‑p)</span>
                            <span id="adv-arrow" style="margin-left:auto;font-size:13px;color:#6b7280">▾</span>
                        </button>
                        <div id="adv-params" class="fr c2" style="display:none">
                            <div class="fg">
                                <label class="fl">Temperature — <span id="tv"
                                        style="color:#e3342f;font-weight:700">{{ $a->temperature ?? 0.7 }}</span></label>
                                <input class="slider" type="range" min="0" max="1" step="0.1"
                                    id="ag-temp" value="{{ $a->temperature ?? 0.7 }}"
                                    oninput="document.getElementById('tv').textContent=this.value">
                                <div class="sl-lab"><span>Precise</span><span>Balanced</span><span>Creative</span></div>
                            </div>
                            <div class="fg">
                                <label class="fl">Top-P — <span id="tpv"
                                        style="color:#e3342f;font-weight:700">{{ $a->top_p ?? 0.9 }}</span></label>
                                <input class="slider" type="range" min="0" max="1" step="0.05"
                                    id="ag-topp" value="{{ $a->top_p ?? 0.9 }}"
                                    oninput="document.getElementById('tpv').textContent=this.value">
                                <div class="sl-lab"><span>Focused</span><span>Normal</span><span>Diverse</span></div>
                            </div>
                        </div>
                        <div class="fg">
                            <label class="fl">API Key Override (optional)</label>
                            <input class="fc" type="password" id="ag-apikey"
                                placeholder="Leave blank to use platform default key" value="{{ $a->api_key_override }}">
                            <span class="fh">Only set if using your own API key for this specific agent</span>
                        </div>
                    </div>

                    <!-- ④ LOGIN STATE & AUTH -->
                    <div class="card m-2" data-tab="basic">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#0f766e,#0d9488)">🔐</div>
                                <div>
                                    <div class="st">Login State & Access Control</div>
                                    <div class="ss2">Authentication requirements and role restrictions for this agent
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ib amber">⚠️ Without login restriction, any unauthenticated user could trigger this
                            agent.
                            Always enable auth for write-capable agents.</div>
                        <div class="fr c2">
                            <div class="fg">
                                <label class="fl">Requires Authentication?</label>
                                <select class="fc" id="ag-auth">
                                    <option value="yes" @selected($a->requires_auth)>Yes — Login Required</option>
                                    <option value="no" @selected(!$a->requires_auth)>No — Public Access</option>
                                    <option value="optional">Optional — Works Both Ways</option>
                                </select>
                            </div>
                            <div class="fg">
                                <label class="fl">Session Validation</label>
                                <select class="fc" id="ag-sess">
                                    <option value="jwt" @selected(($a->session_validation ?? 'jwt') === 'jwt')>Validate JWT Token</option>
                                    <option value="session" @selected($a->session_validation === 'session')>Check Active Session</option>
                                    <option value="api_key" @selected($a->session_validation === 'api_key')>API Key Auth</option>
                                    <option value="none" @selected($a->session_validation === 'none')>None</option>
                                </select>
                            </div>
                        </div>
                        <label class="fl">Allowed Roles — Who Can Trigger This Agent</label>
                        <div class="role-grid">
                            @foreach ($allRoles as $roleKey => $roleLabel)
                                @php $roleOn = in_array($roleKey, $allowedRoles); @endphp
                                <div class="role-item {{ $roleOn ? 'on' : '' }}" data-role="{{ $roleKey }}"
                                    onclick="toggleRole(this)">
                                    <div class="role-check">{!! $roleOn
                                        ? '<svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>'
                                        : '' !!}</div>
                                    <span class="role-label">{{ $roleLabel }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
 
                    <!-- ⑥ MEMORY / CONTEXT -->
                    <div class="card m-2" data-tab="basic">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#06b6d4,#0891b2)">💾</div>
                                <div>
                                    <div class="st">Memory & Context</div>
                                    <div class="ss2">How the agent remembers conversations and data</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-row">
                            <div>
                                <div class="mt-l">Conversation History</div>
                                <div class="mt-h">Remember past messages in same session</div>
                            </div>
                            <div class="tog {{ $a->conv_history_enabled ? 'on' : '' }}" id="ag-convhist"
                                onclick="this.classList.toggle('on')"></div>
                        </div>
                        <div class="mt-row">
                            <div>
                                <div class="mt-l">Cross-Session Memory</div>
                                <div class="mt-h">Remember vendor data across different sessions</div>
                            </div>
                            <div class="tog {{ $a->cross_session_memory ? 'on' : '' }}" id="ag-crossmem"
                                onclick="this.classList.toggle('on')"></div>
                        </div>
                        <div class="mt-row">
                            <div>
                                <div class="mt-l">Inject Vendor Profile</div>
                                <div class="mt-h">Auto-load vendor business info into context</div>
                            </div>
                            <div class="tog {{ $a->inject_vendor_profile ? 'on' : '' }}" id="ag-inject"
                                onclick="this.classList.toggle('on')"></div>
                        </div>
                        <div class="fr c2" style="margin-top:11px">
                            <div class="fg">
                                <label class="fl">Max History Messages</label>
                                <input class="fc" type="number" id="ag-maxhist"
                                    value="{{ $a->max_history_messages ?? 10 }}">
                                <span class="fh">Last N messages in context</span>
                            </div>
                            <div class="fg">
                                <label class="fl">Context Window</label>
                                <select class="fc" id="ag-ctxwin">
                                    <option value="4k" @selected($a->context_window === '4k')>4K tokens</option>
                                    <option value="8k" @selected($a->context_window === '8k')>8K tokens</option>
                                    <option value="32k" @selected(($a->context_window ?? '32k') === '32k')>32K tokens</option>
                                    <option value="128k" @selected($a->context_window === '128k')>128K tokens</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ⑦ API CALLS + TOOL-LEVEL PERMISSIONS -->
                    <div class="card m-2" data-tab="api">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">🔌</div>
                                <div>
                                    <div class="st">API Calls & Tool-Level Permissions</div>
                                    <div class="ss2">Control exactly what each API can do — action type, confirmation,
                                        rate limits</div>
                                </div>
                            </div>
                            <button class="badd ind" onclick="addApi()">+ Add API</button>
                        </div>
                        <div class="ib red">🛡️ <strong>Write / Delete actions</strong> should always have <strong>Require
                                Confirmation = Yes</strong> to prevent the AI from auto-executing destructive operations.
                        </div>
                        <div class="tw">
                            <table class="tbl"> 
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>API Name</th>
                                        <th>Endpoint</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="api-tb">
                                    @forelse($a->apiTools as $i => $tool)
                                        <tr>
                                            <td class="rn">{{ $i + 1 }}</td>
                                            <td><input class="ti" type="text" value="{{ $tool->api_name }}"
                                                    style="min-width:110px"></td>
                                            <td><input class="ti" type="text" value="{{ $tool->endpoint }}"
                                                    style="min-width:180px"></td>
                                            <td><select class="ts" style="width:76px">
                                                    <option @selected(($tool->method ?? 'POST') === 'GET')>GET</option>
                                                    <option @selected(($tool->method ?? 'POST') === 'POST')>POST</option>
                                                    <option @selected(($tool->method ?? 'POST') === 'PUT')>PUT</option>
                                                    <option @selected(($tool->method ?? 'POST') === 'DELETE')>DELETE</option>
                                                </select></td>
                                            <td><span
                                                    class="pill {{ $tool->status === 'active' ? 'ac' : 'dr' }}">{{ ucfirst($tool->status ?? 'active') }}</span>
                                            </td>
                                            <td><button class="brm" onclick="this.closest('tr').remove()">✕</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6"
                                                style="text-align:center;color:#9ca3af;font-size:12px;padding:20px">No API
                                                tools configured yet</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:11px;font-size:11px;color:#6b7280">Tools are configured by API name with endpoint and HTTP method.</div>
                        <div style="display:flex;justify-content:flex-end;padding-top:14px;border-top:1px solid #f1f5f9;margin-top:16px">
                            <button class="bsav" onclick="saveAgent()">💾 Save API Tools</button>
                        </div>
                    </div>

                    <!-- ⑧ FUNCTION SCHEMA (Tool Calling) -->
                    <div class="card m-2" data-tab="schema">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#0f172a,#1e293b)">📐</div>
                                <div>
                                    <div class="st">Function Schema (Tool Calling)</div>
                                    <div class="ss2">Required for OpenAI function calling & Claude tool use — define
                                        parameters and validation</div>
                                </div>
                            </div>
                            <button class="badd pur" onclick="addSchema()">+ Add Function</button>
                        </div>
                        <div class="ib blue">📌 Without a proper JSON schema, AI function calling is unstable. The model
                            needs
                            to know the exact parameter names, types, and which are required.</div>
                        <div id="schema-list">
                            @forelse($a->functions as $fn)
                                @php
                                    $schId = 'schema-fn-' . $fn->id;
                                    $resId = 'res-fn-' . $fn->id;
                                @endphp
                                <div class="schema-entry"
                                    style="margin-bottom:16px;border:1.5px solid #e5e8ef;border-radius:10px;padding:14px">
                                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:11px">
                                        <input class="fc" type="text" value="{{ $fn->function_name }}"
                                            style="flex:1;font-family:'Courier New',monospace;font-size:12.5px;font-weight:700;color:#7c3aed">
                                        <input class="fc" type="text" value="{{ $fn->description }}"
                                            style="flex:2">
                                        <button class="brm" onclick="this.closest('.schema-entry').remove()">✕</button>
                                    </div>
                                    <div class="schema-box">
                                        <span class="schema-lang">JSON SCHEMA</span>
                                        <textarea class="schema-ta" id="{{ $schId }}">{{ is_array($fn->json_schema) ? json_encode($fn->json_schema, JSON_PRETTY_PRINT) : $fn->json_schema }}</textarea>
                                    </div>
                                    <div class="schema-actions">
                                        <button class="schema-btn validate"
                                            onclick="validateSchema('{{ $schId }}','{{ $resId }}')">✓
                                            Validate JSON</button>
                                        <button class="schema-btn format"
                                            onclick="formatSchema('{{ $schId }}','{{ $resId }}')">⟳
                                            Format</button>
                                    </div>
                                    <div class="schema-result" id="{{ $resId }}"></div>
                                </div>
                            @empty
                                <div style="text-align:center;color:#9ca3af;font-size:12px;padding:20px">No function
                                    schemas defined yet</div>
                            @endforelse
                        </div>
                        <div style="display:flex;justify-content:flex-end;padding-top:14px;border-top:1px solid #f1f5f9;margin-top:16px">
                            <button class="bsav" onclick="saveAgent()">💾 Save Functions</button>
                        </div>
                    </div>

                    <!-- ⑨ TASKS + DESTRUCTIVE CONFIRMATION -->
                    <div class="card m-2" data-tab="tasks">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#10b981,#059669)">✅</div>
                                <div>
                                    <div class="st">Tasks</div>
                                    <div class="ss2">Automated actions — enable approval for destructive operations
                                    </div>
                                </div>
                            </div>
                            <button class="badd grn" onclick="addTask()">+ Add Task</button>
                        </div>
                        <div class="ib amber">🔒 Tasks marked <strong>Destructive</strong> (delete data, cancel bookings,
                            deduct wallet) will show an approval prompt before execution.</div>
                        <div class="tklist" id="task-list">
                            @forelse($a->tasks as $task)
                                @php
                                    $tIsDestr = (bool) $task->is_destructive;
                                    $tStatCls = match ($task->status ?? 'active') {
                                        'active' => 'ac',
                                        'inactive' => 'er',
                                        default => 'dr',
                                    };
                                    $tStatLbl = match ($task->status ?? 'active') {
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        default => 'Draft',
                                    };
                                @endphp
                                <div class="tkrow {{ $tIsDestr ? 'destructive' : '' }}">
                                    <div class="tk-top">
                                        <span class="tk-drag">⠿</span>
                                        <input class="ti tk-name" type="text" value="{{ $task->task_name }}"
                                            style="flex:1;min-width:150px;font-weight:600">
                                        <select class="ts" style="width:120px">
                                            <option value="scheduled" @selected($task->trigger_type === 'scheduled')>Scheduled</option>
                                            <option value="on_demand" @selected($task->trigger_type === 'on_demand')>On Demand</option>
                                            <option value="trigger" @selected($task->trigger_type === 'trigger')>Trigger</option>
                                            <option value="manual" @selected($task->trigger_type === 'manual')>Manual</option>
                                        </select>
                                        <select class="ts" style="width:100px">
                                            <option value="analytics" @selected($task->skill_category === 'analytics')>Analytics</option>
                                            <option value="pdf" @selected($task->skill_category === 'pdf')>PDF</option>
                                            <option value="excel" @selected($task->skill_category === 'excel')>Excel</option>
                                            <option value="chatbot" @selected($task->skill_category === 'chatbot')>Chatbot</option>
                                            <option value="seo" @selected($task->skill_category === 'seo')>SEO</option>
                                        </select>
                                        <label
                                            style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:{{ $tIsDestr ? '#ef4444' : '#6b7280' }};cursor:pointer;white-space:nowrap">
                                            <div class="tog {{ $tIsDestr ? 'on' : '' }}"
                                                onclick="toggleDestructive(this)"></div> Destructive?
                                        </label>
                                        <span
                                            class="pill {{ $tIsDestr ? 'er' : $tStatCls }}">{{ $tIsDestr ? 'Destructive' : $tStatLbl }}</span>
                                        <button class="brm" onclick="this.closest('.tkrow').remove()">✕</button>
                                    </div>
                                    <input class="ti" type="text" value="{{ $task->description }}"
                                        style="margin-top:6px;color:#6b7280;font-size:11.5px">
                                    <div class="tk-warn" style="display:{{ $tIsDestr ? 'flex' : 'none' }}">⚠️ This task
                                        is marked destructive. Vendor must approve before execution.</div>
                                    <div class="tk-conf-row {{ $tIsDestr ? '' : 'hidden' }}">
                                        <span style="font-size:13px">✅</span>
                                        <span class="tk-conf-label">Require vendor approval before running this task</span>
                                        <div class="tog gr {{ $task->require_approval ? 'on' : '' }}"
                                            onclick="this.classList.toggle('on')"></div>
                                    </div>
                                </div>
                            @empty
                                <div style="text-align:center;color:#9ca3af;font-size:12px;padding:20px">No tasks defined
                                    yet</div>
                            @endforelse
                        </div>
                        <div style="display:flex;justify-content:flex-end;padding-top:14px;border-top:1px solid #f1f5f9;margin-top:16px">
                            <button class="bsav" onclick="saveAgent()">💾 Save Tasks</button>
                        </div>
                    </div>

                    <!-- ⑪ ERROR HANDLING STRATEGY -->
                    <div class="card m-2" data-tab="basic">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#dc2626,#991b1b)">⚠️</div>
                                <div>
                                    <div class="st">Error Handling Strategy</div>
                                    <div class="ss2">What the agent does when API calls fail or timeout</div>
                                </div>
                            </div>
                        </div>
                        <div class="eh-grid">
                            <div class="eh-item">
                                <div class="eh-label">On API Failure</div>
                                <div class="fr c2" style="margin-bottom:10px">
                                    <div class="fg">
                                        <label class="fl">Retry Count</label>
                                        <select class="fc" id="ag-retry">
                                            <option value="0" @selected(($a->retry_count ?? 1) == 0)>0 — No retry</option>
                                            <option value="1" @selected(($a->retry_count ?? 1) == 1)>1 — Retry once</option>
                                            <option value="2" @selected($a->retry_count == 2)>2 — Retry twice</option>
                                            <option value="3" @selected($a->retry_count == 3)>3 — Retry 3x</option>
                                        </select>
                                    </div>
                                    <div class="fg">
                                        <label class="fl">Backoff Strategy</label>
                                        <select class="fc" id="ag-backoff">
                                            <option value="none" @selected($a->backoff_strategy === 'none')>None</option>
                                            <option value="linear" @selected(($a->backoff_strategy ?? 'linear') === 'linear')>Linear (2s)</option>
                                            <option value="exponential" @selected($a->backoff_strategy === 'exponential')>Exponential</option>
                                        </select>
                                    </div>
                                </div>
                                <label class="fl" style="margin-bottom:6px">Fallback Message to Vendor</label>
                                <textarea class="fallback-ta" id="ag-fallback">{{ $a->fallback_message ?? "Sorry, I couldn't complete this action right now. Please try again in a moment or contact support." }}</textarea>
                            </div>
                            <div class="eh-item">
                                <div class="eh-label">Escalation Rules</div>
                                @foreach ($allEscalations as $escKey => $escData)
                                    @php $escOn = $escalation[$escKey] ?? ($escKey !== 'timeout'); @endphp
                                    <div class="esc-row">
                                        <div class="esc-icon">{{ $escData[0] }}</div>
                                        <div class="esc-body">
                                            <div class="esc-title">{{ $escData[1] }}</div>
                                            <div class="esc-sub">{{ $escData[2] }}</div>
                                        </div>
                                        <div class="tog gr {{ $escOn ? 'on' : '' }}" id="esc-{{ $escKey }}"
                                            onclick="this.classList.toggle('on')"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- ⑫ NOTIFICATIONS & OUTPUT -->
                    <div class="card m-2" data-tab="basic">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#f97316,#ea580c)">🔔</div>
                                <div>
                                    <div class="st">Notifications & Output</div>
                                    <div class="ss2">Where to send agent results and alerts</div>
                                </div>
                            </div>
                        </div>
                        <div class="ng">
                            @foreach ($allNotifs as $notifKey => $notifData)
                                @php $notifOn = $notifSettings[$notifKey] ?? in_array($notifKey, ['telegram','email','in_app']); @endphp
                                <div class="ni">
                                    <div class="nico" style="background:{{ $notifData[3] }}">{{ $notifData[0] }}
                                    </div>
                                    <div class="nb">
                                        <div class="nl">{{ $notifData[1] }}</div>
                                        <div class="nh">{{ $notifData[2] }}</div>
                                    </div>
                                    <div class="tog {{ $notifOn ? 'on' : '' }}" id="notif-{{ $notifKey }}"
                                        onclick="this.classList.toggle('on')"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- ⑬ TEST CONSOLE --> 
                    <div class="card m-2" id="test-section" data-tab="test">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:#1e1e2e">⌨️</div>
                                <div>
                                    <div class="st">Test Agent</div>
                                    <div class="ss2">Send a test message and preview the live response</div>
                                </div>
                            </div> 
                            <span class="pill {{ $env === 'prod' ? 'er' : ($env === 'staging' ? 'dr' : 'ac') }}"
                                id="env-badge">{{ ucfirst($env) }}</span>
                        </div>
                        <div class="console">
                            <div class="con-label">Your Message</div>
                            <textarea class="con-in" id="con-in" placeholder="e.g. Show me vendor sales for last month..."></textarea>
                            <button class="con-run" onclick="runTest()">▶ Run Agent</button>
                            <div class="con-label" style="margin-top:13px">Agent Response</div>
                            <div class="con-out" id="con-out">// Response will appear here after running the agent...
                            </div>
                        </div>
                    </div>
 
                    <!-- ⑭ RUN LOGS -->
                    <div class="card m-2" data-tab="test">
                        <div class="sch">
                            <div class="sch-l">
                                <div class="ico" style="background:linear-gradient(135deg,#374151,#1f2937)">📋</div>
                                <div>
                                    <div class="st">Run Logs</div>
                                    <div class="ss2">Recent execution history</div>
                                </div>
                            </div>
                            <span style="font-size:11.5px;color:#9ca3af">Last 5 runs</span>
                        </div>
                        <div class="log-list">
                            @forelse($a->runLogs as $log)
                                @php
                                    $logIcCls = match ($log->status) {
                                        'success' => 'ok',
                                        'failed' => 'fa',
                                        default => 'wa',
                                    };
                                    $logIcTxt = match ($log->status) {
                                        'success' => '✓',
                                        'failed' => '✕',
                                        default => '!',
                                    };
                                    $logPill = match ($log->status) {
                                        'success' => 'ac',
                                        'failed' => 'er',
                                        default => 'dr',
                                    };
                                    $logPillLbl = match ($log->status) {
                                        'success' => 'OK',
                                        'failed' => 'ERROR',
                                        default => 'PENDING',
                                    };
                                @endphp
                                <div class="log-i">
                                    <div class="log-ic {{ $logIcCls }}">{{ $logIcTxt }}</div>
                                    <div class="log-b">
                                        <div class="log-t">{{ $log->message }}</div>
                                        <div class="log-m">{{ $log->created_at?->format('M d, g:i A') }} ·
                                            {{ $log->trigger_type }} · {{ $log->version_tag }}</div>
                                    </div>
                                    <span
                                        style="font-size:11px;font-weight:600;color:#6b7280">{{ $log->duration_ms ? round($log->duration_ms / 1000, 1) . 's' : '—' }}</span>
                                    <span class="pill {{ $logPill }}"
                                        style="margin-left:8px">{{ $logPillLbl }}</span>
                                </div>
                            @empty
                                <div style="text-align:center;color:#9ca3af;font-size:12px;padding:20px">No run logs yet
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Bottom bar (basic tab only) -->
                    <div class="ai-bb">
                        <button class="bsav" style="padding:9px 26px;font-size:13px" onclick="saveAgent()">💾 Save Agent</button>
                    </div>
                @else
                    <!-- Empty state -->
                    <div
                        style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:60%;color:#9ca3af">
                        <div style="font-size:48px;margin-bottom:16px">🤖</div>
                        <div style="font-size:16px;font-weight:600;color:#374151;margin-bottom:8px">No Agent Selected</div>
                        <div style="font-size:13px">Create a new skill or select one from the left panel</div>
                        <button class="btn-n" style="margin-top:20px" onclick="openNewSkillModal()">+ Create New
                            Skill</button>
                    </div>
                @endif

            </div><!-- /ai-rp -->
        </div><!-- /ai-agent-wrap -->
    </div><!-- /content -->

    <!-- ── New Skill Modal ── -->
    <div class="modal fade" id="newSkillModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius:14px">
                <div class="modal-header" style="border-bottom:1px solid #e5e8ef;padding:18px 22px">
                    <h5 class="modal-title" style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700">Create New
                        Skill</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:22px">
                    <div class="fg" style="margin-bottom:14px">
                        <label class="fl">Skill Name *</label>
                        <input class="fc" type="text" id="ns-name" placeholder="e.g. Sales Analyzer">
                    </div>
                    <div class="fg" style="margin-bottom:14px">
                        <label class="fl">User Type</label>
                        <select class="fc" id="ns-utype">
                            <option value="vendor" @selected($userType === 'vendor')>Vendor</option>
                            <option value="admin" @selected($userType === 'admin')>Admin</option>
                            <option value="user" @selected($userType === 'user')>User</option>
                        </select>
                    </div>
                    <div class="fg">
                        <label class="fl">Skill Type</label>
                        <select class="fc" id="ns-stype">
                            <option value="analytics">Analytics</option>
                            <option value="chatbot">Chatbot</option>
                            <option value="seo">SEO</option>
                            <option value="pdf_analysis">PDF Analysis</option>
                            <option value="excel_generator">Excel Generator</option>
                            <option value="inventory">Inventory</option>
                            <option value="billing">Billing</option>
                            <option value="hr_assistant">HR Assistant</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e8ef;padding:14px 22px">
                    <button class="bdel" data-dismiss="modal">Cancel</button>
                    <button class="bsav" onclick="submitNewSkill()">Create Skill</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Version Bump Modal ── -->
    <div class="modal fade" id="versionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="border-radius:14px">
                <div class="modal-header" style="border-bottom:1px solid #e5e8ef;padding:18px 22px">
                    <h5 class="modal-title" style="font-family:'Plus Jakarta Sans',sans-serif;font-weight:700">New Version
                    </h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:22px">
                    <div class="fg" style="margin-bottom:14px">
                        <label class="fl">Version Tag *</label>
                        <input class="fc" type="text" id="vm-tag" placeholder="e.g. v1.3">
                    </div>
                    <div class="fg">
                        <label class="fl">Changelog / Notes</label>
                        <textarea class="fc" id="vm-log" placeholder="Describe what changed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e8ef;padding:14px 22px">
                    <button class="bdel" data-dismiss="modal">Cancel</button>
                    <button class="bsav" onclick="submitVersion()">Save Version</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        const AGENT_ID = {{ $a?->id ?? 'null' }};
        const USER_TYPE = '{{ $userType }}';
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;

        // ── API helper ──
        function api(url, method, data) {
            return fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF
                },
                body: data ? JSON.stringify(data) : undefined
            }).then(r => r.json());
        }

        function toast(msg, ok = true) {
            // Simple alert — replace with toastr if available
            const t = document.createElement('div');
            t.style =
                `position:fixed;bottom:24px;right:24px;z-index:9999;padding:11px 18px;border-radius:10px;font-size:13px;font-weight:600;font-family:'Plus Jakarta Sans',sans-serif;box-shadow:0 4px 16px rgba(0,0,0,.15);background:${ok?'#d1fae5':'#fee2e2'};color:${ok?'#059669':'#ef4444'}`;
            t.textContent = (ok ? '✓ ' : '✕ ') + msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 3000);
        }

        // ── Skill type chip sync ──
        function setSkillType(val, el) {
            document.getElementById('ag-stype').value = val;
            document.querySelectorAll('.chip').forEach(c => c.classList.remove('on'));
            el.classList.add('on');
        }

        // ── Environment switcher ──
        function changeEnv(sel) {
            sel.className = 'env-select ' + sel.value;
            const warn = document.getElementById('env-warn');
            const badge = document.getElementById('env-badge');
            if (sel.value === 'prod') {
                warn.className = 'ib red';
                warn.innerHTML =
                    '🔴 <strong>Production mode active.</strong> This agent runs on LIVE data. Switch to Sandbox for safe testing.';
                badge.textContent = 'Production';
                badge.className = 'pill er';
            } else if (sel.value === 'staging') {
                warn.className = 'ib amber';
                warn.innerHTML = '🟡 <strong>Staging mode.</strong> Uses staging database — safe but not real data.';
                badge.textContent = 'Staging';
                badge.className = 'pill dr';
            } else {
                warn.className = 'ib green';
                warn.innerHTML = '🟢 <strong>Sandbox mode.</strong> Safe to test — no real data will be affected.';
                badge.textContent = 'Sandbox';
                badge.className = 'pill ac';
            }
        }

        // ── Role toggle ──
        function toggleRole(el) {
            el.classList.toggle('on');
            el.querySelector('.role-check').innerHTML = el.classList.contains('on') ?
                '<svg width="9" height="9" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>' :
                '';
        }

        // ── Destructive task toggle ──
        function toggleDestructive(tog) {
            tog.classList.toggle('on');
            const row = tog.closest('.tkrow');
            const isOn = tog.classList.contains('on');
            const pill = row.querySelector('.pill');
            const confRow = row.querySelector('.tk-conf-row');
            const warnRow = row.querySelector('.tk-warn');
            const label = tog.closest('label');
            if (isOn) {
                row.classList.add('destructive');
                if (pill) {
                    pill.textContent = 'Destructive';
                    pill.className = 'pill er';
                }
                if (confRow) confRow.classList.remove('hidden');
                if (warnRow) warnRow.style.display = 'flex';
                if (label) label.style.color = '#ef4444';
            } else {
                row.classList.remove('destructive');
                if (pill) {
                    pill.textContent = 'Active';
                    pill.className = 'pill ac';
                }
                if (confRow) confRow.classList.add('hidden');
                if (warnRow) warnRow.style.display = 'none';
                if (label) label.style.color = '#6b7280';
            }
        }

        // ── API table — add row ──
        function addApi() {
            const tb = document.getElementById('api-tb');
            // Remove empty-state row if present
            const emptyRow = tb.querySelector('td[colspan]');
            if (emptyRow) emptyRow.closest('tr').remove();
            const count = tb.querySelectorAll('tr').length + 1;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class="rn">${count}</td>
<td><input class="ti" type="text" placeholder="API Name" style="min-width:110px"></td>
<td><input class="ti" type="text" placeholder="/api/v1/ai-internal/..." style="min-width:180px"></td>
<td><select class="ts" style="width:76px"><option>GET</option><option selected>POST</option><option>PUT</option><option>DELETE</option></select></td>
<td><span class="pill ac">Active</span></td>
<td><button class="brm" onclick="this.closest('tr').remove()">✕</button></td>`;
            tb.appendChild(tr);
            tr.querySelector('input').focus();
        }


        // ── Task — add row ──
        function addTask() {
            const emptyDiv = document.querySelector('#task-list div[style*="padding:20px"]');
            if (emptyDiv) emptyDiv.remove();
            const d = document.createElement('div');
            d.className = 'tkrow';
            d.innerHTML = `
<div class="tk-top">
    <span class="tk-drag">⠿</span>
    <input class="ti tk-name" type="text" placeholder="Task name" style="flex:1;min-width:150px;font-weight:600">
    <select class="ts" style="width:120px"><option>Scheduled</option><option>On Demand</option><option>Trigger</option><option>Manual</option></select>
    <select class="ts" style="width:100px"><option>Analytics</option><option>PDF</option><option>Excel</option><option>Chatbot</option><option>SEO</option></select>
    <label style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#6b7280;cursor:pointer;white-space:nowrap">
        <div class="tog" onclick="toggleDestructive(this)"></div> Destructive?
    </label>
    <span class="pill dr">Draft</span>
    <button class="brm" onclick="this.closest('.tkrow').remove()">✕</button>
</div>
<input class="ti" type="text" placeholder="Describe what this task does..." style="margin-top:6px;color:#6b7280;font-size:11.5px">
<div class="tk-warn" style="display:none">⚠️ This task is marked destructive. Vendor must approve before execution.</div>
<div class="tk-conf-row hidden">
    <span style="font-size:13px">✅</span>
    <span class="tk-conf-label">Require vendor approval before running this task</span>
    <div class="tog gr on" onclick="this.classList.toggle('on')"></div>
</div>`;
            document.getElementById('task-list').appendChild(d);
            d.querySelector('input').focus();
        }

        // ── Schema functions ──
        let schIdx = 1000;

        function addSchema() {
            const emptyDiv = document.querySelector('#schema-list div[style*="padding:20px"]');
            if (emptyDiv) emptyDiv.remove();
            schIdx++;
            const id = 'schema-' + schIdx,
                rid = 'res-' + schIdx;
            const div = document.createElement('div');
            div.className = 'schema-entry';
            div.style = 'margin-bottom:16px;border:1.5px solid #e5e8ef;border-radius:10px;padding:14px';
            div.innerHTML = `
<div style="display:flex;align-items:center;gap:10px;margin-bottom:11px">
    <input class="fc" type="text" placeholder="function_name" style="flex:1;font-family:'Courier New',monospace;font-size:12.5px;font-weight:700;color:#7c3aed">
    <input class="fc" type="text" placeholder="Short description" style="flex:2">
    <button class="brm" onclick="this.closest('.schema-entry').remove()">✕</button>
</div>
<div class="schema-box">
    <span class="schema-lang">JSON SCHEMA</span>
    <textarea class="schema-ta" id="${id}">{
  "name": "function_name",
  "parameters": {
    "type": "object",
    "properties": {
      "param1": {"type": "string", "description": "Description"}
    },
    "required": ["param1"]
  }
}</textarea>
</div>
<div class="schema-actions">
    <button class="schema-btn validate" onclick="validateSchema('${id}','${rid}')">✓ Validate JSON</button>
    <button class="schema-btn format"   onclick="formatSchema('${id}','${rid}')">⟳ Format</button>
</div>
<div class="schema-result" id="${rid}"></div>`;
            document.getElementById('schema-list').appendChild(div);
            div.querySelector('input').focus();
        }

        function validateSchema(taId, resId) {
            const ta = document.getElementById(taId),
                res = document.getElementById(resId);
            try {
                JSON.parse(ta.value);
                res.className = 'schema-result ok';
                res.textContent = '✓ Valid JSON — Schema looks good!';
            } catch (e) {
                res.className = 'schema-result err';
                res.textContent = '✕ Invalid JSON: ' + e.message;
            }
        }

        function formatSchema(taId, resId) {
            const ta = document.getElementById(taId),
                res = document.getElementById(resId);
            try {
                ta.value = JSON.stringify(JSON.parse(ta.value), null, 2);
                res.className = 'schema-result ok';
                res.textContent = '✓ Formatted!';
            } catch (e) {
                res.className = 'schema-result err';
                res.textContent = '✕ ' + e.message;
            }
        }

        // ── Data collectors ──
        function getAllowedRoles() {
            return [...document.querySelectorAll('.role-item.on')].map(el => el.dataset.role);
        }

        function getNotifSettings() {
            const keys = ['telegram', 'email', 'in_app', 'sms', 'report', 'webhook'];
            return Object.fromEntries(keys.map(k => {
                const el = document.getElementById('notif-' + k);
                return [k, el ? el.classList.contains('on') : false];
            }));
        }

        function getEscalationRules() {
            const keys = ['auth_error', 'write_error', 'timeout', 'rate_limit'];
            return Object.fromEntries(keys.map(k => {
                const el = document.getElementById('esc-' + k);
                return [k, el ? el.classList.contains('on') : false];
            }));
        }

        function getApiTools() {
            return [...document.querySelectorAll('#api-tb tr')].map(tr => {
                const inputs = tr.querySelectorAll('input.ti');
                const selects = tr.querySelectorAll('select.ts');

                if (!inputs[0]?.value) return null;
                return {
                    api_name: inputs[0]?.value,
                    endpoint: inputs[1]?.value,
                    method: selects[0]?.value,
                    status: 'active',
                };
            }).filter(Boolean);
        }

        function getFunctions() {
            return [...document.querySelectorAll('#schema-list .schema-entry')].map(entry => {
                const inputs = entry.querySelectorAll('input.fc');
                const ta = entry.querySelector('textarea.schema-ta');
                if (!inputs[0]?.value) return null;
                let schema = null;
                try {
                    schema = JSON.parse(ta?.value);
                } catch (e) {}
                return {
                    function_name: inputs[0]?.value,
                    description: inputs[1]?.value,
                    json_schema: schema
                };
            }).filter(Boolean);
        }

        function getTasks() {
            return [...document.querySelectorAll('#task-list .tkrow')].map(row => {
                const name = row.querySelector('.tk-name')?.value;
                if (!name) return null;
                const selects = row.querySelectorAll('select.ts');
                const inputs = row.querySelectorAll('input.ti');
                const isDestr = row.querySelector('.tk-top .tog')?.classList.contains('on') ? 1 : 0;
                const confTog = row.querySelector('.tk-conf-row .tog');
                return {
                    task_name: name,
                    description: inputs[inputs.length - 1]?.value,
                    trigger_type: selects[0]?.value?.toLowerCase().replace(' ', '_'),
                    skill_category: selects[1]?.value?.toLowerCase(),
                    is_destructive: isDestr,
                    require_approval: confTog?.classList.contains('on') ? 1 : 0,
                    status: 'active',
                };
            }).filter(Boolean);
        }

        // ── Save Agent ──
        async function saveAgent() {
            if (!AGENT_ID) {
                toast('No agent selected', false);
                return;
            }
            const btns = document.querySelectorAll('.bsav');
            btns.forEach(b => {
                b.disabled = true;
                b.textContent = 'Saving...';
            });  
            try { 
                const data = {
                    name: document.getElementById('ag-name').value,
                    description: document.getElementById('ag-desc').value,
                    user_type: document.getElementById('ag-utype').value,
                    skill_type: document.getElementById('ag-stype').value,
                    status: document.getElementById('ag-status').value,
                    prompt: document.getElementById('ag-prompt').value,
                    environment: document.getElementById('env-sel').value,
                    ai_provider: document.getElementById('ag-provider').value,
                    ai_model: document.getElementById('ag-model').value,
                    max_tokens: document.getElementById('ag-tokens').value,
                    temperature: document.getElementById('ag-temp').value,
                    top_p: document.getElementById('ag-topp').value, 
                    api_key_override: document.getElementById('ag-apikey').value,
                    requires_auth: document.getElementById('ag-auth').value === 'yes' ? 1 : 0,
                    session_validation: document.getElementById('ag-sess').value,
                    allowed_roles: getAllowedRoles(), 
                    conv_history_enabled: document.getElementById('ag-convhist').classList.contains('on') ? 1 : 0,
                    cross_session_memory: document.getElementById('ag-crossmem').classList.contains('on') ? 1 : 0,
                    inject_vendor_profile: document.getElementById('ag-inject').classList.contains('on') ? 1 : 0,
                    max_history_messages: document.getElementById('ag-maxhist').value,
                    context_window: document.getElementById('ag-ctxwin').value,
                    retry_count: document.getElementById('ag-retry').value,
                    backoff_strategy: document.getElementById('ag-backoff').value,
                    fallback_message: document.getElementById('ag-fallback').value,
                    escalation_rules: getEscalationRules(),
                    notification_settings: getNotifSettings(),
                    api_tools: getApiTools(),
                    functions: getFunctions(),
                    tasks: getTasks(),
                };
                const res = await api(`/admin/agent/${AGENT_ID}/update`, 'POST', data);
                if (res.status) {
                    toast('Agent saved successfully!');
                    document.getElementById('agent-title').textContent = data.name;
                } else {
                    toast(res.message || 'Save failed', false);
                }
            } catch (e) {
                toast('Network error — could not save', false);
            } finally {
                btns.forEach(b => {
                    b.disabled = false;
                    b.textContent = 'Save Changes';
                });
                document.querySelector('.ai-bb .bsav').textContent = '💾 Save Agent';
            }
        }

        // ── Delete Agent ──
        async function deleteAgent() {
            if (!AGENT_ID) return;
            if (!confirm('Delete this agent? This cannot be undone.')) return;
            const res = await api(`/admin/agent/${AGENT_ID}`, 'DELETE');
            if (res.status) {
                window.location.href = '{{ route('admin.agent.index', ['user_type' => $userType ?? 'vendor']) }}';
            } else {
                toast('Delete failed', false);
            }
        }

        // ── Version modal ──
        function openVersionModal() {
            $('#versionModal').modal('show');
        }
        async function submitVersion() {
            if (!AGENT_ID) return;
            const tag = document.getElementById('vm-tag').value;
            const log = document.getElementById('vm-log').value;
            if (!tag) {
                alert('Version tag is required');
                return;
            }
            const res = await api(`/admin/agent/${AGENT_ID}/version`, 'POST', {
                version_tag: tag,
                changelog: log
            });
            if (res.status) {
                $('#versionModal').modal('hide');
                document.getElementById('ver-display').textContent = tag;
                document.getElementById('ver-updated').textContent = 'Last updated: just now · by Admin';
                toast('Version saved!');
            } else {
                toast('Failed to save version', false);
            }
        }

        // ── New Skill modal ──
        function openNewSkillModal() {
            $('#newSkillModal').modal('show');
        }
        async function submitNewSkill() {
            const name = document.getElementById('ns-name').value.trim();
            if (!name) {
                alert('Skill name is required');
                return;
            }
            const utype = document.getElementById('ns-utype').value;
            const res = await api('/admin/agent/store', 'POST', {
                name,
                user_type: utype,
                skill_type: document.getElementById('ns-stype').value,
                status: 'draft',
            });
            if (res.status && res.agent) {
                $('#newSkillModal').modal('hide');
                window.location.href = `/admin/agent?user_type=${utype}&agent_id=${res.agent.id}`;
            } else {
                toast('Could not create skill', false);
            }
        }

        // ── Right-panel section tabs ──
        // ── AI provider → model options ──────────────────────
        const PROVIDER_MODELS = {
            anthropic: [
                { value: 'claude-opus-4-6',           label: 'Claude Opus 4.6' },
                { value: 'claude-sonnet-4-6',          label: 'Claude Sonnet 4.6' },
                { value: 'claude-haiku-4-5-20251001',  label: 'Claude Haiku 4.5' },
            ],
            openai: [
                { value: 'gpt-4o',        label: 'GPT-4o' },
                { value: 'gpt-4o-mini',   label: 'GPT-4o Mini' },
                { value: 'gpt-4-turbo',   label: 'GPT-4 Turbo' },
                { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo' },
            ],
            gemini: [
                { value: 'gemini-2.0-flash',   label: 'Gemini 2.0 Flash' },
                { value: 'gemini-1.5-pro',     label: 'Gemini 1.5 Pro' },
                { value: 'gemini-1.5-flash',   label: 'Gemini 1.5 Flash' },
            ],
        };

        function updateModelSelect(provider, currentModel) {
            const sel  = document.getElementById('ag-model');
            const list = PROVIDER_MODELS[provider] || PROVIDER_MODELS['anthropic'];
            sel.innerHTML = list.map(m =>
                `<option value="${m.value}"${m.value === currentModel ? ' selected' : ''}>${m.label}</option>`
            ).join('');
            // If saved model doesn't exist in new list, default to first
            if (!list.find(m => m.value === currentModel)) {
                sel.value = list[0].value;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const provSel = document.getElementById('ag-provider');
            const savedModel = @json($a->ai_model ?? '');
            updateModelSelect(provSel ? provSel.value : 'anthropic', savedModel);
            if (provSel) {
                provSel.addEventListener('change', function () {
                    updateModelSelect(this.value, '');
                });
            }
        });

        function switchRpTab(tab) {
            document.querySelectorAll('.ai-rp .card[data-tab]').forEach(c => {
                c.style.display = c.dataset.tab === tab ? '' : 'none';
            });
            document.querySelectorAll('.rp-tab-btn').forEach(b => {
                b.classList.toggle('active', b.dataset.tab === tab);
            });
            const bb = document.querySelector('.ai-bb');
            if (bb) bb.style.display = tab === 'basic' ? '' : 'none';
        }

        document.addEventListener('DOMContentLoaded', () => switchRpTab('basic'));

        // ── Test console ──
        function openTest() {
            switchRpTab('test');
            setTimeout(() => {
                document.getElementById('test-section')?.scrollIntoView({ behavior: 'smooth' });
            }, 50);
        }
 
        async function runTest() {
            const inp = document.getElementById('con-in').value.trim();
            if (!inp) {
                toast('Enter a test message first', false);
                return;
            }

            const out = document.getElementById('con-out');
            const btn = document.querySelector('.con-run');
            const env = document.getElementById('env-sel').value;
            const agentName = document.getElementById('ag-name')?.value || 'Agent';

            out.style.color = '#f59e0b';
            out.textContent = `// Running ${agentName} in ${env.toUpperCase()} mode...\n// Connecting to AI service...`;
            btn.disabled = true;
            btn.textContent = '⏳ Running...';

            try {
                const res = await api(`/admin/agent/${AGENT_ID}/test`, 'POST', {
                    message: inp
                });
                if (res.success) {
                    out.style.color = '#a3e635';
                    out.textContent =
                        `> Agent: ${agentName}\n> Environment: ${env.toUpperCase()}\n> Input: "${inp}"\n\n📊 Response:\n${res.message}\n\n> Logged to run history`;
                } else {
                    out.style.color = '#ef4444';
                    out.textContent =
                        `> Error: ${res.message || 'AI service returned an error'}${res.detail ? '\n\n' + res.detail : ''}`;
                }
            } catch (e) {
                out.style.color = '#ef4444';
                out.textContent = `> Network error: ${e.message}`;
            } finally {
                btn.disabled = false;
                btn.textContent = '▶ Run Agent';
            }
        }
    </script>
@endpush









