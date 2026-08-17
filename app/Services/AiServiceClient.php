<?php

namespace App\Services;
 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
 
class AiServiceClient 
{
    private string $url;
    private string $key; 

    public function __construct()
    {
        $this->url = rtrim(config('services.ai_service.url', ''), '/');
        $this->key = config('services.ai_service.key', ''); 
    } 

    public function chat(int $userId , string $guard, string $message, ?array $fileContent = null, ?int $agentId = null, ?string $systemPrompt = null, ?array $modelConfig = null, string $type = 'text', ?string $currentPage = null, ?array $screenshotContent = null, ?string $pageStructure = null): array
    {
        // Auto-resolve agent_id from system_prompts if not explicitly provided
        if ($agentId === null && $guard !== 'agent_test') {
            $userType = match ($guard) {
                'admin'  => 'admin', 
                'vendor' => 'vendor', 
                default  => 'user',
            }; 
            
            $resolved = DB::table('system_prompts')
                ->where('user_type', $userType)
                ->where('status', 'active')
                ->orderByDesc('updated_at')
                ->first(['id', 'ai_provider', 'ai_model', 'max_tokens', 'temperature', 'top_p', 'api_key_override', 'inject_vendor_profile']);
                // prx($resolved, 'Resolved agent for guard: ' . $guard);
            if ($resolved) {
                $agentId = (int) $resolved->id;

                // Force provider/model from main app resolved agent so runtime
                // does not depend on ai_service DB state.
                if ($modelConfig === null) {
                    $modelConfig = [
                        'ai_provider'      => $resolved->ai_provider ?: 'anthropic',
                        'ai_model'         => $resolved->ai_model ?: null,
                        'max_tokens'       => $resolved->max_tokens ?: null,
                        'temperature'      => strlen((string) $resolved->temperature) ? (float) $resolved->temperature : null,
                        'top_p'            => strlen((string) $resolved->top_p) ? (float) $resolved->top_p : null,
                        'api_key_override' => $resolved->api_key_override ?: null,
                    ];
                }

                // For non-vendor guards, load prompt from file if available
                if ($guard !== 'vendor' && $systemPrompt === null) {
                    $promptFile = storage_path("app/prompts/{$userType}.txt");
                    if (is_file($promptFile)) {
                        $systemPrompt = file_get_contents($promptFile);
                    }
                }

                // Inject vendor profile into system prompt so Sam knows who is logged in.
                //
                // Honours the agent's "Inject Vendor Profile" toggle, which until now was saved and
                // never consulted. Absent or null counts as ON: every existing agent row has it set,
                // and a missing value must not be read as "turn Sam's context off" — he would stop
                // knowing who he is talking to and start telling logged-in vendors to log in.
                $injectProfile = !isset($resolved->inject_vendor_profile) || (bool) $resolved->inject_vendor_profile;

                if ($guard === 'vendor' && $systemPrompt === null && $injectProfile) {
                    $vendor = DB::table('vendors')->where('id', $userId)
                        ->first(['f_name', 'l_name', 'email', 'phone']);
                    $store = DB::table('stores')->where('vendor_id', $userId)
                        ->first(['name', 'phone', 'email', 'gst', 'address']);

                    if ($vendor && $store) {
                        $navBreadcrumb = $currentPage ? $this->resolveVendorNavigation($currentPage) : '';
                        if ($navBreadcrumb) {
                            // Determine current top-level section so Sam skips steps already taken
                            $breadcrumbParts = array_map('trim', explode('→', $navBreadcrumb));
                            // e.g. ['Sidebar', 'Inventory Management', 'Items'] → current section = 'Inventory Management'
                            $currentSection = $breadcrumbParts[1] ?? '';
                            $relativeNavNote = $currentSection
                                ? "Since the vendor is already inside \"{$currentSection}\", skip \"Sidebar → {$currentSection}\" from every navigation instruction — only tell them what to click NEXT from where they are."
                                : "Skip any steps the vendor has already taken.";

                            $pageContext = "VENDOR'S CURRENT LOCATION: {$navBreadcrumb}\n"
                                . "NAVIGATION RULE: {$relativeNavNote} "
                                . "If the destination is the SAME page they are on, say \"You are already here — just [action].\"\n"
                                . "LINK RULE: Always format navigation as a markdown link, e.g. [Add Employee](/staff/add-new). Never write bare paths.\n\n";
                        } elseif ($currentPage) {
                            $pageContext = "CURRENT PAGE: {$currentPage}\n"
                                . "NAVIGATION RULE: Give navigation relative to their current page. Skip steps already taken. "
                                . "Always format navigation as a markdown link e.g. [Page Name](/path).\n\n";
                        } else {
                            $pageContext = '';
                        }

                        $contextPrefix = "IMPORTANT CONTEXT — The vendor is ALREADY LOGGED IN and is chatting from inside the MC Vendor Hub dashboard. "
                            . "Never tell them to log in, visit a login page, or navigate to their account — they are already there.\n\n"
                            . "PAGE AWARENESS: You receive the vendor's exact current browser page structure with every message — a precise list of every heading, button, dropdown (with all options), input field, table columns, and clickable row links actually present on the page. "
                            . "When the vendor asks where something is or how to do something on the current page, use this structure as your definitive source of truth. "
                            . "Give exact, confident answers: 'Click the X button in the top-left', 'Use the dropdown with options A/B/C'. "
                            . "NEVER say 'possibly', 'might', 'if you see', or 'you should find' — you know exactly what is there. "
                            . "If an element is not in the structure, it does not exist on this page — tell the vendor clearly. "
                            . "NEVER mention 'screenshot', 'image', 'page view', or 'structure data' in your responses. Answer naturally.\n\n"
                            . $pageContext
                            . "CURRENT VENDOR (logged in):\n"
                            . "- Name: {$vendor->f_name} {$vendor->l_name}\n"
                            . "- Email: {$vendor->email}\n"
                            . "- Phone: {$vendor->phone}\n"
                            . "- Store name: {$store->name}\n"
                            . "- Store phone: {$store->phone}\n"
                            . "- Store email: " . ($store->email ?: 'N/A') . "\n"
                            . "- GST: " . ($store->gst ?: 'Not registered') . "\n"
                            . "- Address: " . ($store->address ?: 'N/A') . "\n\n";

                        $promptFile = storage_path("app/prompts/{$userType}.txt");
                        $promptText = is_file($promptFile) ? file_get_contents($promptFile) : (DB::table('system_prompts')->where('id', $agentId)->value('prompt') ?? '');
                        $systemPrompt = $contextPrefix . $promptText . "\n\n" . $this->vendorCapabilitiesPrompt();
                    }
                }
            }
        }
        $payload = [
            'user_id' => $userId,
            'guard'   => $guard,
            'message' => $message,
            'type'    => $type,
        ];

        if ($agentId !== null) {
            $payload['agent_id'] = $agentId;
        }

        if ($systemPrompt !== null) {
            $payload['system_prompt'] = $systemPrompt;
        }

        if ($modelConfig !== null) {
            $payload['model_config'] = $modelConfig;
        }

        if ($fileContent) {
            $payload['attachment'] = $fileContent;
        }

        if ($screenshotContent) {
            $payload['page_screenshot'] = $screenshotContent;
        }

        if ($pageStructure) {
            $payload['page_structure'] = $pageStructure;
        }
 
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(120)
                ->post("{$this->url}/api/ai/chat", $payload);
            if (!$response->successful()) { 
                $body = $response->json();
                // Surface Laravel validation messages cleanly (422)
                if ($response->status() === 422 && !empty($body['errors'])) {
                    $first = array_values(array_merge(...array_values($body['errors'])))[0] ?? ($body['message'] ?? 'Validation error');
                    return ['success' => false, 'message' => $first];
                }
  
                $message = $body['message'] ?? null; 
                $debugError = is_string($body['debug_error'] ?? null) ? $body['debug_error'] : '';

                // Prefer clear upstream message instead of generic "500".
                if (!$message) {
                    $message = 'AI service error: ' . $response->status();
                }

                // Anthropic quota/billing issue: provide a user-facing actionable message.
                if ($debugError !== '' && stripos($debugError, 'credit balance is too low') !== false) {
                    $message = 'AI service is temporarily unavailable due to low API credits. Please contact support.';
                }

                return ['success' => false, 'message' => $message, 'detail' => $response->body()];
            }

            return $response->json() ?? ['success' => false, 'message' => 'No response from AI service.'];
        } catch (\Exception $e) {
            Log::error('AI service chat error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'AI service unavailable.'];
        } 
    }

    private function vendorCapabilitiesPrompt(): string
    {
        return <<<'PROMPT'
## What You Can Do (vendor_api_call tool)

You have access to the vendor_api_call tool. Use it to fetch data or take actions on behalf of the vendor — no need to ask them to do it manually. Always confirm before write/delete actions.

### Staff (module: "staff")
- list — show all staff members
- get — find a staff member (data: {id} or {name})
- add — add new staff (data: {f_name, l_name, email, phone, salary, designation, department_id, role_id})
- edit — update staff details (data: {id, ...fields to change})
- delete — deactivate staff (data: {id})

### Inventory (module: "inventory")
- list — show all inventory items
- get — find an item (data: {id}, {name}, or {sku})
- add — add item (data: {item_name, sku, stock, selling_price, landing_price, unit, category_id})
- edit — update item (data: {id, ...fields})
- low_stock — items below threshold (data: {threshold} — default 10)

### Invoices / Billing (module: "invoice")
- list — recent invoices
- get — find invoice (data: {id} or {number})
- unpaid — all unpaid/pending invoices
- add — create a new invoice (data: {customer_name, items: [{name, price, qty, tax}], payment_method, payment_status})
  - Single item shorthand: {customer_name, item_name, price, qty, unit, tax}
  - **INVOICE CREATION RULE**: When the vendor asks to create/make/generate a bill or invoice, scan the ENTIRE conversation (including earlier messages) for customer name, item/service name, and price/amount. If you have all three, call vendor_api_call IMMEDIATELY — do NOT ask for confirmation, do NOT ask again for information already given. Only ask if something is genuinely missing (e.g. no price mentioned at all).
- mark_paid — mark an invoice as paid (data: {id} or {number}, payment_method optional)

### Customer Leads / Enquiries (module: "leads")
- list — recent enquiries sent to vendor's store (data: {status} optional)
- count — enquiry counts by status
- get — get enquiry detail (data: {id})

### CRM / Own Leads (module: "crm")
- list — vendor's own CRM leads (data: {status} optional)
- get — find a CRM lead (data: {id} or {name})
- add — add a new lead (data: {client_name, phone, email, service, requirements, status, follow_up_date})
- update_status — update lead status (data: {id, status, remarks})

### Attendance (module: "attendance")
- list — who was present on a date (data: {date} — default today)
- absent — who was absent on a date (data: {date} — default today)

### Leave (module: "leave")
- list — all leave records
- pending — pending approval requests
- approve — approve a leave request (data: {id})
- reject — reject a leave request (data: {id})

### Salary (module: "salary")
- list — salary records for a month (data: {month} e.g. "2026-05")
- mark_paid — mark a salary transaction as paid (data: {id, payment_method optional})

### Projects (module: "project")
- list — all projects (data: {status} optional: "In Progress", "Completed", etc.)
- get — project details (data: {id} or {title})
- add — create a project (data: {title, description, start_date, end_date, cost, priority, status})
- update — update a project (data: {id, ...fields to change})

### Tasks (module: "task")
- list — all tasks (data: {status} optional)
- pending — tasks not yet completed
- add — create a task (data: {title, description, employee_id, status, task_type, project_id})
- update — update a task (data: {id, status, progress, ...fields})

### Calendar / Appointments (module: "calendar")
- list — appointments for a date (data: {date} — default today)
- upcoming — next upcoming scheduled appointments

### Job Cards (module: "job_card")
- list — recent job cards
- get — job card details (data: {id} or {number})

### Store Info (module: "store")
- get — store details (name, phone, address, GST)
- staff_count — total active staff count

### Account / Finance (module: "account")
- summary — total billed, paid, unpaid amounts across all invoices
- recent — last 10 invoices

### Banking (module: "banking")
- list — all bank accounts with current balances
- balance — total balance across all bank accounts
- transactions — recent transactions (data: {bank_id} optional to filter by account)

### Assets (module: "assets")
- list — all store assets with item name, cost, current value
- get — asset details (data: {id})
- summary — total asset count, cost, current value

### Shifts (module: "shifts")
- list — all defined shifts with timings and working days
- get — shift details (data: {id} or {name})
- add — create a shift (data: {name, start_time, end_time, grace_minutes, working_days})

### Quotations (module: "quotation")
- list — recent quotations (data: {status} optional: "New", "Accepted", "Declined")
- get — quotation details (data: {id})
- summary — count of quotations by status

### Clients (module: "clients")
- list — all store clients/customers
- get — find a client (data: {id}, {name}, or {phone})
- add — add a new client (data: {name, phone, email, address, gst})
- count — total client count

### Orders (module: "orders")
- list — recent orders (data: {status} optional)
- summary — total orders, revenue, active orders, today's count
- get — order details (data: {id})
- pending — orders not yet delivered

### Service Items (module: "service")
- list — all active service/shop items
- get — item details (data: {id} or {name})
- top — top 10 most ordered items

### POS (module: "pos")
- list — recent POS orders (data: {date} optional)
- summary — today's POS orders count and revenue, monthly revenue
- today — today's POS orders

### Documents (module: "documents")
- list — count of gatepasses and receivable receipts
- gatepass — recent inventory gate passes
- receipts — recent receivable receipts with client name and amount

### Notifications (module: "notification")
- list — recent notifications sent to customers
- send — create a notification (data: {title, description})
- count — total sent/draft notification count

### Shop Items (module: "items")
- list — all shop items sorted by popularity
- get — item details (data: {id} or {name})
- inactive — items that are currently disabled
- summary — total items, active count, most ordered item

### Campaigns (module: "campaign")
- list — all item campaigns
- active — currently running campaigns
- get — campaign details (data: {id} or {title})

### Coupons (module: "coupon")
- list — all coupons with discount and expiry
- get — coupon details (data: {id} or {code})
- add — create a coupon (data: {code, title, discount, discount_type, min_purchase, expire_date, limit})
- active — currently active and non-expired coupons

## Navigation Quick Links

When giving navigation instructions, ALWAYS use markdown links `[Label](/path)`. Never write bare paths or long "Sidebar → X → Y" chains — just the next click from where they are.

| Section | Page | Link |
|---------|------|------|
| HR | Staff list | [Staff](/staff) |
| HR | Add employee | [Add Employee](/staff/add-new) |
| HR | Add staff (free) | [Add Staff (Free)](/basic-staff) |
| HR | Staff departments | [Staff Departments](/staff-department) |
| HR | Attendance list | [Attendance](/attendance/list) |
| HR | Leave | [Leave](/leave) |
| HR | Salary | [Salary](/salary) |
| Billing | Invoice list | [Invoice List](/billing/list) |
| Billing | Create invoice | [Create Invoice](/billing/create-invoice) |
| Billing | Manual bill | [Manual Bill](/billing/manual-bill) |
| Inventory | Items | [Inventory Items](/inventory/item) |
| Inventory | Stock | [Stock](/inventory/stock) |
| Inventory | Add entry | [Inventory Entry](/inventory/entry) |
| Inventory | Purchase | [Purchase](/inventory/purchase) |
| Inventory | Sales | [Sales](/inventory/sale) |
| Inventory | Category | [Category](/inventory/category) |
| Inventory | Reports | [Inventory Reports](/inventory/report) |
| Inventory | Gatepass | [Gatepass](/inventory/gatepass) |
| Account | Day Book | [Day Book](/account/day-book) |
| Account | Master Ledger | [Master Ledger](/account/master-ledger) |
| Account | Journal Entry | [Journal Entry](/account/journal-entry) |
| Account | Statement | [Trial Balance](/account/statement) |
| Account | Banking | [Banking](/account/banking) |
| Account | Petty Cash | [Petty Cash](/account/petty-cashbook) |
| Account | Monthly Finance | [Monthly Finance](/account/monthly-finance) |
| Account | Reports | [Account Reports](/account/report) |
| Account | Taxation | [Taxation](/account/taxation) |
| Account | Settings | [Account Settings](/account/setting) |
| Projects | Project list | [Projects](/project) |
| Projects | Add project | [Add Project](/project/add) |
| Tasks | Task list | [Tasks](/task) |
| Tasks | Add task | [Add Task](/task/add) |
| CRM / Leads | Customer leads | [Leads](/leads) |
| CRM / Leads | Add lead | [Add Lead](/leads/add) |
| CRM / Leads | Clients | [Clients](/crm/client) |
| CRM / Leads | Add client | [Add Client](/crm/client/add) |
| Documents | Job Card | [Job Card](/documents/job-card) |
| Documents | Gatepass | [Documents Gatepass](/documents/gatepass) |
| Documents | Receivable Receipt | [Receivable Receipt](/documents/receivable-receipt) |
| Calendar | Smart Calendar | [Smart Calendar](/smart-calendar) |
| Analytics | Performance Analytics | [Performance Analytics](/performance-analytics) |
| Notifications | Notifications | [Notifications](/notification) |
| Settings | General settings | [Settings](/settings) |
| Settings | **Menu Preference** (show/hide sidebar items) | [Menu Preference](/menu-preference) |
| Settings | Add-ons / Modules | [Add-ons](/addon) |
| Profile | Business webpage | [Business Page](/shop) |
| Profile | Shop settings | [Shop Settings](/shop-settings) |
| Enquiries | Customer enquiries | [Enquiries](/service-request) |

CRITICAL RULES:
1. When the vendor asks for data ("show my staff", "list invoices", "what tasks are pending"), call vendor_api_call IMMEDIATELY — do not give navigation instructions.
2. CONFIRMATION FLOW: When you describe a write action and ask the vendor to confirm, and the vendor replies with YES / OK / sure / go ahead / proceed / confirm — you MUST immediately call vendor_api_call to execute EXACTLY the action you described in your previous message. Do NOT call a different module. Do NOT show unrelated data.
3. NEVER show leads/enquiries data unless the vendor explicitly asked for leads or enquiries. Do not call module=leads as a fallback.
4. Only give navigation instructions if the vendor explicitly asks HOW to do something in the dashboard UI. Always use markdown links — never bare text paths.
PROMPT;
    }

    private function resolveVendorNavigation(string $currentPage): string
    {
        // Extract just the path portion (strip title in parentheses)
        $path = trim(preg_replace('/\s*\(.*\)$/', '', $currentPage));
        $path = '/' . ltrim($path, '/');

        $map = [
            // Dashboard
            '#^/vendor/dashboard#'                                      => 'Dashboard',
            '#^/dashboard#'                                             => 'Dashboard',

            // HR Management
            '#^/staff/add-new#'                                         => 'Sidebar → HR Management → Staff → Add New Employee',
            '#^/basic-staff#'                                           => 'Sidebar → HR Management → Staff → Add Staff (Free)',
            '#^/staff/add#'                                             => 'Sidebar → HR Management → Staff → Add Staff',
            '#^/staff/edit/#'                                           => 'Sidebar → HR Management → Staff → Edit Staff',
            '#^/staff/settings#'                                        => 'Sidebar → HR Management → Staff → Settings',
            '#^/staff/team/edit/#'                                      => 'Sidebar → HR Management → Staff → Teams → Edit Team',
            '#^/staff/team#'                                            => 'Sidebar → HR Management → Staff → Teams',
            '#^/staff#'                                                 => 'Sidebar → HR Management → Staff',
            '#^/staff-department#'                                      => 'Sidebar → HR Management → Staff Department',
            '#^/attendance/list#'                                       => 'Sidebar → HR Management → Attendance → Attendance List',
            '#^/attendance/manage/#'                                    => 'Sidebar → HR Management → Attendance → Manage Attendance',
            '#^/attendance#'                                            => 'Sidebar → HR Management → Attendance',
            '#^/leave/add#'                                             => 'Sidebar → HR Management → Leave → Add Leave',
            '#^/leave#'                                                 => 'Sidebar → HR Management → Leave',
            '#^/salary/advance-request#'                                => 'Sidebar → HR Management → Salary → Advance Request',
            '#^/salary#'                                                => 'Sidebar → HR Management → Salary',

            // Billing & POS
            '#^/billing/manual-bill#'                                   => 'Sidebar → Billing & POS → Manual Bill',
            '#^/billing/create-invoice#'                                => 'Sidebar → Billing & POS → Create Invoice',
            '#^/billing/edit/#'                                         => 'Sidebar → Billing & POS → Edit Invoice',
            '#^/billing/list#'                                          => 'Sidebar → Billing & POS → Invoice List',
            '#^/billing#'                                               => 'Sidebar → Billing & POS',

            // Inventory Management
            '#^/inventory/dashboard#'                                   => 'Sidebar → Inventory Management → Dashboard',
            '#^/inventory/settings#'                                    => 'Sidebar → Inventory Management → Settings',
            '#^/inventory/edit-item/#'                                  => 'Sidebar → Inventory Management → Items → Edit Item',
            '#^/inventory/item#'                                        => 'Sidebar → Inventory Management → Items',
            '#^/inventory/entry#'                                       => 'Sidebar → Inventory Management → Entry',
            '#^/inventory/gatepass/sale#'                               => 'Sidebar → Inventory Management → Gatepass → Sales Gatepass',
            '#^/inventory/gatepass/purchase#'                           => 'Sidebar → Inventory Management → Gatepass → Purchase Gatepass',
            '#^/inventory/gatepass#'                                    => 'Sidebar → Inventory Management → Gatepass',
            '#^/inventory/purchase#'                                    => 'Sidebar → Inventory Management → Purchase',
            '#^/inventory/stock#'                                       => 'Sidebar → Inventory Management → Stock',
            '#^/inventory/sale#'                                        => 'Sidebar → Inventory Management → Sales',
            '#^/inventory/category#'                                    => 'Sidebar → Inventory Management → Category',
            '#^/inventory/report#'                                      => 'Sidebar → Inventory Management → Reports',
            '#^/inventory/storage-unit#'                                => 'Sidebar → Inventory Management → Storage Unit',
            '#^/inventory#'                                             => 'Sidebar → Inventory Management',

            // Account Management
            '#^/account/master-ledger#'                                 => 'Sidebar → Account Management → Master Ledger',
            '#^/account/journal-entry#'                                 => 'Sidebar → Account Management → Journal Entry',
            '#^/account/petty-cashbook#'                                => 'Sidebar → Account Management → Petty Cash Book',
            '#^/account/day-book#'                                      => 'Sidebar → Account Management → Day Book',
            '#^/account/monthly-finance#'                               => 'Sidebar → Account Management → Monthly Finance',
            '#^/account/statement#'                                     => 'Sidebar → Account Management → Statement',
            '#^/account/banking/bank-account#'                          => 'Sidebar → Account Management → Banking → Bank Accounts',
            '#^/account/banking/cash-book#'                             => 'Sidebar → Account Management → Banking → Cash Book',
            '#^/account/banking/bank-reconciliation#'                   => 'Sidebar → Account Management → Banking → Bank Reconciliation',
            '#^/account/banking#'                                       => 'Sidebar → Account Management → Banking',
            '#^/account/taxation#'                                      => 'Sidebar → Account Management → Taxation',
            '#^/account/report#'                                        => 'Sidebar → Account Management → Reports',
            '#^/account/setting/chart-of-account#'                      => 'Sidebar → Account Management → Settings → Chart of Accounts',
            '#^/account/setting#'                                       => 'Sidebar → Account Management → Settings',
            '#^/account/maintenance#'                                   => 'Sidebar → Account Management → Maintenance',
            '#^/account/request-form/master-ledger#'                    => 'Sidebar → Account Management → Request Form → Master Ledger',
            '#^/account/request-form/journal-entry#'                    => 'Sidebar → Account Management → Request Form → Journal Entry',
            '#^/account#'                                               => 'Sidebar → Account Management',

            // Project Management
            '#^/project/\d+/milestone#'                                 => 'Sidebar → Project Management → Project Details → Milestones',
            '#^/project/detail/#'                                       => 'Sidebar → Project Management → Project Details',
            '#^/project/add#'                                           => 'Sidebar → Project Management → Add Project',
            '#^/project/edit/#'                                         => 'Sidebar → Project Management → Edit Project',
            '#^/project#'                                               => 'Sidebar → Project Management',

            // Task Management
            '#^/task/add#'                                              => 'Sidebar → Task Management → Add Task',
            '#^/task/edit/#'                                            => 'Sidebar → Task Management → Edit Task',
            '#^/task/detail/#'                                          => 'Sidebar → Task Management → Task Detail',
            '#^/task/setting#'                                          => 'Sidebar → Task Management → Settings',
            '#^/task#'                                                  => 'Sidebar → Task Management',

            // CRM / Client Management
            '#^/crm/client/add#'                                        => 'Sidebar → Client Management (CRM) → Add Client',
            '#^/crm/client/edit/#'                                      => 'Sidebar → Client Management (CRM) → Edit Client',
            '#^/crm/client#'                                            => 'Sidebar → Client Management (CRM) → Clients',
            '#^/crm#'                                                   => 'Sidebar → Client Management (CRM)',

            // Lead Management
            '#^/leads/add#'                                             => 'Sidebar → Lead Management → Add Lead',
            '#^/leads/edit/#'                                           => 'Sidebar → Lead Management → Edit Lead',
            '#^/leads/report#'                                          => 'Sidebar → Lead Management → Reports',
            '#^/leads#'                                                 => 'Sidebar → Lead Management',

            // Documents
            '#^/documents/gatepass#'                                    => 'Sidebar → Documents → Gatepass',
            '#^/documents/receivable-receipt#'                          => 'Sidebar → Documents → Receivable Receipt',
            '#^/documents/job-card#'                                    => 'Sidebar → Documents → Job Card',
            '#^/documents/service-report#'                              => 'Sidebar → Documents → Service Report',
            '#^/documents#'                                             => 'Sidebar → Documents',

            // Smart Calendar
            '#^/smart-calendar#'                                        => 'Sidebar → Smart Calendar',

            // Notifications
            '#^/notification#'                                          => 'Sidebar → Notifications',

            // Business Webpage / Profile
            '#^/shop-settings#'                                         => 'Sidebar → Business Webpage / Profile Settings',
            '#^/shop#'                                                  => 'Sidebar → Business Webpage',

            // Settings
            '#^/settings#'                                              => 'Sidebar → Settings',

            // Menu Preference
            '#^/menu-preference#'                                       => 'Sidebar → Menu Preference',

            // Add-ons
            '#^/addon#'                                                 => 'Sidebar → Add-ons / Modules',

            // Performance Analytics
            '#^/performance-analytics#'                                 => 'Sidebar → Performance Analytics',
            '#^/store-panel/performance-analytics#'                     => 'Sidebar → Performance Analytics',

            // Push Notifications (separate from the Notifications page already mapped above)
            '#^/push-notification#'                                     => 'Sidebar → Push Notifications',

            // Enquiries / Leads from customers
            '#^/service-request#'                                       => 'Sidebar → Enquiries (Customer Leads)',

            // Customer list
            '#^/customer/add#'                                          => 'Sidebar → Customers → Add Customer',
            '#^/customer#'                                              => 'Sidebar → Customers',
        ];

        foreach ($map as $pattern => $label) {
            if (preg_match($pattern, $path)) {
                return $label;
            }
        }

        return ''; // unknown page — don't inject false breadcrumb
    }

    public function history(int $userId, string $guard): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(30)
                ->get("{$this->url}/api/ai/history", ['user_id' => $userId, 'guard' => $guard]);

            return $response->json() ?? ['success' => false, 'messages' => []];
        } catch (\Exception $e) {
            Log::error('AI service history error', ['error' => $e->getMessage()]);
            return ['success' => false, 'messages' => []];
        }
    }

    public function clearMemory(int $userId, string $guard): array
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->key])
                ->timeout(30)
                ->post("{$this->url}/api/ai/clear", ['user_id' => $userId, 'guard' => $guard]);

            return $response->json() ?? ['success' => false, 'message' => 'Memory cleared.'];
        } catch (\Exception $e) {
            Log::error('AI service clear error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'AI service unavailable.'];
        }
    }
}
