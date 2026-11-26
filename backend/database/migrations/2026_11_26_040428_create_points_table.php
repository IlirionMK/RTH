<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index(['trip_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
