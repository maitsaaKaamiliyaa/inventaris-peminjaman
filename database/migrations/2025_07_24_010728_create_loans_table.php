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
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('item_id')->references('id')->on('items')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('jumlah')->default(0);
            $table->date('loan_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'returned', 'rejected'])
                ->default('pending')
                ->comment('pending: waiting for approval, approved: loan approved, returned: item returned, rejected: loan rejected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
