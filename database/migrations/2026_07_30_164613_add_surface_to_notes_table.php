<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which demo a note belongs to.
 *
 * The Notes demo and the Apple Notes demo share this table and are meant to be
 * two apps — exactly the split the three feeds needed. A note written in one
 * was turning up in the other's folder list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            // Existing rows predate the split; the plain Notes demo is where
            // they were written.
            $table->string('surface')->default('notes')->after('id');
            $table->index(['surface', 'folder']);
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['surface', 'folder']);
            $table->dropColumn('surface');
        });
    }
};
