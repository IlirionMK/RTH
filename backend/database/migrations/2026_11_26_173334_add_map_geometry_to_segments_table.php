<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->json('map_geometry')->nullable();
        });

        DB::table('segments')->update(['map_geometry' => DB::raw('geometry')]);

        Schema::table('segments', function (Blueprint $table) {
            $table->json('map_geometry')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->dropColumn('map_geometry');
        });
    }
};
