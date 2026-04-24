<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\McCampaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    // ── All campaigns listing ────────────────────────────────────────────────

    public function list()
    {
        $campaigns = McCampaign::whereIn('status', ['active', 'ended', 'draft'])
            ->latest()
            ->get();

        view()->share('campaign', null);
        return view('front-views.campaigns.list', compact('campaigns'));
    }

    // ── Individual campaign pages ────────────────────────────────────────────

    public function index(string $slug)
    {
        $campaign = $this->load($slug);
        return view('front-views.campaign.index', compact('campaign'));
    }

    public function how_it_works(string $slug)
    {
        $campaign = $this->load($slug);
        return view('front-views.campaign.how-it-works', compact('campaign'));
    }

    public function results(string $slug)
    {
        $campaign = $this->load($slug, ['winners']);
        return view('front-views.campaign.results', compact('campaign'));
    }

    public function winners(string $slug)
    {
        $campaign = $this->load($slug, ['winners']);
        return view('front-views.campaign.winners', compact('campaign'));
    }

    public function faq(string $slug)
    {
        $campaign = $this->load($slug, ['faqs']);
        return view('front-views.campaign.faq', compact('campaign'));
    }

    public function tnc(string $slug)
    {
        $campaign = $this->load($slug);
        return view('front-views.campaign.tnc', compact('campaign'));
    }

    public function enter(string $slug)
    {
        $campaign = $this->load($slug);
        return view('front-views.campaign.enter', compact('campaign'));
    }

    public function sponsors(string $slug)
    {
        $campaign = $this->load($slug, ['sponsors']);
        return view('front-views.campaign.sponsors', compact('campaign'));
    }

    public function ambassadors(string $slug)
    {
        $campaign = $this->load($slug, ['influencers']);
        return view('front-views.campaign.ambassadors', compact('campaign'));
    }

    public function team(string $slug)
    {
        $campaign = $this->load($slug, ['teamMembers']);
        return view('front-views.campaign.team', compact('campaign'));
    }

    public function partners(string $slug)
    {
        $campaign = $this->load($slug, ['collaborations']);
        return view('front-views.campaign.partners', compact('campaign'));
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function load(string $slug, array $with = []): McCampaign
    {
        $always = ['faqs', 'winners', 'sponsors'];
        $campaign = McCampaign::with(array_unique(array_merge($always, $with)))
            ->where('slug', $slug)
            ->firstOrFail();

        view()->share('campaign', $campaign);
        return $campaign;
    }
}
