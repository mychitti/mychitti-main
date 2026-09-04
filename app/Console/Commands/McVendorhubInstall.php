<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Installs the MC Vendorhub separation:
 *  - stores.show_in_mychitti  : vendor opt-out from the MyChitti consumer marketplace
 *  - contacts.brand / leads.brand : which brand a sales & marketing record belongs to
 *  - renames the "MY CITY" module to "MyChitti"
 */
class McVendorhubInstall extends Command
{
    protected $signature = 'mcvendorhub:install';
    protected $description = 'Install the MC Vendorhub module columns and rename the MY CITY module to MyChitti';

    public function handle(): int
    {
        $this->storeVisibilityColumn();
        $this->brandColumn('contacts');
        $this->brandColumn('leads');
        $this->renameMyCityModule();
        $this->info('MC Vendorhub module installed.');
        return self::SUCCESS;
    }

    private function storeVisibilityColumn(): void
    {
        if (!Schema::hasTable('stores')) {
            $this->warn('stores table not found, skipped.');
            return;
        }
        if (Schema::hasColumn('stores', 'show_in_mychitti')) {
            $this->line('stores.show_in_mychitti already exists.');
            return;
        }
        DB::statement("ALTER TABLE stores ADD COLUMN show_in_mychitti TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
        DB::statement("ALTER TABLE stores ADD INDEX idx_show_in_mychitti (show_in_mychitti)");
        $this->info('Added stores.show_in_mychitti.');
    }

    private function brandColumn(string $table): void
    {
        if (!Schema::hasTable($table)) {
            $this->warn($table . ' table not found, skipped.');
            return;
        }
        if (Schema::hasColumn($table, 'brand')) {
            $this->line($table . '.brand already exists.');
            return;
        }
        DB::statement("ALTER TABLE {$table} ADD COLUMN brand VARCHAR(20) NOT NULL DEFAULT 'mychitti'");
        DB::statement("ALTER TABLE {$table} ADD INDEX idx_brand (brand)");
        $this->info('Added ' . $table . '.brand.');

        // Historical MC Vendorhub enquiries were already tagged with contacts.type = 'mc_vendor'
        if ($table === 'contacts' && Schema::hasColumn('contacts', 'type')) {
            $moved = DB::table('contacts')->where('type', 'mc_vendor')->update(['brand' => 'mcvendorhub']);
            $this->info('Backfilled ' . $moved . ' existing contacts as mcvendorhub.');
        }
    }

    private function renameMyCityModule(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }
        $renamed = DB::table('modules')
            ->whereIn(DB::raw('LOWER(module_name)'), ['my city', 'mycity', 'my-city'])
            ->update(['module_name' => 'MyChitti']);

        if ($renamed) {
            $this->info('Renamed the MY CITY module to MyChitti.');
            if (Schema::hasTable('translations')) {
                DB::table('translations')
                    ->where('translationable_type', 'App\Models\Module')
                    ->where('key', 'module_name')
                    ->whereIn(DB::raw('LOWER(value)'), ['my city', 'mycity', 'my-city'])
                    ->update(['value' => 'MyChitti']);
            }
        } else {
            $this->line('No MY CITY module found to rename.');
        }
    }
}
