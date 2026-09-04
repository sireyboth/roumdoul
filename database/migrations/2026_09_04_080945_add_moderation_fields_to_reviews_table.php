<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('order_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('email')->nullable()->after('customer_name');
            $table->string('source')->default('order')->after('rating');
            $table->boolean('is_approved')->default(true)->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['email', 'source', 'is_approved']);
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
