<?php

namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request) {
        return view('front-views.campaign.index');
    }
    public function how_it_works(Request $request) {
        return view('front-views.campaign.how-it-works');
    }
    public function results(Request $request) {
        return view('front-views.campaign.results');
    }
    public function winners(Request $request) {
        return view('front-views.campaign.winners');
    }
    public function faq(Request $request) {
        return view('front-views.campaign.faq');
    }
    public function enter(Request $request) {
        return view('front-views.campaign.enter');
    }
    public function tnc(Request $request) {
        return view('front-views.campaign.tnc');
    }
}
