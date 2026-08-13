<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * What happens after a patient answers "How was your experience?".
 *
 * The feedback question has always been sendable; nothing read the answer, so a tap landed in the
 * inbox and stopped. This carries it forward: a happy answer is thanked and pointed at the review
 * page and the store's Instagram, an unhappy one is asked what went wrong, and whatever they type
 * next becomes a complaint the hospital can see and work.
 *
 * Everything after the question travels as FREE-FORM text, not templates. The patient has just
 * messaged the business, so their 24-hour service window is open by definition — which makes these
 * replies instant, exempt from template approval, and free. A template would be all three of the
 * opposite.
 *
 * Attribution comes from Meta: a button tap carries context.id, the id of the message being
 * answered, so a rating lands on the exact visit that asked rather than on a guess.
 */
class FeedbackFlow
{
    const STATE_WAITING_RATING = 'awaiting_rating';
    const STATE_WAITING_ISSUE  = 'awaiting_issue';
    const STATE_CLOSED         = 'closed';

    /** Ratings, mapped from the quick-reply labels on the visit_feedback template. */
    const POSITIVE = ['very good', 'good', 'excellent', 'great'];
    const NEUTRAL  = ['okay', 'ok', 'average', 'fine'];
    const NEGATIVE = ['not good', 'bad', 'poor', 'terrible'];

    /** How long after the question an answer is still treated as answering it. */
    const REPLY_WINDOW_HOURS = 72;

    public static function ensureTables(): void
    {
        if (!Schema::hasTable('wa_feedback_threads')) {
            DB::statement("CREATE TABLE `wa_feedback_threads` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `record_id` BIGINT NULL,
                `patient_id` BIGINT NULL,
                `phone10` VARCHAR(10) NOT NULL,
                `wamid` VARCHAR(255) NULL,
                `rating` VARCHAR(12) NULL,
                `reply_label` VARCHAR(120) NULL,
                `issue_text` TEXT NULL,
                `state` VARCHAR(20) NOT NULL DEFAULT 'awaiting_rating',
                `answered_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `wft_wamid` (`wamid`),
                KEY `wft_open` (`store_id`, `phone10`, `state`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        if (!Schema::hasTable('store_complaints')) {
            DB::statement("CREATE TABLE `store_complaints` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `store_id` BIGINT NOT NULL,
                `source` VARCHAR(30) NOT NULL DEFAULT 'whatsapp_feedback',
                `patient_id` BIGINT NULL,
                `record_id` BIGINT NULL,
                `name` VARCHAR(190) NULL,
                `phone` VARCHAR(32) NULL,
                `rating` VARCHAR(12) NULL,
                `issue` TEXT NULL,
                `status` VARCHAR(20) NOT NULL DEFAULT 'open',
                `resolved_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NULL,
                `updated_at` TIMESTAMP NULL,
                PRIMARY KEY (`id`),
                KEY `sc_store` (`store_id`, `status`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    /** Last ten digits, the key every phone comparison in this codebase uses. */
    public static function phoneKey(?string $phone): string
    {
        return substr(preg_replace('/[^0-9]/', '', (string) $phone) ?? '', -10);
    }

    /**
     * Record that a feedback question went out, so its answer can be recognised later.
     * Called from the one place every feedback send funnels through.
     */
    public static function opened(int $storeId, ?int $recordId, ?int $patientId, ?string $phone, ?string $wamid): void
    {
        $key = self::phoneKey($phone);
        if ($key === '') {
            return;
        }

        try {
            self::ensureTables();

            // A newer question supersedes an older unanswered one for the same person.
            DB::table('wa_feedback_threads')
                ->where('store_id', $storeId)->where('phone10', $key)
                ->where('state', self::STATE_WAITING_RATING)
                ->update(['state' => self::STATE_CLOSED, 'updated_at' => now()]);

            DB::table('wa_feedback_threads')->insert([
                'store_id'   => $storeId,
                'record_id'  => $recordId,
                'patient_id' => $patientId,
                'phone10'    => $key,
                'wamid'      => $wamid,
                'state'      => self::STATE_WAITING_RATING,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Feedback thread not opened: ' . $e->getMessage());
        }
    }

    /**
     * Handle an inbound message that might be answering a feedback question.
     *
     * Returns TRUE when this flow consumed the message, which tells the webhook to leave it alone
     * — the AI auto-reply must never answer "the doctor was late" with a knowledge-base article.
     */
    public static function handleReply(?int $storeId, ?string $from, ?string $buttonLabel, ?string $text, ?string $contextWamid): bool
    {
        if (!$storeId || !$from) {
            return false;
        }

        try {
            if (!Schema::hasTable('wa_feedback_threads')) {
                return false;
            }

            $key = self::phoneKey($from);
            if ($key === '') {
                return false;
            }

            // A tap answers the message it was attached to. Falling back to the newest open thread
            // covers a typed "not good" and the odd client that sends no context.
            $thread = null;
            if ($contextWamid) {
                $thread = DB::table('wa_feedback_threads')
                    ->where('store_id', $storeId)->where('wamid', $contextWamid)->first();
            }
            if (!$thread) {
                $thread = DB::table('wa_feedback_threads')
                    ->where('store_id', $storeId)->where('phone10', $key)
                    ->whereIn('state', [self::STATE_WAITING_RATING, self::STATE_WAITING_ISSUE])
                    ->where('created_at', '>=', now()->subHours(self::REPLY_WINDOW_HOURS))
                    ->orderByDesc('id')->first();
            }
            if (!$thread || $thread->state === self::STATE_CLOSED) {
                return false;
            }

            $answer = trim((string) ($buttonLabel ?: $text));
            if ($answer === '') {
                return false;
            }

            if ($thread->state === self::STATE_WAITING_ISSUE) {
                return self::captureIssue($thread, $answer, $from);
            }

            $rating = self::ratingOf($answer);
            if ($rating === null) {
                // Not an answer to this question — a normal message that happens to arrive while a
                // thread is open. Leave it to the auto-reply.
                return false;
            }

            DB::table('wa_feedback_threads')->where('id', $thread->id)->update([
                'rating'      => $rating,
                'reply_label' => mb_substr($answer, 0, 120),
                'answered_at' => now(),
                'state'       => $rating === 'bad' ? self::STATE_WAITING_ISSUE : self::STATE_CLOSED,
                'updated_at'  => now(),
            ]);

            return $rating === 'bad'
                ? self::askWhatWentWrong($storeId, $from)
                : self::thankAndAskForReview($storeId, $from, $rating);
        } catch (\Throwable $e) {
            Log::error('Feedback reply handling failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Which of the three answers this is, or null when it is not an answer at all.
     *
     * Exact match, never a substring: these are button labels, and "book an appointment" contains
     * "ok". A loose match would score an ordinary enquiry as feedback, close the thread and thank
     * someone for a review they never gave. Anything that is not one of the labels falls through
     * to the auto-reply, which is where a real question belongs.
     */
    public static function ratingOf(string $answer): ?string
    {
        $a = mb_strtolower(trim($answer));

        foreach ([['bad', self::NEGATIVE], ['good', self::POSITIVE], ['okay', self::NEUTRAL]] as [$rating, $words]) {
            if (in_array($a, $words, true)) {
                return $rating;
            }
        }

        return null;
    }

    /** Happy answer: thank them, and ask for the review while they are pleased enough to leave one. */
    protected static function thankAndAskForReview(int $storeId, string $to, string $rating): bool
    {
        $store = DB::table('stores')->where('id', $storeId)
            ->first(['name', 'slug', 'zone_id', 'insta_url', 'fb_url']);
        $name = $store->name ?? 'our team';

        $lines = [
            $rating === 'good'
                ? "Thank you! We're glad you had a good experience with {$name}. 🙏"
                : "Thank you for letting us know — we'll keep working to do better at {$name}.",
        ];

        if ($url = self::storeUrl($store)) {
            $lines[] = "If you have a moment, a review really helps other patients find us: {$url}";
        }

        // Whichever socials the store has filled in under Website Settings — a store with neither
        // simply gets a shorter message rather than a line pointing nowhere.
        $socials = array_filter([
            !empty($store->insta_url) ? "Instagram: {$store->insta_url}" : null,
            !empty($store->fb_url) ? "Facebook: {$store->fb_url}" : null,
        ]);
        if ($socials) {
            $lines[] = "Follow us for updates —\n" . implode("\n", $socials);
        }

        return self::say($storeId, $to, implode("\n\n", $lines));
    }

    /** Unhappy answer: ask the one question that matters, and wait for the detail. */
    protected static function askWhatWentWrong(int $storeId, string $to): bool
    {
        return self::say(
            $storeId,
            $to,
            "We're sorry to hear that, and we'd like to put it right.\n\n"
            . "Could you tell us what went wrong? Just reply to this message — it goes straight to the team."
        );
    }

    /** Their answer to "what went wrong" becomes a complaint the store can work. */
    protected static function captureIssue($thread, string $issue, string $from): bool
    {
        $storeId = (int) $thread->store_id;

        $patient = $thread->patient_id
            ? DB::table('patients')->where('id', $thread->patient_id)->first(['name'])
            : null;

        DB::table('wa_feedback_threads')->where('id', $thread->id)->update([
            'issue_text' => mb_substr($issue, 0, 2000),
            'state'      => self::STATE_CLOSED,
            'updated_at' => now(),
        ]);

        DB::table('store_complaints')->insert([
            'store_id'   => $storeId,
            'source'     => 'whatsapp_feedback',
            'patient_id' => $thread->patient_id,
            'record_id'  => $thread->record_id,
            'name'       => $patient->name ?? null,
            'phone'      => $from,
            'rating'     => $thread->rating,
            'issue'      => mb_substr($issue, 0, 2000),
            'status'     => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::notifyStore($storeId, $patient->name ?? 'A patient', $from, $issue);

        return self::say(
            $storeId,
            $from,
            "Thank you — we've passed this to the team and someone will look into it."
        );
    }

    /**
     * Tell the store itself, on the number they run the business from.
     *
     * Best effort and deliberately quiet on failure: a complaint that is safely recorded must not
     * be lost because the store's own alert could not be delivered.
     */
    protected static function notifyStore(int $storeId, string $name, string $phone, string $issue): void
    {
        try {
            $storePhone = DB::table('stores')->where('id', $storeId)->value('phone');
            if (!$storePhone) {
                return;
            }

            WhatsAppService::make($storeId)->sendText(
                $storePhone,
                "⚠️ Unhappy feedback from {$name} ({$phone}):\n\n" . mb_substr($issue, 0, 600)
                . "\n\nOpen Feedback & Complaints in your panel to follow it up.",
                false,
                'complaint alert'
            );
        } catch (\Throwable $e) {
            Log::warning('Complaint alert not delivered: ' . $e->getMessage());
        }
    }

    /** Free-form reply inside the window the patient just opened by messaging us. */
    protected static function say(int $storeId, string $to, string $body): bool
    {
        try {
            $wa = WhatsAppService::make($storeId);
            if ($wa->source() !== 'vendor') {
                return false;
            }
            $wa->sendText($to, $body, true, 'feedback reply');
            return true;
        } catch (\Throwable $e) {
            Log::warning('Feedback reply not sent: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * The store's public page, where the rating and review box already live.
     *
     * The {city} segment is the first part of the store's zone name — the route accepts any
     * non-reserved slug there, so "tirupati" from "Tirupati, Andhra Pradesh, India" is enough.
     * Built from the zone rather than _selectedCity(), which reads a session this has no access to.
     */
    public static function storeUrl($store): ?string
    {
        if (empty($store->slug)) {
            return null;
        }

        $zone = DB::table('zones')->where('id', $store->zone_id ?? 0)->value('name');
        $city = Str::slug(trim(explode(',', (string) $zone)[0] ?? '')) ?: 'city';

        return url($city . '/store/' . $store->slug);
    }
}
