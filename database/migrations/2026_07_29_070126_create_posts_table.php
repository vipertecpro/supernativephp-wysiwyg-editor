<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_handle');
            // The editor's normalised HTML is the source of truth; the plain
            // rendition it delivers alongside is what the timeline shows.
            $table->text('body_html');
            $table->text('body_text');
            // The canonical form — media and poll options only survive here.
            $table->text('body_json')->nullable();
            $table->unsignedInteger('replies')->default(0);
            $table->unsignedInteger('reposts')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
