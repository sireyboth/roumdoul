<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Optional short tag on a plan card in the shop, e.g. «Best value». */
    public function up(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->string('badge', 40)->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('service_plans', function (Blueprint $table) {
            $table->dropColumn('badge');
        });
    }
};
