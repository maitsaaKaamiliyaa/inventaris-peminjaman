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
        Schema::table('kodes', function (Blueprint $table) {
            $table->integer('jumlah_rusak')->default(0)->after('jumlah');
            $table->integer('jumlah_dipinjam')->default(0)->after('jumlah_rusak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kodes', function (Blueprint $table) {
            //
        });
    }
};
