<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Random URL handle for every post: the calendar deep-links to
 * /posts/{uid} instead of exposing the auto-increment id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('uid', 32)->nullable()->unique();
        });

        foreach (DB::table('posts')->whereNull('uid')->pluck('id') as $id) {
            DB::table('posts')->where('id', $id)->update(['uid' => Str::random(16)]);
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
