<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Premium (★) becomes an admin choice per template instead of a list in code:
 *  - invitation_templates.premium_fields: the fields of this design a plan must include (★).
 *    Filled with today's ★ list (the catalog's premium fields this template ticks), so nothing changes.
 *  - invitations.free_fields: the fields that were free when the invitation was paid. Filled with today's
 *    free list, so a field the admin later marks ★ never disappears from an invitation already sold.
 */
return new class extends Migration
{
    /** The ★ list as it was in code before this migration (InvitationTemplate::FIELD_CATALOG minus FREE_FIELDS). */
    private const PREMIUM_BEFORE = [
        'venue_address', 'map_url', 'khmer_date', 'rsvp_enabled', 'countdown_enabled', 'event_schedule',
        'story_chapters', 'qr_code', 'cta_label', 'cta_url',
    ];

    /** InvitationTemplate::FREE_FIELDS as it was in code before this migration. */
    private const FREE_BEFORE = [
        'sender_name', 'groom_name', 'bride_name', 'celebrant_name', 'turning_age', 'headline', 'message',
        'cover_image', 'event_date', 'venue_name', 'music_url', 'accent_color', 'groom_photo', 'bride_photo',
        'groom_phone', 'bride_phone', 'groom_father', 'groom_mother', 'bride_father', 'bride_mother',
        'thanks_message', 'apology_message', 'reception_message', 'gallery_feed', 'gift_account', 'contact_phone',
        'host_names', 'title_image', 'opening_line', 'gift_message', 'rsvp_message', 'background_image',
        'music_file', 'couple_initials', 'end_time', 'rsvp_deadline', 'dress_code', 'what_to_bring',
        'love_reasons', 'photo_captions', 'song_title', 'closing_line', 'together_since', 'photo_gallery',
    ];

    public function up(): void
    {
        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->json('premium_fields')->nullable()->after('fields');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $table->json('free_fields')->nullable()->after('features');
        });

        DB::table('invitation_templates')->orderBy('id')->each(function ($template) {
            $fields = json_decode($template->fields ?? '[]', true) ?: [];
            DB::table('invitation_templates')->where('id', $template->id)->update([
                'premium_fields' => json_encode(array_values(array_intersect(self::PREMIUM_BEFORE, $fields))),
            ]);
        });

        DB::table('invitations')->whereNull('free_fields')->update([
            'free_fields' => json_encode(self::FREE_BEFORE),
        ]);
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('free_fields');
        });

        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->dropColumn('premium_fields');
        });
    }
};
