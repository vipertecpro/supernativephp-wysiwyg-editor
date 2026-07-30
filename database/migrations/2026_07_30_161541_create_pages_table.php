<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A page in the Notion demo.
 *
 * Kept apart from `notes` because a page is a different thing: it carries an
 * icon, and its title is the first line of the document rather than a column
 * somebody typed separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            // The emoji standing in for a page, as Notion does it.
            $table->string('icon')->default('📄');
            // The editor's normalised HTML is the source of truth to render;
            // the JSON is what re-opens loss-free. See ContentSaved.
            $table->text('body_html');
            $table->text('body_text');
            $table->text('body_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
