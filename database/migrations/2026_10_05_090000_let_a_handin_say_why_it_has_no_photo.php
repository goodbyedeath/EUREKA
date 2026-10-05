<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A hand-in with no photo, and the reason it has none.
 *
 * `verification_photo` is required on submit, and the Finish pane has no way back into the room once the
 * clock runs out. The client reported the consequence (report #45): a handset with no working camera, or
 * one whose camera permission is refused while the team is at a booth with the clock already gone, cannot
 * hand in at all — and everything the team answered is lost. Losing the answers is worse than losing the
 * photograph.
 *
 * So the photo stays required unless the client says why it could not be taken. The reason is stored, not
 * inferred, and the admin sees it beside the attempt: an exception that is loud is one the crew can
 * challenge on the spot, which is what keeps it from becoming the normal path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('photo_missing_reason', 32)->nullable()->after('photo_captured_at');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('photo_missing_reason');
        });
    }
};
