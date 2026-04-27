<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apkads', function (Blueprint $table) {
            // The editable HTML body produced by the RichEditor. Nullable so
            // existing rows stay valid; the public route 404s when this is
            // empty rather than rendering an empty page.
            $table->longText('privacy_policy')->nullable()->after('link');

            // The URL slug used at privacy-policy.{domain}/{slug}. Computed at
            // save time from `packagename` (or slugified `name` as fallback)
            // and kept stable thereafter so bookmarked URLs don't break.
            $table->string('privacy_policy_slug')->nullable()->unique()->after('privacy_policy');

            // When the auto-generator was last run. Surfaced in the admin UI
            // so the editor can see whether they're looking at template text
            // or hand-edited content.
            $table->timestamp('privacy_policy_generated_at')->nullable()->after('privacy_policy_slug');
        });
    }

    public function down(): void
    {
        Schema::table('apkads', function (Blueprint $table) {
            $table->dropUnique(['privacy_policy_slug']);
            $table->dropColumn([
                'privacy_policy',
                'privacy_policy_slug',
                'privacy_policy_generated_at',
            ]);
        });
    }
};
