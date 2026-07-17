<?php

namespace App\Http\Controllers\Vendor;

use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Review;
use App\Http\Controllers\Controller;
use App\Models\StoreReview;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::whereHas('item', function($query){
            return $query->where('store_id', Helpers::get_store_id());
        })->latest()->paginate(config('default_pagination'));
        return view('vendor-views.review.index', compact('reviews'));
    }
    public function submit_reply(Request $request) {
        // prx($request->all());
        $rev = StoreReview::find($request->rev_id);
        $rev->reply = $request->reply;
        $rev->replied_at = now();
        $rev->save();

        Toastr::success(translate('replied successfully'));
        return redirect()->back();
    }

    /**
     * Draft a public reply to a specific review with AI (Phase 4 §4.1 "AI Reply Drafts", made
     * contextual). Reads the actual review's rating + comment, scoped to the vendor's own store,
     * and returns a ready-to-edit reply the vendor pastes into the reply box. OpenAI gpt-4o-mini.
     */
    public function ai_draft_reply(Request $request)
    {
        $request->validate(['rev_id' => 'required']);

        $rev = StoreReview::where('id', $request->rev_id)
            ->where('store_id', Helpers::get_store_id())
            ->first();

        if (!$rev) {
            return response()->json(['success' => false, 'message' => 'Review not found.'], 404);
        }

        $store     = DB::table('stores')->where('id', $rev->store_id)->first(['name']);
        $storeName = $store->name ?? 'the business';
        $comment   = trim((string) $rev->comment);
        $rating    = (int) $rev->rating;
        $firstName = optional($rev->user)->f_name ?: '';

        $key = config('services.openai.key');
        if (!$key) {
            return response()->json(['success' => false, 'message' => 'AI is not configured. Please contact support.'], 500);
        }

        $system = "You are replying on behalf of \"{$storeName}\", a local service business in India, to a "
            . "customer review on its My Chitti profile. Write ONE short, warm, genuine public reply (2–3 sentences). "
            . "Greet the reviewer by first name if given. If the rating is 3 or below or the comment raises a problem, "
            . "apologise sincerely, take brief responsibility, and invite them to reach out so you can make it right — "
            . "never be defensive, never make excuses, never promise specific refunds or compensation. If the rating is "
            . "high, thank them warmly and invite them back. Sound human, not corporate. No hashtags, no emojis overload. "
            . "Plain text, ready to post as-is.";

        $user = "Reviewer first name: " . ($firstName !== '' ? $firstName : '(not given)') . "\n"
            . "Rating: {$rating}/5\n"
            . "Their review: \"" . ($comment !== '' ? $comment : '(no written comment — rating only)') . "\"";

        try {
            $response = Http::timeout(45)->withToken($key)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 300,
                'temperature' => 0.7,
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI service is temporarily unavailable. Please try again.'], 503);
        }

        if (!$response->ok()) {
            return response()->json(['success' => false, 'message' => 'Could not generate right now. Please try again.'], 502);
        }

        $text = trim((string) $response->json('choices.0.message.content'));
        if ($text === '') {
            return response()->json(['success' => false, 'message' => 'Empty response — please try again.'], 502);
        }

        return response()->json(['success' => true, 'text' => $text]);
    }
}
