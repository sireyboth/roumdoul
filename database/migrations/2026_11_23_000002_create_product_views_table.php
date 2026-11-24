<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who looked at a product (its shop page) or tried its demo — one row per visitor, product, kind and local day,
 * so refreshing doesn't inflate the numbers. `visitor` is a salted hash, never a raw IP (App\Services\ProductViews).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 8); // page | demo
            $table->char('visitor', 64);
            $table->date('viewed_on'); // business-local day
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['service_id', 'kind', 'visitor', 'viewed_on']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
