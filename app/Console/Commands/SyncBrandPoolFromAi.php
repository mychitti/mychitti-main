<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\BrandPoolController;
use App\Models\BrandPool;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Monthly: ask AI for brand names per service category and add any genuinely new ones to the
 * shared brand pool — the same generator behind the admin "Generate with AI" button
 * (BrandPoolController::fetchAiBrandNames), just run unattended across every category instead of
 * one at a time. New brands are attached to the category they came from and left active so they
 * show up immediately in vendor inventory picks and "Brand + Service" SEO chips.
 */
class SyncBrandPoolFromAi extends Command
{
    protected $signature = 'brand-pool:ai-sync {--count=15 : Brands to request per category}';

    protected $description = 'Ask AI for brand names per category and add new ones to the shared brand pool';

    public function handle(BrandPoolController $brandPoolController): int
    {
        $count = max(5, min(50, (int) $this->option('count')));

        $categories = Category::whereNull('added_by')
            ->where('status', 1)
            ->where('position', 0)
            ->where(function ($q) {
                $q->where('parent_id', 0)->orWhereNull('parent_id');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $added = 0;
        $linked = 0;

        foreach ($categories as $category) {
            try {
                $names = $brandPoolController->fetchAiBrandNames($category->name, 'category', '', $count);
            } catch (\Throwable $e) {
                Log::warning('brand-pool:ai-sync failed for category "' . $category->name . '": ' . $e->getMessage());
                continue;
            }

            foreach ($names as $name) {
                $name = trim($name);
                if ($name === '') continue;

                $slug  = Str::slug($name);
                $brand = BrandPool::where('slug', $slug)->first();

                if ($brand) {
                    if (!$brand->categories()->where('category_id', $category->id)->exists()) {
                        $brand->categories()->attach($category->id);
                        $linked++;
                    }
                    continue;
                }

                $brand = BrandPool::create([
                    'name'   => $name,
                    'slug'   => BrandPool::makeSlug($name),
                    'status' => 'active',
                ]);
                $brand->categories()->attach($category->id);
                $added++;
            }
        }

        $this->info("brand-pool:ai-sync complete — {$added} new brand(s) added, {$linked} existing brand(s) newly linked, across {$categories->count()} categories.");

        return self::SUCCESS;
    }
}
