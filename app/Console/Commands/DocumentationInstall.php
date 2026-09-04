<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Installs the admin Documentation module (document library: SRS, write-ups, files, versions)
 * and the API Endpoints registry (projects, each holding its own endpoints).
 */
class DocumentationInstall extends Command
{
    protected $signature = 'documentation:install';
    protected $description = 'Install the admin Documentation and API Endpoints tables';

    public function handle(): int
    {
        $this->categories();
        $this->documents();
        $this->files();
        $this->versions();
        $this->apiProjects();
        $this->apiEndpoints();
        $this->migrateLegacyEndpoints();
        $this->seedCategories();
        $this->info('Documentation module installed.');
        return self::SUCCESS;
    }

    private function categories(): void
    {
        if (Schema::hasTable('documentation_categories')) {
            return;
        }
        DB::statement("
            CREATE TABLE documentation_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                color VARCHAR(20) NULL,
                description VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status TINYINT NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->info('Created documentation_categories.');
    }

    private function documents(): void
    {
        if (Schema::hasTable('documentations')) {
            return;
        }
        DB::statement("
            CREATE TABLE documentations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(280) NOT NULL,
                category_id BIGINT UNSIGNED NULL,
                doc_type VARCHAR(20) NOT NULL DEFAULT 'editor',
                summary TEXT NULL,
                content LONGTEXT NULL,
                version VARCHAR(20) NOT NULL DEFAULT '1.0',
                tags VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_slug (slug),
                KEY idx_category (category_id),
                KEY idx_status (status),
                KEY idx_type (doc_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->info('Created documentations.');
    }

    private function files(): void
    {
        if (Schema::hasTable('documentation_files')) {
            return;
        }
        DB::statement("
            CREATE TABLE documentation_files (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                documentation_id BIGINT UNSIGNED NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                extension VARCHAR(20) NULL,
                mime VARCHAR(150) NULL,
                size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                uploaded_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                KEY idx_doc (documentation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->info('Created documentation_files.');
    }

    private function versions(): void
    {
        if (Schema::hasTable('documentation_versions')) {
            return;
        }
        DB::statement("
            CREATE TABLE documentation_versions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                documentation_id BIGINT UNSIGNED NOT NULL,
                version VARCHAR(20) NOT NULL,
                source VARCHAR(20) NOT NULL DEFAULT 'content',
                content LONGTEXT NULL,
                file_name VARCHAR(255) NULL,
                stored_name VARCHAR(255) NULL,
                extension VARCHAR(20) NULL,
                size BIGINT UNSIGNED NOT NULL DEFAULT 0,
                note VARCHAR(255) NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                KEY idx_doc (documentation_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->info('Created documentation_versions.');
    }

    /**
     * A project is the app the endpoints belong to — User App, Vendor App, Admin Panel.
     */
    private function apiProjects(): void
    {
        if (Schema::hasTable('api_projects')) {
            return;
        }
        DB::statement("
            CREATE TABLE api_projects (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(170) NOT NULL,
                base_url VARCHAR(255) NULL,
                version VARCHAR(20) NULL,
                color VARCHAR(20) NULL,
                description TEXT NULL,
                status TINYINT NOT NULL DEFAULT 1,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                UNIQUE KEY uq_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->info('Created api_projects.');
    }

    /**
     * params / headers / images hold JSON arrays — repeatable rows in the UI, and a plain
     * "key = value" block when they travel through Excel.
     */
    private function apiEndpoints(): void
    {
        if (Schema::hasTable('api_endpoints')) {
            return;
        }
        DB::statement("
            CREATE TABLE api_endpoints (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id BIGINT UNSIGNED NOT NULL,
                folder VARCHAR(190) NULL,
                name VARCHAR(190) NULL,
                method VARCHAR(10) NOT NULL DEFAULT 'GET',
                endpoint VARCHAR(500) NOT NULL,
                auth_type VARCHAR(60) NULL,
                description TEXT NULL,
                params LONGTEXT NULL,
                headers LONGTEXT NULL,
                request_body LONGTEXT NULL,
                response_sample LONGTEXT NULL,
                status_code VARCHAR(20) NULL,
                usage_note TEXT NULL,
                images LONGTEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                KEY idx_project (project_id),
                KEY idx_method (method)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $this->info('Created api_endpoints.');
    }

    /**
     * Endpoints used to hang off a document before API Endpoints became its own module. Anything
     * installed before that split is moved into one project per owning document so nothing is
     * stranded; the old table is renamed rather than dropped.
     */
    private function migrateLegacyEndpoints(): void
    {
        if (!Schema::hasTable('documentation_endpoints') || !Schema::hasTable('api_endpoints')) {
            return;
        }
        if (DB::table('api_endpoints')->exists() || !DB::table('documentation_endpoints')->exists()) {
            return;
        }

        $moved = 0;
        foreach (DB::table('documentation_endpoints')->distinct()->pluck('documentation_id') as $docId) {
            $title = DB::table('documentations')->where('id', $docId)->value('title') ?? ('Project #' . $docId);

            $projectId = DB::table('api_projects')->insertGetId([
                'name'       => $title,
                'slug'       => Str::slug($title) . '-' . Str::lower(Str::random(5)),
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (DB::table('documentation_endpoints')->where('documentation_id', $docId)->get() as $row) {
                DB::table('api_endpoints')->insert([
                    'project_id'      => $projectId,
                    'folder'          => $row->folder,
                    'method'          => $row->method,
                    'endpoint'        => $row->endpoint,
                    'auth_type'       => $row->auth_type,
                    'description'     => $row->description,
                    'params'          => $row->request_params,
                    'request_body'    => $row->request_body,
                    'response_sample' => $row->response_sample,
                    'status_code'     => $row->status_code,
                    'sort_order'      => $row->sort_order,
                    'created_at'      => $row->created_at,
                    'updated_at'      => $row->updated_at,
                ]);
                $moved++;
            }
        }

        DB::statement('RENAME TABLE documentation_endpoints TO documentation_endpoints_legacy');
        $this->info("Moved {$moved} endpoint(s) into api_projects; old table renamed to documentation_endpoints_legacy.");
    }

    private function seedCategories(): void
    {
        if (DB::table('documentation_categories')->exists()) {
            return;
        }
        $defaults = [
            ['name' => 'SRS',                'color' => '#6c5ce7', 'description' => 'Software Requirement Specifications'],
            ['name' => 'API Documentation',  'color' => '#1a73e8', 'description' => 'Integration guides and API write-ups'],
            ['name' => 'Technical Design',   'color' => '#00b894', 'description' => 'Architecture and design documents'],
            ['name' => 'User Guide',         'color' => '#fdcb6e', 'description' => 'How-to guides and manuals'],
            ['name' => 'Process',            'color' => '#e17055', 'description' => 'SOPs and internal processes'],
        ];
        foreach ($defaults as $i => $row) {
            DB::table('documentation_categories')->insert([
                'name'        => $row['name'],
                'slug'        => Str::slug($row['name']),
                'color'       => $row['color'],
                'description' => $row['description'],
                'sort_order'  => $i,
                'status'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
        $this->info('Seeded ' . count($defaults) . ' default categories.');
    }
}
