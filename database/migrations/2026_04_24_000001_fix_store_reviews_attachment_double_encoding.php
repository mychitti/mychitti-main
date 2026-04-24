<?php

use Illuminate\Database\Migrations\Migration;

class FixStoreReviewsAttachmentDoubleEncoding extends Migration
{
    public function up()
    {
        // Rows where attachment was double-encoded (json_encode on an already-json string)
        // are stored as a JSON string type e.g. "\"[\\\"path\\\"]\""
        // JSON_UNQUOTE unwraps the outer string to restore a proper JSON array.
        DB::statement("
            UPDATE store_reviews
            SET attachment = JSON_UNQUOTE(attachment)
            WHERE attachment IS NOT NULL
              AND JSON_VALID(attachment)
              AND JSON_TYPE(attachment) = 'STRING'
        ");
    }

    public function down()
    {
        // Re-wrap the JSON arrays back into double-encoded strings
        DB::statement("
            UPDATE store_reviews
            SET attachment = JSON_QUOTE(attachment)
            WHERE attachment IS NOT NULL
              AND JSON_VALID(attachment)
              AND JSON_TYPE(attachment) != 'STRING'
        ");
    }
}
