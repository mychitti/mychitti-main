<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\McCampaign;
use App\Models\McCampaignFaq;
use App\Models\McCampaignWinner;
use App\Models\McCampaignSponsor;
use App\Models\McCampaignInfluencer;
use App\Models\McCampaignGuest;
use App\Models\McCampaignCollaboration;
use App\Models\McCampaignBudgetItem;
use App\Models\McCampaignExpense;
use App\Models\McCampaignTarget;
use App\Models\McCampaignTeamMember;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketingCampaignController extends Controller
{
    // ─── List ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $campaigns = McCampaign::when($request->search, fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        return view('admin-views.mc.list', compact('campaigns'));
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function create()
    {
        return view('admin-views.mc.form', ['campaign' => null]);
    }

    // ─── Store ───────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:draft,active,ended,cancelled',
        ]);

        $data = $this->extractCampaignData($request);
        $data['created_by'] = auth('admin')->id();

        $campaign = McCampaign::create($data);

        $this->syncFaqs($campaign, $request);

        Toastr::success('Campaign created successfully.');
        return redirect()->route('admin.mc.edit', $campaign->id);
    }

    // ─── Edit ────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $campaign = McCampaign::with([
            'faqs', 'winners', 'sponsors', 'influencers',
            'guests', 'collaborations', 'budgetItems',
            'expenses', 'targets', 'teamMembers',
        ])->findOrFail($id);

        return view('admin-views.mc.form', compact('campaign'));
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'status' => 'required|in:draft,active,ended,cancelled',
        ]);

        $campaign = McCampaign::findOrFail($id);
        $campaign->update($this->extractCampaignData($request));
        $this->syncFaqs($campaign, $request);

        Toastr::success('Campaign updated successfully.');
        return redirect()->route('admin.mc.edit', $campaign->id);
    }

    // ─── Status toggle ───────────────────────────────────────────────────────

    public function status($id, $status)
    {
        McCampaign::findOrFail($id)->update(['status' => $status]);
        Toastr::success('Status updated.');
        return back();
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        McCampaign::findOrFail($id)->delete();
        Toastr::success('Campaign deleted.');
        return back();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Sponsors
    // ═════════════════════════════════════════════════════════════════════════

    public function storeSponsor(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $campaign = McCampaign::findOrFail($id);

        $logo = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo')->store('mc/sponsors', 'public');
        }

        $campaign->sponsors()->create([
            'name'       => $request->name,
            'logo'       => $logo,
            'website'    => $request->website,
            'tier'       => $request->tier ?? 'partner',
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json(['success' => true, 'data' => $campaign->sponsors()->orderBy('sort_order')->get()]);
    }

    public function destroySponsor($id, $sponsorId)
    {
        $sponsor = McCampaignSponsor::where('campaign_id', $id)->findOrFail($sponsorId);
        if ($sponsor->logo) Storage::disk('public')->delete($sponsor->logo);
        $sponsor->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Influencers
    // ═════════════════════════════════════════════════════════════════════════

    public function storeInfluencer(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->influencers()->create($request->only('name', 'handle', 'platform', 'followers', 'agreed_rate', 'notes'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyInfluencer($id, $rid)
    {
        McCampaignInfluencer::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Guests
    // ═════════════════════════════════════════════════════════════════════════

    public function storeGuest(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->guests()->create($request->only('name', 'email', 'phone', 'type', 'notes'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyGuest($id, $rid)
    {
        McCampaignGuest::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Collaborations
    // ═════════════════════════════════════════════════════════════════════════

    public function storeCollaboration(Request $request, $id)
    {
        $request->validate(['partner_name' => 'required|string|max:255']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->collaborations()->create($request->only('partner_name', 'type', 'terms', 'status', 'notes'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyCollaboration($id, $rid)
    {
        McCampaignCollaboration::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Budget Items
    // ═════════════════════════════════════════════════════════════════════════

    public function storeBudgetItem(Request $request, $id)
    {
        $request->validate(['item_description' => 'required|string', 'budgeted_amount' => 'required|numeric']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->budgetItems()->create($request->only('category', 'item_description', 'budgeted_amount', 'actual_amount', 'notes'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyBudgetItem($id, $rid)
    {
        McCampaignBudgetItem::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Expenses
    // ═════════════════════════════════════════════════════════════════════════

    public function storeExpense(Request $request, $id)
    {
        $request->validate(['title' => 'required|string', 'amount' => 'required|numeric']);
        $campaign = McCampaign::findOrFail($id);

        $receipt = null;
        if ($request->hasFile('receipt')) {
            $receipt = $request->file('receipt')->store('mc/receipts', 'public');
        }

        $record = $campaign->expenses()->create(array_merge(
            $request->only('title', 'category', 'amount', 'expense_date', 'notes'),
            ['receipt' => $receipt]
        ));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyExpense($id, $rid)
    {
        $rec = McCampaignExpense::where('campaign_id', $id)->findOrFail($rid);
        if ($rec->receipt) Storage::disk('public')->delete($rec->receipt);
        $rec->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Targets
    // ═════════════════════════════════════════════════════════════════════════

    public function storeTarget(Request $request, $id)
    {
        $request->validate(['metric' => 'required|string', 'target_value' => 'required|numeric']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->targets()->create($request->only('metric', 'target_value', 'current_value', 'unit', 'notes'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function updateTarget(Request $request, $id, $rid)
    {
        $target = McCampaignTarget::where('campaign_id', $id)->findOrFail($rid);
        $target->update($request->only('current_value'));
        return response()->json(['success' => true, 'progress' => $target->progress]);
    }

    public function destroyTarget($id, $rid)
    {
        McCampaignTarget::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Team Members
    // ═════════════════════════════════════════════════════════════════════════

    public function storeTeamMember(Request $request, $id)
    {
        $request->validate(['member_name' => 'required|string', 'role' => 'required|string']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->teamMembers()->create($request->only('member_name', 'role', 'responsibilities', 'phone', 'email'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyTeamMember($id, $rid)
    {
        McCampaignTeamMember::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // AJAX — Winners
    // ═════════════════════════════════════════════════════════════════════════

    public function storeWinner(Request $request, $id)
    {
        $request->validate(['winner_name' => 'required|string']);
        $campaign = McCampaign::findOrFail($id);
        $record = $campaign->winners()->create($request->only('winner_name', 'position', 'prize_detail', 'drawn_at', 'notes'));
        return response()->json(['success' => true, 'data' => $record]);
    }

    public function destroyWinner($id, $rid)
    {
        McCampaignWinner::where('campaign_id', $id)->findOrFail($rid)->delete();
        return response()->json(['success' => true]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // Helpers
    // ═════════════════════════════════════════════════════════════════════════

    private function extractCampaignData(Request $request): array
    {
        $data = $request->only([
            'name', 'status', 'start_date', 'end_date', 'draw_date',
            'prize_title', 'prize_value', 'number_of_winners', 'prize_description',
            'max_entries_per_person', 'total_entry_limit',
            'page_content', 'how_it_works', 'terms',
            'meta_title', 'meta_description',
            'require_login', 'show_entry_count', 'plan_notes',
        ]);

        // Auto-generate slug from name if not provided
        $data['slug'] = $request->filled('slug')
            ? Str::slug($request->slug)
            : Str::slug($request->name);

        // Banner image upload
        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('mc/banners', 'public');
        }

        // OG image upload
        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('mc/og', 'public');
        }

        $data['require_login']    = $request->boolean('require_login');
        $data['show_entry_count'] = $request->boolean('show_entry_count');

        return $data;
    }

    private function syncFaqs(McCampaign $campaign, Request $request): void
    {
        if (!$request->has('faqs')) return;

        $campaign->faqs()->delete();
        foreach ($request->faqs as $i => $faq) {
            if (!empty($faq['question'])) {
                $campaign->faqs()->create([
                    'question'   => $faq['question'],
                    'answer'     => $faq['answer'] ?? '',
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
