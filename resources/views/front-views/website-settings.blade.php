@extends('layouts.vendor.app')

@section('title', 'Website & Domain Settings')

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --dark: #1a1f36;
        --dark-blue: #0f1419;
        --soft-white: #f8f9fd;
        --border-color: #e4e7f1;
        --text-primary: #2d3748;
        --text-secondary: #718096;
        --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
        --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.12);
        --radius-sm: 12px;
        --radius-md: 16px;
        --radius-lg: 24px;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--soft-white);
        color: var(--text-primary);
        line-height: 1.6;
    }

    .website-settings-wrapper {
        min-height: 100vh;
        padding: 40px 20px;
        background: var(--soft-white);
    }

    .settings-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Header */
    .settings-hero {
        background: white;
        border-radius: var(--radius-lg);
        padding: 48px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }

    .settings-hero::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 400px;
        height: 400px;
        background: var(--primary-gradient);
        opacity: 0.05;
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .settings-hero-content {
        position: relative;
        z-index: 1;
    }

    .settings-title {
        font-family: 'Sora', sans-serif;
        font-size: 42px;
        font-weight: 800;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        letter-spacing: -1px;
    }

    .settings-subtitle {
        font-size: 18px;
        color: var(--text-secondary);
        margin-bottom: 24px;
    }

    .breadcrumb-custom {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: var(--text-secondary);
    }

    .breadcrumb-custom a {
        color: #667eea;
        text-decoration: none;
        transition: color 0.2s;
    }

    .breadcrumb-custom a:hover {
        color: #764ba2;
    }

    /* Tab Navigation */
    .settings-tabs {
        background: white;
        border-radius: var(--radius-md);
        padding: 8px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        display: flex;
        gap: 8px;
        overflow-x: auto;
    }

    .settings-tab {
        flex: 1;
        min-width: 180px;
        padding: 16px 24px;
        border: none;
        background: transparent;
        border-radius: var(--radius-sm);
        font-family: 'DM Sans', sans-serif;
        font-size: 15px;
        font-weight: 600;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        white-space: nowrap;
    }

    .settings-tab:hover {
        background: #f7fafc;
        color: var(--text-primary);
    }

    .settings-tab.active {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .settings-tab-icon {
        font-size: 20px;
    }

    /* Main Grid */
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
    }

    /* Card Styles */
    .settings-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 36px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        transition: all 0.3s ease;
    }

    .settings-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .settings-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--border-color);
    }

    .settings-card-icon {
        width: 48px;
        height: 48px;
        background: var(--primary-gradient);
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .settings-card-title {
        font-family: 'Sora', sans-serif;
        font-size: 22px;
        font-weight: 700;
        color: var(--text-primary);
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 24px;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-label-icon {
        font-size: 16px;
    }

    .form-control {
        width: 100%;
        padding: 14px 18px;
        font-size: 15px;
        color: var(--text-primary);
        background: #f8f9fd;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-sm);
        transition: all 0.3s ease;
        font-family: 'DM Sans', sans-serif;
    }

    .form-control:hover {
        border-color: #cbd5e0;
        background: white;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .form-control::placeholder {
        color: #a0aec0;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    /* Domain Settings Specific */
    .domain-preview {
        background: linear-gradient(135deg, #f8f9fd 0%, #e9ecf5 100%);
        border-radius: var(--radius-md);
        padding: 24px;
        margin-bottom: 24px;
        border: 2px dashed var(--border-color);
    }

    .domain-preview-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .domain-preview-url {
        font-family: 'Monaco', 'Courier New', monospace;
        font-size: 20px;
        font-weight: 700;
        color: #667eea;
        word-break: break-all;
    }

    .domain-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 12px;
    }

    .domain-status.active {
        background: #d4f4dd;
        color: #0d6832;
    }

    .domain-status.pending {
        background: #fef3c7;
        color: #78350f;
    }

    .domain-status.inactive {
        background: #fee2e2;
        color: #991b1b;
    }

    .domain-instructions {
        background: #eff6ff;
        border-left: 4px solid #667eea;
        padding: 16px 20px;
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        margin-top: 20px;
    }

    .domain-instructions-title {
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 8px;
    }

    .domain-instructions ul {
        margin: 0;
        padding-left: 20px;
        color: #1e40af;
        font-size: 14px;
    }

    .domain-instructions li {
        margin-bottom: 6px;
    }

    /* Image Upload */
    .image-upload-wrapper {
        display: flex;
        gap: 24px;
        margin-top: 20px;
    }

    .image-upload-box {
        flex: 1;
        position: relative;
    }

    .image-upload-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 12px;
        display: block;
    }

    .image-upload-label span {
        color: #667eea;
        font-size: 12px;
    }

    .image-preview {
        width: 100%;
        height: 180px;
        border: 2px dashed var(--border-color);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fd;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .image-preview:hover {
        border-color: #667eea;
        background: white;
    }

    .image-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .image-upload-placeholder {
        text-align: center;
        color: var(--text-secondary);
    }

    .image-upload-placeholder-icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .image-upload-placeholder-text {
        font-size: 14px;
        font-weight: 600;
    }

    .image-upload-placeholder-hint {
        font-size: 12px;
        margin-top: 4px;
        opacity: 0.7;
    }

    /* Buttons */
    .btn-custom {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 28px;
        font-size: 15px;
        font-weight: 600;
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'DM Sans', sans-serif;
        position: relative;
        overflow: hidden;
    }

    .btn-custom::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-custom:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-custom span {
        position: relative;
        z-index: 1;
    }

    .btn-primary {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
    }

    .btn-secondary {
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
    }

    .btn-secondary:hover {
        background: #667eea;
        color: white;
    }

    .btn-success {
        background: var(--success-gradient);
        color: white;
        box-shadow: 0 4px 14px rgba(79, 172, 254, 0.4);
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(79, 172, 254, 0.5);
    }

    .btn-block {
        width: 100%;
        justify-content: center;
    }

    /* Phone Numbers Section */
    .phone-section {
        background: linear-gradient(135deg, #f8f9fd 0%, #e9ecf5 100%);
        padding: 24px;
        border-radius: var(--radius-md);
        margin-bottom: 24px;
        border: 1px solid var(--border-color);
    }

    .phone-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .phone-item {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
        animation: slideIn 0.4s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .phone-item .form-control {
        flex: 1;
        background: white;
    }

    .btn-remove {
        padding: 14px 18px;
        background: #fee2e2;
        color: #991b1b;
        border: none;
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .btn-remove:hover {
        background: #fecaca;
        transform: scale(1.05);
    }

    /* Social Media */
    .social-inputs {
        display: grid;
        gap: 16px;
    }

    .social-input-group {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8f9fd;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-sm);
        padding: 4px 4px 4px 18px;
        transition: all 0.3s ease;
    }

    .social-input-group:hover {
        border-color: #cbd5e0;
        background: white;
    }

    .social-input-group:focus-within {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .social-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .social-input-group input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 10px 12px;
        font-size: 15px;
        color: var(--text-primary);
    }

    .social-input-group input:focus {
        outline: none;
    }

    /* Toggle Switch */
    .toggle-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        background: #f8f9fd;
        border-radius: var(--radius-sm);
        margin-bottom: 16px;
    }

    .toggle-label {
        font-weight: 600;
        color: var(--text-primary);
    }

    .toggle-switch {
        position: relative;
        width: 56px;
        height: 30px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e0;
        transition: 0.4s;
        border-radius: 50px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input:checked + .toggle-slider {
        background: var(--primary-gradient);
    }

    .toggle-switch input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }

    /* Info Box */
    .info-box {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 16px 20px;
        border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        margin-top: 20px;
        font-size: 14px;
        color: #78350f;
    }

    .info-box-icon {
        font-size: 18px;
        margin-right: 8px;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .settings-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .settings-hero {
            padding: 32px 24px;
        }

        .settings-title {
            font-size: 32px;
        }

        .settings-tabs {
            flex-direction: column;
        }

        .settings-tab {
            min-width: auto;
        }

        .settings-card {
            padding: 24px;
        }

        .image-upload-wrapper {
            flex-direction: column;
        }
    }

    /* Map Styles */
    #map {
        height: 400px;
        border-radius: var(--radius-md);
        margin-top: 16px;
        border: 2px solid var(--border-color);
        overflow: hidden;
    }

    #searchInput {
        margin-bottom: 16px;
    }

    /* Custom Select */
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }

    /* Loading State */
    .btn-custom.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    .btn-custom.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Success Message */
    .success-message {
        background: #d4f4dd;
        color: #0d6832;
        padding: 16px 20px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.4s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
</style>
@endpush

@section('content')
<div class="website-settings-wrapper">
    <div class="settings-container">
        <!-- Hero Header -->
        <div class="settings-hero">
            <div class="settings-hero-content">
                <h1 class="settings-title">Website & Domain Settings</h1>
                <p class="settings-subtitle">Configure your personal domain, branding, and store information</p>
                <div class="breadcrumb-custom">
                    <a href="{{ route('vendor.dashboard') }}">Dashboard</a>
                    <span>/</span>
                    <span>Website Settings</span>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="settings-tabs">
            <button class="settings-tab active" data-tab="domain">
                <span class="settings-tab-icon">🌐</span>
                <span>Domain & Website</span>
            </button>
            <button class="settings-tab" data-tab="business">
                <span class="settings-tab-icon">🏢</span>
                <span>Business Info</span>
            </button>
            <button class="settings-tab" data-tab="branding">
                <span class="settings-tab-icon">🎨</span>
                <span>Branding</span>
            </button>
            <button class="settings-tab" data-tab="seo">
                <span class="settings-tab-icon">📊</span>
                <span>SEO & Meta</span>
            </button>
        </div>

        <!-- Domain & Website Tab -->
        <div id="domain-tab" class="tab-content active">
            <div class="settings-grid">
                <!-- Domain Configuration -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">🌐</div>
                        <h2 class="settings-card-title">Domain Configuration</h2>
                    </div>

                    <form id="domainForm" method="POST" action="{{ route('vendor.settings.update-domain') }}">
                        @csrf
                        
                        <!-- Domain Preview -->
                        <div class="domain-preview">
                            <div class="domain-preview-label">Your Website URL</div>
                            <div class="domain-preview-url" id="domainPreview">
                                {{ $storeConfig?->custom_domain ?? $store->slug }}.mychitti.net
                            </div>
                            <span class="domain-status {{ $storeConfig?->domain_status ?? 'active' }}">
                                <span>●</span>
                                <span>{{ ucfirst($storeConfig?->domain_status ?? 'Active') }}</span>
                            </span>
                        </div>

                        <!-- Custom Subdomain -->
                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">🔗</span>
                                <span>Custom Subdomain</span>
                            </label>
                            <input 
                                type="text" 
                                name="custom_domain" 
                                id="customDomain"
                                class="form-control" 
                                value="{{ $storeConfig?->custom_domain ?? $store->slug }}"
                                placeholder="yourstore"
                            >
                            <small style="color: var(--text-secondary); margin-top: 8px; display: block;">
                                Your website will be accessible at: <strong><span id="domainHint">yourstore</span>.mychitti.net</strong>
                            </small>
                        </div>

                        <!-- Custom Domain (Future) -->
                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">🌍</span>
                                <span>Custom Domain (Coming Soon)</span>
                            </label>
                            <input 
                                type="text" 
                                name="personal_domain" 
                                class="form-control" 
                                value="{{ $storeConfig?->personal_domain ?? '' }}"
                                placeholder="www.yourdomain.com"
                                disabled
                            >
                            <div class="info-box">
                                <span class="info-box-icon">ℹ️</span>
                                Custom domain feature will be available soon. You'll be able to use your own domain like www.yourstore.com
                            </div>
                        </div>

                        <!-- Website Status Toggle -->
                        <div class="toggle-wrapper">
                            <span class="toggle-label">Website Active</span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="website_active" {{ ($storeConfig?->website_active ?? true) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <button type="submit" class="btn-custom btn-primary btn-block">
                            <span>💾</span>
                            <span>Save Domain Settings</span>
                        </button>
                    </form>

                    <!-- DNS Instructions -->
                    <div class="domain-instructions">
                        <div class="domain-instructions-title">📌 Quick Setup Guide</div>
                        <ul>
                            <li>Choose a unique subdomain for your store</li>
                            <li>Your website will be live at yourstore.mychitti.net</li>
                            <li>Custom domain support coming soon</li>
                        </ul>
                    </div>
                </div>

                <!-- Inventory Position -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">📦</div>
                        <h2 class="settings-card-title">Layout Settings</h2>
                    </div>

                    <form method="POST" action="{{ route('vendor.settings.update-layout') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">📍</span>
                                <span>Inventory Items Position</span>
                            </label>
                            <select name="inventory_items_position" class="form-control">
                                <option value="above" {{ ($storeConfig?->inventory_items_position ?? 'above') == 'above' ? 'selected' : '' }}>
                                    Above Service Section
                                </option>
                                <option value="below" {{ ($storeConfig?->inventory_items_position ?? 'above') == 'below' ? 'selected' : '' }}>
                                    Below Service Section
                                </option>
                            </select>
                            <div class="info-box">
                                <span class="info-box-icon">💡</span>
                                Choose where inventory items appear relative to your services section
                            </div>
                        </div>

                        <button type="submit" class="btn-custom btn-success btn-block">
                            <span>✓</span>
                            <span>Update Layout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Business Info Tab -->
        <div id="business-tab" class="tab-content">
            <div class="settings-grid">
                <!-- Contact Information -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">📞</div>
                        <h2 class="settings-card-title">Contact Information</h2>
                    </div>

                    <form method="POST" action="{{ route('vendor.settings.webpage-update') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">🏪</span>
                                <span>Website Name</span>
                            </label>
                            <input 
                                type="text" 
                                name="website_name" 
                                class="form-control" 
                                value="{{ $storeConfig?->webpage_name ?? $store->name }}"
                                placeholder="Enter your website name"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">✉️</span>
                                <span>Email Address</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control" 
                                value="{{ $storeConfig?->webpage_email ?? $store->email }}"
                                placeholder="contact@mail.com"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">📍</span>
                                <span>Business Address</span>
                            </label>
                            <textarea 
                                name="address" 
                                class="form-control" 
                                placeholder="Enter your complete address"
                            >{{ $storeConfig?->webpage_address ?? $store->address }}</textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">🆔</span>
                                <span>GST Number</span>
                            </label>
                            <input 
                                type="text" 
                                name="gst_number" 
                                class="form-control" 
                                value="{{ $storeConfig?->gst_number ?? '' }}"
                                placeholder="37Aqbpj6658a1zz"
                            >
                        </div>

                        <!-- Phone Numbers -->
                        <div class="phone-section">
                            <div class="phone-header">
                                <label class="form-label" style="margin-bottom: 0;">
                                    <span class="form-label-icon">📱</span>
                                    <span>Phone Numbers</span>
                                </label>
                                <button type="button" class="btn-custom btn-secondary" onclick="addPhoneNumber()" style="padding: 10px 20px;">
                                    <span>+</span>
                                    <span>Add</span>
                                </button>
                            </div>
                            <div id="phoneContainer">
                                @php
                                    $phones = $storeConfig?->webpage_phones;
                                    if ($phones) {
                                        $phones = json_decode($phones, true);
                                    } else {
                                        $phones = [$store->phone ?? ''];
                                    }
                                @endphp
                                @foreach($phones as $phone)
                                <div class="phone-item">
                                    <input 
                                        type="text" 
                                        name="phone[]" 
                                        class="form-control intl_input" 
                                        value="{{ $phone }}"
                                        placeholder="+91 1234567890"
                                    >
                                    <button type="button" class="btn-remove" onclick="removePhoneNumber(this)">
                                        🗑️
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="btn-custom btn-primary btn-block">
                            <span>💾</span>
                            <span>Save Contact Information</span>
                        </button>
                    </form>
                </div>

                <!-- Location Map -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">🗺️</div>
                        <h2 class="settings-card-title">Location</h2>
                    </div>

                    <form method="POST" action="{{ route('vendor.settings.update-location') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="form-label-icon">🔍</span>
                                <span>Search Location</span>
                            </label>
                            <input 
                                type="text" 
                                id="searchInput" 
                                class="form-control" 
                                placeholder="Search for your business location"
                            >
                        </div>

                        <input type="hidden" name="latitude" id="latitude" value="{{ $storeConfig?->webpage_latitude ?? $store->latitude }}">
                        <input type="hidden" name="longitude" id="longitude" value="{{ $storeConfig?->webpage_longitude ?? $store->longitude }}">

                        <div id="map"></div>

                        <button type="submit" class="btn-custom btn-success btn-block" style="margin-top: 20px;">
                            <span>📍</span>
                            <span>Update Location</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Branding Tab -->
        <div id="branding-tab" class="tab-content">
            <div class="settings-grid">
                <!-- Logo & Images -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">🎨</div>
                        <h2 class="settings-card-title">Brand Images</h2>
                    </div>

                    <form method="POST" action="{{ route('vendor.settings.update-branding') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="image-upload-wrapper">
                            <!-- Logo -->
                            <div class="image-upload-box">
                                <label class="image-upload-label">
                                    Brand Logo <span>(500×500 PX)</span>
                                </label>
                                <label for="logoUpload" class="image-preview">
                                    <img 
                                        id="logoPreview" 
                                        src="{{ $store->logo ? asset('storage/app/public/store/' . $store->logo) : '' }}" 
                                        style="{{ $store->logo ? '' : 'display:none;' }}"
                                    >
                                    <div class="image-upload-placeholder" style="{{ $store->logo ? 'display:none;' : '' }}">
                                        <div class="image-upload-placeholder-icon">🖼️</div>
                                        <div class="image-upload-placeholder-text">Upload Logo</div>
                                        <div class="image-upload-placeholder-hint">Click to browse</div>
                                    </div>
                                </label>
                                <input 
                                    type="file" 
                                    id="logoUpload" 
                                    name="logo" 
                                    accept="image/*" 
                                    style="display: none;"
                                    onchange="previewImage(this, 'logoPreview')"
                                >
                            </div>

                            <!-- Cover Photo -->
                            <div class="image-upload-box">
                                <label class="image-upload-label">
                                    Cover Photo <span>(1050×500 PX)</span>
                                </label>
                                <label for="coverUpload" class="image-preview">
                                    <img 
                                        id="coverPreview" 
                                        src="{{ $store->cover_photo ? asset('storage/app/public/store/' . $store->cover_photo) : '' }}" 
                                        style="{{ $store->cover_photo ? '' : 'display:none;' }}"
                                    >
                                    <div class="image-upload-placeholder" style="{{ $store->cover_photo ? 'display:none;' : '' }}">
                                        <div class="image-upload-placeholder-icon">🖼️</div>
                                        <div class="image-upload-placeholder-text">Upload Cover</div>
                                        <div class="image-upload-placeholder-hint">Click to browse</div>
                                    </div>
                                </label>
                                <input 
                                    type="file" 
                                    id="coverUpload" 
                                    name="cover_photo" 
                                    accept="image/*" 
                                    style="display: none;"
                                    onchange="previewImage(this, 'coverPreview')"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-custom btn-primary btn-block" style="margin-top: 24px;">
                            <span>🎨</span>
                            <span>Update Branding</span>
                        </button>
                    </form>
                </div>

                <!-- Social Media -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon">📱</div>
                        <h2 class="settings-card-title">Social Media</h2>
                    </div>

                    <form method="POST" action="{{ route('vendor.business-settings.update-social-media', [$store->id]) }}">
                        @csrf
                        
                        <div class="social-inputs">
                            <div class="social-input-group">
                                <div class="social-icon">📘</div>
                                <input 
                                    type="url" 
                                    name="fb_url" 
                                    value="{{ $store->fb_url ?? '' }}" 
                                    placeholder="Facebook URL"
                                >
                            </div>

                            <div class="social-input-group">
                                <div class="social-icon">📸</div>
                                <input 
                                    type="url" 
                                    name="insta_url" 
                                    value="{{ $store->insta_url ?? '' }}" 
                                    placeholder="Instagram URL"
                                >
                            </div>

                            <div class="social-input-group">
                                <div class="social-icon">🐦</div>
                                <input 
                                    type="url" 
                                    name="twitter_url" 
                                    value="{{ $store->twitter_url ?? '' }}" 
                                    placeholder="Twitter URL"
                                >
                            </div>

                            <div class="social-input-group">
                                <div class="social-icon">📌</div>
                                <input 
                                    type="url" 
                                    name="pinterest_url" 
                                    value="{{ $store->pinterest_url ?? '' }}" 
                                    placeholder="Pinterest URL"
                                >
                            </div>

                            <div class="social-input-group">
                                <div class="social-icon">💼</div>
                                <input 
                                    type="url" 
                                    name="linkedin_url" 
                                    value="{{ $store->linkedin_url ?? '' }}" 
                                    placeholder="LinkedIn URL"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-custom btn-success btn-block" style="margin-top: 24px;">
                            <span>🔗</span>
                            <span>Save Social Links</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- SEO & Meta Tab -->
        <div id="seo-tab" class="tab-content">
            <div class="settings-card" style="max-width: 900px; margin: 0 auto;">
                <div class="settings-card-header">
                    <div class="settings-card-icon">📊</div>
                    <h2 class="settings-card-title">SEO & Meta Data</h2>
                </div>

                <form method="POST" action="{{ route('vendor.business-settings.update-meta-data', [$store->id]) }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">
                            <span class="form-label-icon">📝</span>
                            <span>Meta Title</span>
                        </label>
                        <input 
                            type="text" 
                            name="meta_title" 
                            class="form-control" 
                            value="{{ $store->meta_title ?? '' }}"
                            placeholder="Enter meta title for SEO"
                        >
                        <small style="color: var(--text-secondary); margin-top: 8px; display: block;">
                            Recommended: 50-60 characters
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="form-label-icon">📄</span>
                            <span>Meta Description</span>
                        </label>
                        <textarea 
                            name="meta_description" 
                            class="form-control" 
                            placeholder="Enter meta description for SEO"
                            rows="4"
                        >{{ $store->meta_description ?? '' }}</textarea>
                        <small style="color: var(--text-secondary); margin-top: 8px; display: block;">
                            Recommended: 150-160 characters
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="image-upload-label">
                            Meta Image <span>(1200×630 PX recommended)</span>
                        </label>
                        <label for="metaImageUpload" class="image-preview" style="height: 250px;">
                            <img 
                                id="metaImagePreview" 
                                src="{{ $store->meta_image ? asset('storage/app/public/store/' . $store->meta_image) : '' }}" 
                                style="{{ $store->meta_image ? '' : 'display:none;' }}"
                            >
                            <div class="image-upload-placeholder" style="{{ $store->meta_image ? 'display:none;' : '' }}">
                                <div class="image-upload-placeholder-icon">🖼️</div>
                                <div class="image-upload-placeholder-text">Upload Meta Image</div>
                                <div class="image-upload-placeholder-hint">Used when sharing on social media</div>
                            </div>
                        </label>
                        <input 
                            type="file" 
                            id="metaImageUpload" 
                            name="meta_image" 
                            accept="image/*" 
                            style="display: none;"
                            onchange="previewImage(this, 'metaImagePreview')"
                        >
                    </div>

                    <div class="info-box">
                        <span class="info-box-icon">💡</span>
                        Meta information helps your website appear better in search results and when shared on social media
                    </div>

                    <button type="submit" class="btn-custom btn-primary btn-block" style="margin-top: 24px;">
                        <span>🚀</span>
                        <span>Update SEO Settings</span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection

@push('script_2')
@include('admin-views.partials.tel_input')

<script>
// Tab Switching
document.querySelectorAll('.settings-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Remove active class from all tabs and contents
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Show corresponding content
        const tabName = this.getAttribute('data-tab');
        document.getElementById(tabName + '-tab').classList.add('active');
    });
});

// Domain Preview
const customDomainInput = document.getElementById('customDomain');
if (customDomainInput) {
    customDomainInput.addEventListener('input', function() {
        const domain = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        this.value = domain;
        document.getElementById('domainPreview').textContent = domain + '.mychitti.net';
        document.getElementById('domainHint').textContent = domain;
    });
}

// Phone Number Management
function addPhoneNumber() {
    const container = document.getElementById('phoneContainer');
    const phoneItem = document.createElement('div');
    phoneItem.className = 'phone-item';
    phoneItem.innerHTML = `
        <input type="text" name="phone[]" class="form-control intl_input" placeholder="+91 1234567890">
        <button type="button" class="btn-remove" onclick="removePhoneNumber(this)">🗑️</button>
    `;
    container.appendChild(phoneItem);
    
    // Reinitialize international phone input if you're using it
    if (typeof initIntlPhone === 'function') {
        initIntlPhone(phoneItem.querySelector('.intl_input'));
    }
}

function removePhoneNumber(button) {
    const phoneItems = document.querySelectorAll('.phone-item');
    if (phoneItems.length > 1) {
        button.parentElement.remove();
    } else {
        alert('At least one phone number is required');
    }
}

// Image Preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const placeholder = preview.nextElementSibling;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (placeholder) placeholder.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Google Maps
var map, marker, geocoder;

function initMap() {
    const defaultLat = parseFloat({{ $storeConfig?->webpage_latitude ?? $store->latitude ?? 0 }});
    const defaultLng = parseFloat({{ $storeConfig?->webpage_longitude ?? $store->longitude ?? 0 }});
    
    loadMap(defaultLat, defaultLng);
}

function loadMap(latitude, longitude) {
    const location = {
        lat: latitude,
        lng: longitude,
    };

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 15,
        center: location,
        styles: [
            {
                featureType: "all",
                elementType: "geometry",
                stylers: [{ color: "#f8f9fd" }]
            },
            {
                featureType: "water",
                elementType: "geometry",
                stylers: [{ color: "#c9d7ef" }]
            }
        ]
    });

    marker = new google.maps.Marker({
        position: location,
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP
    });

    geocoder = new google.maps.Geocoder();
    updateLatLng(latitude, longitude);

    google.maps.event.addListener(marker, "dragend", function(event) {
        updateLatLng(event.latLng.lat(), event.latLng.lng());
    });

    const input = document.getElementById("searchInput");
    const autocomplete = new google.maps.places.Autocomplete(input);

    autocomplete.addListener("place_changed", function() {
        const place = autocomplete.getPlace();
        if (!place.geometry) {
            alert("No details found for this location!");
            return;
        }

        map.setCenter(place.geometry.location);
        marker.setPosition(place.geometry.location);
        marker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(() => marker.setAnimation(null), 750);

        updateLatLng(
            place.geometry.location.lat(),
            place.geometry.location.lng()
        );
    });
}

function updateLatLng(lat, lng) {
    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;
}

// Form Submission with Loading State
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        }
    });
});

// Success message auto-hide
if (document.querySelector('.success-message')) {
    setTimeout(() => {
        document.querySelector('.success-message').style.display = 'none';
    }, 5000);
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value ?? 'YOUR_API_KEY' }}&libraries=places&callback=initMap" async defer></script>

@endpush