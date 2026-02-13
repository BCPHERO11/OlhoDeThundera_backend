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
        Schema::create('occurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('external_id')->unique()->nullable($value = false);
            $table->unsignedTinyInteger('type')->index();
            $table->unsignedTinyInteger('status')
                ->default(0)
                ->index();
            $table->string('description');
            $table->dateTime('reported_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};
