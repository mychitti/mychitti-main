# 🌐 Platform Features & Modules Documentation

Welcome to the comprehensive features and capability documentation for **MyChitti**. MyChitti is a highly-scalable, production-grade **Multi-Vendor SaaS Marketplace** designed to run diverse business operations under a single unified dashboard, structured on a powerful **Zone + Module** architecture.

---

## 1. System Overview & Architecture

MyChitti coordinates transactions among four distinct user roles and portals:

*   **Admin Portal:** Central command center for system settings, global parameters, zone configuration, pricing configuration, subscription plan configuration, service configuration, finance reports, payment configurations, and AI agent configuration.
*   **Vendor Portal:** Self-service console for stores, hospitals, pos, crm, laundries to manage items, pricing, inventory, HR, shift schedules, and incoming leads.
*   **Vendor Employee Portal:** Sub-accounts with role-based permissions (Granular Permissions) enabling staff to execute operational tasks.
*   **Customer Web/App:** Multi-channel portal supporting standard purchasing, geospatial address management, service requests tracking. 
---

## 2. Modular Business Verticals

MyChitti includes customized modules (`app/Modules/`) tailored for specific industry niches:

### 🏥 Hospital Management System (HMIS)
*   **OPD & IPD Management:** Out-Patient and In-Patient workflows including patient intake logs and status tracking.
*   **Doctor & Nurse Directories:** Rosters, duty schedules, holiday overrides, and doctor profiles.
*   **Appointment Scheduler:** Booking systems with status checks (confirmed, completed, rescheduled).
*   **Ward & Bed Allocation:** Dynamic tracking of hospital wards and bed availability.
*   **Medical Billing & Prescriptions:** Complete module generating prescription sheets, notes, and clinic bills.

### 🧺 Laundry Service
*   **Service Configurations:** Set services by item type (wash, dry clean, iron, fold).
*   **Workflow Pipeline:** Pick-up scheduling, wash cycles, quality control, packaging, and dispatch tracking.

### 🖥️ Point of Sale (POS)
*   **In-Store Checkout:** Rapid POS checkout terminal for walking customers, printing paper tokens, and instant queue processing.

### 🛍️ Common Module (Standard Retail & Marketplace)
*   **Standard Storefronts:** Used by general business types that do not require specialized medical (HMIS) or laundry configurations.
*   **Flexible Service Booking:** Supports generic service requests, listings, and customer bookings for standard service providers.

---

## 3. Core Enterprise Features

### 🗺️ Zone & Geospatial Management
*   **Polygon Boundaries:** Admin draws serviceable area boundaries using map polygons. All calculations are zone-aware.

### 💰 Finance & Profitability Hub
*   **Net Profit Line Overlay:** A combo chart contrasting total revenue bars with net profit margins.
*   **Dynamic Insights:** Business summary blocks summarizing income channels, receivables, and operational health.
*   **Tax Auditing:** Breakdowns of SGST, CGST, and IGST collected across all transactions.
*   **Outstanding Receivables:** Tracking unpaid platform invoices generated for store subscription fees.

### 📈 Marketing & Growth
*   **SaaS Subscriptions:** Vendor subscription plans with duration limits and modular feature locks.
*   **Referral & Loyalty Systems:** Custom wallet bonuses, loyalty-to-wallet point conversions, and referral commission parameters.
*   **Campaign Systems:** Store-specific discount campaigns, flash sales, and customizable coupon codes.

### 👥 HR & Employee Management
*   **Time & Attendance:** Biometric/manual attendance records, time cards, and shift schedules.
*   **Salary Logs:** Basic salary, task-based commissions, and advance request tracking.
*   **Leave Trackers:** Document submissions, reviews, approvals, and carry-overs.

### 🪵 Inventory & Storage
*   **Storage Units:** Inventory control for ingredients, supplies, or raw products.
*   **Gatepasses:** Generates supply orders, gatepasses, and return slips.

---

## 4. AI & Intelligent Systems

MyChitti includes an **AI Droplet API Client** linking the Laravel web application to modern LLMs (Anthropic Claude and OpenAI):

*   **Rolling Memory Summarization:** Admin chat screens include auto-summarization logs (`Summary` model) to maintain context without overloading token budgets.
*   **RAG Context Injection:** Feeds localized business manuals or documents into prompts to deliver customer and vendor support.

---

## 5. Payments & Integrations

*   **Online Gateways:** Razorpay is the primary active payment gateway in use (with architectural support for Stripe, PayPal, Paystack, Bkash, etc.).
*   **SaaS Tenants:** Supports custom vendor domain mappings (`DomainPurchase`) and template purchase checks (`TemplatePurchase`).
