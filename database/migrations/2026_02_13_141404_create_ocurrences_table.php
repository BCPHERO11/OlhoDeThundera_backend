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

            // Primary UUID
            $table->uuid('id')->primary();

            // External reference UUID (unique)
            $table->uuid('external_id')->unique();

            // Type of occurrence
            $table->string('type', 100)->index();

            /*
             * Status Enum (stored as int)
             * 0 = reported
             * 1 = in_progress
             * 2 = resolved
             * 3 = cancelled
             */
            $table->unsignedTinyInteger('status')
                ->default(0)
                ->index();

            // Description
            $table->string('description');

            // When it was reported
            $table->dateTime('reported_at')->index();

            // Laravel timestamps (created_at, updated_at)
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
