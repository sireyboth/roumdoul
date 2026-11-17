<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every invitation keeps its own limits, copied from its plan when the order is paid (and raised by
 * add-ons later), so editing a plan in admin only affects new buyers — never invitations already sold.
 * Existing invitations are backfilled from their plan, so nothing changes for them today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->unsignedInteger('max_gallery_photos')->nullable()->after('max_recipients');
            $table->json('features')->nullable()->after('max_gallery_photos');
            $table->timestamp('expiry_reminded_at')->nullable()->after('expires_at');
        });

        DB::table('invitations')->whereNotNull('service_plan_id')->orderBy('id')->each(function ($invitation) {
            $plan = DB::table('service_plans')->where('id', $invitation->service_plan_id)->first();
            if ($plan) {
                DB::table('invitations')->where('id', $invitation->id)->update([
                    'max_gallery_photos' => $plan->max_gallery_photos,
                    'features' => $plan->features,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn(['max_gallery_photos', 'features', 'expiry_reminded_at']);
        });
    }
};
