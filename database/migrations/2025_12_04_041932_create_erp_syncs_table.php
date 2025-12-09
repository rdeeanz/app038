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
        Schema::create('erp_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // products, orders, inventory, etc.
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('endpoint')->nullable();
            $table->text('params')->nullable(); // JSON string
            $table->text('result')->nullable(); // JSON string
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_syncs');
    }
};
