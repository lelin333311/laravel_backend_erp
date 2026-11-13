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
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('type', ['in', 'out']);
            $table->string('party_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('cash');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
};
