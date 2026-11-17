<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders.type: 'purchase' (a normal checkout) or 'addon' (extras for one invitation the customer already owns,
 * orders.invitation_id). order_items.addon_type / addon_value say what each add-on line buys:
 *   guests (quantity = how many), photos (quantity = how many), feature (addon_value = field key),
 *   upgrade (service_plan_id = the new plan), extend (quantity = how many extensions).
 * Payment check: an admin marks an order paid with the amount received and the bank transaction ID
 * (unique — one payment can't unlock two orders); paid_at / paid_by record who confirmed it and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('type', 20)->default('purchase')->after('order_number');
            $table->foreignId('invitation_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->decimal('amount_received', 10, 2)->nullable()->after('discount_amount');
            $table->string('transaction_ref', 100)->nullable()->unique()->after('amount_received');
            $table->timestamp('paid_at')->nullable()->after('transaction_ref');
            $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            // set once an add-on order's extras were applied to its invitation, so a paid → pending → paid flip can't apply them twice
            $table->timestamp('addons_applied_at')->nullable()->after('paid_by');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('addon_type', 20)->nullable()->after('service_plan_id');
            $table->string('addon_value', 100)->nullable()->after('addon_type');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['addon_type', 'addon_value']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by');
            $table->dropConstrainedForeignId('invitation_id');
            $table->dropUnique(['transaction_ref']);
            $table->dropColumn(['type', 'amount_received', 'transaction_ref', 'paid_at', 'addons_applied_at']);
        });
    }
};
