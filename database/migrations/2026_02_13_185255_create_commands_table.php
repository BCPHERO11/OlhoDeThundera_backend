<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 191)->unique();
            $table->string('source', 50)->index();
            $table->unsignedSmallInteger('type');
            $table->json('payload');
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->dateTime('processed_at')->nullable()->index();
            $table->string('error', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
