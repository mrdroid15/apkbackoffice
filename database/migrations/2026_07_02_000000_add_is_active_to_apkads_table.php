<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apkads', function (Blueprint $table) {
            // Per-ad kill switch. When false the public /api lookup treats the
            // row as missing (404) so apps stop serving the ad without the row
            // (or its uploaded image) being deleted. Defaults to true so every
            // existing ad stays live after this migration runs.
            $table->boolean('is_active')->default(true)->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('apkads', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
