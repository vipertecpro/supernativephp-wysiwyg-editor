<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the Apple Notes demo adds to a note: a folder and a pin.
 *
 * Both are the APP's business — the editor knows nothing about either, and
 * should not. It hands back a document; where that document lives and whether
 * it sits at the top of a list is filing, not editing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->string('folder')->default('Notes')->after('id');
            $table->boolean('pinned')->default(false)->after('folder');
            $table->index(['folder', 'pinned']);
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['folder', 'pinned']);
            $table->dropColumn(['folder', 'pinned']);
        });
    }
};
