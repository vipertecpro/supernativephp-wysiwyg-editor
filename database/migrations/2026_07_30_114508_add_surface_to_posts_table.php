<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which demo a post belongs to.
 *
 * The three feeds shared one table, so a post written on a colour in the
 * Facebook demo turned up in LinkedIn as plain text and in X clipped to a
 * short post. Each is meant to be its own app, and an app does not show
 * another one's posts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Existing rows predate the split; X is where they were written.
            $table->string('surface')->default('x')->after('id');
            $table->index(['surface', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['surface', 'created_at']);
            $table->dropColumn('surface');
        });
    }
};
