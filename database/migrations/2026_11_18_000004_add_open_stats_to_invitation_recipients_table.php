<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guest stats: viewed_at stays the FIRST time a guest opened their link; open_count counts visits
 * (a refresh within 30 minutes doesn't count again) and last_viewed_at is the latest one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->unsignedInteger('open_count')->default(0)->after('viewed_at');
            $table->timestamp('last_viewed_at')->nullable()->after('open_count');
        });

        DB::table('invitation_recipients')->whereNotNull('viewed_at')->update([
            'open_count' => 1,
            'last_viewed_at' => DB::raw('viewed_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->dropColumn(['open_count', 'last_viewed_at']);
        });
    }
};
