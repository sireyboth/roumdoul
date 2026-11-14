<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products a promo code is limited to. No rows for a code = it applies to the whole order.
     */
    public function up(): void
    {
        Schema::create('promo_code_service', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['promo_code_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_service');
    }
};
