<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('viewed_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('reported_at')->nullable()->after('is_approved');
        });

        // Audit trail for moderation. Deliberately stores no comment text, names, emails or IPs —
        // only what happened, to which record, why, and a salted hash for spotting repeats.
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 60);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('reasons')->nullable();
            $table->string('content_hash', 64)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('reported_at');
        });

        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
