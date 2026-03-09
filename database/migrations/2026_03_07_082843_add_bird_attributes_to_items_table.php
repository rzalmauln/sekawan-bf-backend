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
        Schema::table('items', function (Blueprint $table) {
            $table->string('gaya_main')->nullable();
            $table->string('body')->nullable();
            $table->string('umur')->nullable();
            $table->string('materi')->nullable();
            $table->string('volume')->nullable();
            $table->string('panjang_ekor')->nullable();
            $table->string('warna')->nullable();
            $table->string('warna_kaki')->nullable();
            $table->string('paruh')->nullable();
            $table->string('jenis_kepala')->nullable();

            $table->string('voer')->nullable();
            $table->string('extra_fooding')->nullable();
            $table->string('embun')->nullable();
            $table->string('jemur')->nullable();
            $table->string('mandi')->nullable();
            $table->string('tenggar')->nullable();
            $table->string('krodong_ablak')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'gaya_main',
                'body',
                'umur',
                'materi',
                'volume',
                'panjang_ekor',
                'warna',
                'warna_kaki',
                'paruh',
                'jenis_kepala',
                'voer',
                'extra_fooding',
                'embun',
                'jemur',
                'mandi',
                'tenggar',
                'krodong_ablak'
            ]);
        });
    }
};
