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
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('party_code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['customer', 'supplier', 'both'])->default('customer');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->enum('balance_type', ['debit', 'credit'])->default('debit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};