<?php
 
namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
 
/**
 * Vendor AI generators (Phase 4 §4.1). Two agents share one mechanism:
 *   Sam   — operations: quotation drafts, WhatsApp replies, business descriptions.
 *   Zayan — marketing:  social posts, SEO content, sales/CRM outreach.
 * Calls OpenAI directly (gpt-4o-mini) — the key already lives in the main app, and one-off
 * generations shouldn't touch the vendor chat memory. Output is returned for the vendor to edit.
 */
class AiToolController extends Controller
{
    private const SAM_TOOLS   = ['quotation', 'reply', 'description'];
    private const ZAYAN_TOOLS = ['social', 'seo', 'outreach'];

    public function index()
    {
        return view('vendor-views.ai-tools.index', [
            'pageTitle' => 'AI Assistant',
            'agent'     => 'Sam',
            'subtitle'  => 'Generate quotations, replies and descriptions in seconds. Review and edit before you use them.',
            'tools'     => $this->samToolMeta(),
        ]);
    }

    public function marketing()
    {
        return view('vendor-views.ai-tools.index', [
            'pageTitle' => 'Marketing AI',
            'agent'     => 'Zayan',
            'subtitle'  => 'Create social posts, SEO content and follow-up messages that bring customers back.',
            'tools'     => $this->zayanToolMeta(),
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'tool'    => 'required|in:' . implode(',', array_merge(self::SAM_TOOLS, self::ZAYAN_TOOLS)),
            'input'   => 'required|string|max:2000',
            'context' => 'nullable|string|max:1000',
        ]);

        $store = DB::table('stores')->where('id', Helpers::get_store_id())->first(['name', 'address', 'gst']);
        $storeName = $store->name ?? 'the business';

        [$system, $user] = $this->prompt($request->tool, $storeName, trim($request->input), trim((string) $request->context));

        $key = config('services.openai.key');
        if (!$key) {
            return response()->json(['success' => false, 'message' => 'AI is not configured. Please contact support.'], 500);
        }

        try {
            $response = Http::timeout(45)->withToken($key)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => 'gpt-4o-mini',
                'max_tokens'  => 900,
                'temperature' => 0.75,
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

    private function samToolMeta(): array
    {
        return [
            'quotation'   => ['title' => 'Quotation Generator', 'icon' => 'tio-receipt-outlined', 'desc' => 'Draft a professional price quote to send a customer.', 'ph' => 'e.g. AC service + gas refill for a 1.5-ton split AC, customer Ramesh, Renigunta. ₹1200 service, ₹1800 gas.'],
            'reply'       => ['title' => 'WhatsApp Reply', 'icon' => 'tio-chat', 'desc' => 'Get 3 ready replies to a customer message.', 'ph' => "Paste the customer's WhatsApp message here…"],
            'description' => ['title' => 'Business Description', 'icon' => 'tio-user-outlined', 'desc' => 'Write a polished description for your store page.', 'ph' => 'e.g. We repair ACs, washing machines and fridges across Tirupati. 10 yrs experience, same-day service, genuine parts.'],
        ];
    }

    private function zayanToolMeta(): array
    {
        return [
            'social'   => ['title' => 'Social Media Post', 'icon' => 'tio-instagram', 'desc' => 'A ready-to-post caption with hashtags.', 'ph' => 'e.g. Monsoon offer — 20% off AC deep cleaning this week only.'],
            'seo'      => ['title' => 'SEO Content', 'icon' => 'tio-search', 'desc' => 'SEO title, meta and a paragraph for your page.', 'ph' => 'e.g. Washing machine repair service in Tirupati'],
            'outreach' => ['title' => 'Follow-up Message', 'icon' => 'tio-send-outlined', 'desc' => 'Re-engage a lead who didn’t convert.', 'ph' => 'e.g. Customer Priya asked about AC installation last week but didn’t book.'],
        ];
    }

    /** @return array{0:string,1:string} [system, user] */
    private function prompt(string $tool, string $storeName, string $input, string $context): array
    {
        $ctx = $context ? "\n\nExtra context: {$context}" : '';

        switch ($tool) {
            case 'quotation':
                return [
                    "You are Sam, assistant for \"{$storeName}\", a local service business in India. Draft a clear, professional "
                    . "price quotation to send a customer: a simple itemised layout (service — description — amount), a subtotal, "
                    . "and a polite closing. Use ₹. If a price is missing, put '₹____' for the vendor to fill. Concise, ready to send. Plain text.",
                    "Quotation request details:\n{$input}{$ctx}",
                ];
            case 'reply':
                return [
                    "You are Sam, helping \"{$storeName}\" reply to a customer on WhatsApp. Write 3 short, friendly, professional "
                    . "reply options (numbered 1–3), 1–3 sentences each, warm Indian-business tone. Don't invent prices or commitments.",
                    "Customer's message:\n{$input}{$ctx}",
                ];
            case 'description':
                return [
                    "You are Sam, writing a business description for \"{$storeName}\" (a local service business in India) for its "
                    . "My Chitti profile. 2 short paragraphs (60–110 words): what they do, who they serve, why customers trust them. "
                    . "Natural, benefit-led, SEO-friendly (service + city naturally), no keyword stuffing, no false claims. Plain text.",
                    "About the business:\n{$input}{$ctx}",
                ];
            case 'social':
                return [
                    "You are Zayan, a social-media marketer for \"{$storeName}\", a local Indian business. Write 2 short, catchy "
                    . "post captions (for Instagram/Facebook) for the given topic, each with a clear call-to-action and 4–6 relevant "
                    . "local hashtags. Friendly, upbeat, no over-promising. Label them 'Option 1' and 'Option 2'.",
                    "Post topic:\n{$input}{$ctx}",
                ];
            case 'seo':
                return [
                    "You are Zayan, an SEO writer for \"{$storeName}\", a local Indian service business. For the given service/topic, "
                    . "produce: an SEO Title (<=60 chars), a Meta Description (<=155 chars), and a natural 90–130 word paragraph — all "
                    . "including the service and city naturally, no keyword stuffing, no false claims. Label each part clearly. Plain text.",
                    "Service / topic:\n{$input}{$ctx}",
                ];
            case 'outreach':
            default:
                return [
                    "You are Zayan, writing a warm sales follow-up for \"{$storeName}\" to re-engage a customer who enquired but didn't "
                    . "book. Write 2 short messages (WhatsApp-friendly), polite and helpful (not pushy), inviting them to proceed or ask "
                    . "questions. Label 'Option 1' and 'Option 2'. Don't invent prices the vendor didn't state.",
                    "Lead / situation:\n{$input}{$ctx}",
                ];
        }
    }
}
