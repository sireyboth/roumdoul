<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-on prices, set per plan (Digital Invitations only). A blank price = that add-on isn't sold for this plan.
 *   extra_guest_price  price of one extra guest link
 *   extra_photo_price  price of one extra gallery photo
 *   feature_prices     {"rsvp_enabled": 2.00, "countdown_enabled": 1.00, …} — unlock one premium field
 *   extend_price       price of one extension of extend_months (default 6) months
 * Upgrading to a bigger plan of the same product costs the price difference (no field needed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->decimal('extra_guest_price', 8, 2)->nullable()->after('max_gallery_photos');
            $table->decimal('extra_photo_price', 8, 2)->nullable()->after('extra_guest_price');
            $table->json('feature_prices')->nullable()->after('extra_photo_price');
            $table->decimal('extend_price', 8, 2)->nullable()->after('feature_prices');
            $table->unsignedInteger('extend_months')->nullable()->after('extend_price');
        });
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn(['extra_guest_price', 'extra_photo_price', 'feature_prices', 'extend_price', 'extend_months']);
        });
    }
};
