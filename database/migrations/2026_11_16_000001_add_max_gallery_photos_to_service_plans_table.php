<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many photos an invitation on this plan may hold in its photo gallery.
     * Null = the default (InvitationManagePage::MAX_GALLERY_PHOTOS).
     */
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_gallery_photos')->nullable()->after('max_recipients');
        });
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn('max_gallery_photos');
        });
    }
};
