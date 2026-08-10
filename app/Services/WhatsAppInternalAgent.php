<?php

namespace App\Services;

use App\Models\AcceptedServiceRequest;
use Illuminate\Support\Facades\DB;

/**
 * The assistant the business talks to, as opposed to the one its customers talk to.
 *
 * When a message arrives on a store's WhatsApp number from the owner's or a staff member's own
 * phone, answering it as if a stranger had written is wrong twice over: the customer prompt
 * forbids everything they are entitled to ask, and their question ("sales today?") is not in any
 * knowledge document. So the sender is identified first and, when they belong to the business,
 * this section replaces the customer one.
 *
 * Identity is the sender's phone number matched against the owner's and the staff roster. That is
 * the only signal WhatsApp gives us, and it is what gates the figures below — see promptSection.
 *
 * Tokens are unaffected: the reply is produced by SendAutoReply like any other, so it is metered
 * to the store's own wallet from the vendor's plan pool.
 */
class WhatsAppInternalAgent
{
    const ROLE_OWNER = 'owner';
    const ROLE_STAFF = 'staff';

    /** Trailing digits compared, so +91 98… and 098… are one person. */
    const KEY_DIGITS = 10;

    /**
     * Who sent this, if the business knows them. Owner is checked first: an owner who is also on
     * the staff roster is still the owner, and it is the owner who may see the figures.
     */
    public static function identify(int $storeId, string $phoneKey): ?array
    {
        $phoneKey = static::key($phoneKey);
        if (strlen($phoneKey) < static::KEY_DIGITS) {
            return null;
        }

        $vendorId = DB::table('stores')->where('id', $storeId)->value('vendor_id');
        if ($vendorId) {
            $owner = static::matchPhone(DB::table('vendors')->where('id', $vendorId), $phoneKey)
                ->first(['id', 'f_name', 'l_name']);

            if ($owner) {
                return [
                    'role' => self::ROLE_OWNER,
                    'id'   => $owner->id,
                    'name' => trim(($owner->f_name ?? '') . ' ' . ($owner->l_name ?? '')) ?: 'Owner',
                ];
            }
        }

        $staff = static::matchPhone(
            DB::table('vendor_employees')->where('store_id', $storeId),
            $phoneKey
        )->first(['id', 'f_name', 'l_name', 'employee_role_id']);

        if ($staff) {
            return [
                'role'    => self::ROLE_STAFF,
                'id'      => $staff->id,
                'name'    => trim(($staff->f_name ?? '') . ' ' . ($staff->l_name ?? '')) ?: 'Team member',
                'role_id' => $staff->employee_role_id ?? null,
            ];
        }

        return null;
    }

    /**
     * The internal context block, replacing the customer one.
     *
     * Staff get the business's own details and their own record. The figures — leads, sales and
     * customers — are added for the owner only, so a staff phone cannot read the takings.
     */
    public static function promptSection(int $storeId, array $who): string
    {
        $store = DB::table('stores')->where('id', $storeId)
            ->first(['name', 'phone', 'email', 'address']);

        $isOwner = ($who['role'] ?? null) === self::ROLE_OWNER;

        $section = "\n\nWHO YOU ARE TALKING TO: {$who['name']}, "
            . ($isOwner ? 'the owner of this business' : 'a staff member of this business')
            . ". They are messaging from their own phone, which the business has on record.\n\n"
            . "THE BUSINESS:\n"
            . '- Name: ' . ($store->name ?? '—') . "\n"
            . '- Phone: ' . ($store->phone ?? '—') . "\n"
            . '- Email: ' . ($store->email ?? '—') . "\n"
            . '- Address: ' . ($store->address ?? '—') . "\n";

        $section .= "\n" . static::staffSection($storeId, $who, $isOwner);

        if ($isOwner) {
            $section .= "\n" . static::statsSection($storeId);
        }

        $section .= "\nRULES FOR THIS CONVERSATION (they replace the customer rules above):\n"
            . "- You are talking to the business itself, not to a customer. Answer their questions about "
            . "the business directly from the details above.\n"
            . "- Quote the figures exactly as given. Never estimate, project or invent a number, and never "
            . "compute a figure that is not listed — say it is not available and that they can see it in "
            . "the vendor panel.\n"
            . ($isOwner
                ? "- They are the owner, so the leads, sales and customer figures above may be shared with them.\n"
                : "- They are staff, NOT the owner. You do NOT have this business's leads, sales or customer "
                    . "figures. If they ask for any of it, say only the owner can see those figures. Do not "
                    . "guess at them and do not read them out of anything earlier in this conversation.\n")
            . "- Never reveal another customer's personal records here.\n"
            . "- Keep replies short and WhatsApp-friendly: plain text, no markdown.\n";

        return $section;
    }

    /** The roster. Owners see everyone; a staff member sees only their own record. */
    protected static function staffSection(int $storeId, array $who, bool $isOwner): string
    {
        if (!$isOwner) {
            return "YOUR RECORD:\n- " . $who['name'] . " (staff of this business)\n";
        }

        $staff = DB::table('vendor_employees as e')
            ->leftJoin('employee_roles as r', 'r.id', '=', 'e.employee_role_id')
            ->where('e.store_id', $storeId)
            ->orderBy('e.f_name')
            ->limit(50)
            ->get(['e.f_name', 'e.l_name', 'e.phone', 'r.name as role_name']);

        if ($staff->isEmpty()) {
            return "STAFF: none on record.\n";
        }

        $lines = $staff->map(function ($s) {
            $name = trim(($s->f_name ?? '') . ' ' . ($s->l_name ?? '')) ?: 'Unnamed';
            $role = $s->role_name ? ' — ' . $s->role_name : '';
            $phone = $s->phone ? ' (' . $s->phone . ')' : '';

            return '- ' . $name . $role . $phone;
        })->implode("\n");

        return "STAFF (" . $staff->count() . "):\n" . $lines . "\n";
    }

    /**
     * The owner-only figures.
     *
     * Each is read from the same table the vendor panel reads, so a number quoted over WhatsApp
     * and the same number on screen cannot disagree:
     *   leads     — accepted service requests (vendor_id holds the STORE id, as elsewhere)
     *   sales     — finalised POS / POS Retail bills in manual_invoices (store id again)
     *   customers — the store's own customer book
     */
    protected static function statsSection(int $storeId): string
    {
        $monthStart = now()->startOfMonth();
        $today = now()->toDateString();

        $leadsTotal = AcceptedServiceRequest::where('vendor_id', $storeId)->count();
        $leadsMonth = AcceptedServiceRequest::where('vendor_id', $storeId)
            ->where('created_at', '>=', $monthStart)->count();
        $leadsOpen = AcceptedServiceRequest::where('vendor_id', $storeId)
            ->where('current_status', '!=', 'Completed')->count();

        $sales = DB::table('manual_invoices')->where('vendor_id', $storeId)
            ->where('type', 'manual')->where('pos_status', 'final');
        $salesToday = (clone $sales)->whereDate('created_at', $today)
            ->selectRaw('COUNT(*) bills, COALESCE(SUM(total_amount),0) amount')->first();
        $salesMonth = (clone $sales)->where('created_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) bills, COALESCE(SUM(total_amount),0) amount')->first();

        $customers = DB::table('store_customers')->where('store_id', $storeId);
        $customersTotal = (clone $customers)->count();
        $customersMonth = (clone $customers)->where('created_at', '>=', $monthStart)->count();

        return "BUSINESS FIGURES (owner only — as of " . now()->format('d M Y, h:i A') . "):\n"
            . "- Leads (service requests): {$leadsTotal} total, {$leadsMonth} this month, {$leadsOpen} not yet completed\n"
            . '- Sales today: ' . (int) $salesToday->bills . ' bills, ' . _price((float) $salesToday->amount) . "\n"
            . '- Sales this month: ' . (int) $salesMonth->bills . ' bills, ' . _price((float) $salesMonth->amount) . "\n"
            . "- Customers: {$customersTotal} total, {$customersMonth} added this month\n";
    }

    /** Digits only, trailing KEY_DIGITS — the same shape SendAutoReply keys a conversation by. */
    protected static function key(string $phone): string
    {
        return substr(preg_replace('/[^0-9]/', '', $phone) ?? '', -self::KEY_DIGITS);
    }

    /** Match a stored phone on its trailing digits, however it happens to be formatted. */
    protected static function matchPhone($query, string $phoneKey)
    {
        return $query->whereRaw(
            "RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(phone,''), ' ', ''), '-', ''), '+', ''), ?) = ?",
            [self::KEY_DIGITS, $phoneKey]
        );
    }
}
