<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('occurrence_id')->index();
            $table->unsignedTinyInteger('status')
                ->default(0)
                ->index();
            $table->string('resource_code', 50);
            $table->timestamps();

            $table->foreign('occurrence_id')
                ->references('id')
                ->on('occurrences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
