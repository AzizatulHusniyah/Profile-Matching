<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('spk_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('gap_results');
            $table->json('weighted_gap_results');
            $table->json('factor_results');
            $table->json('ranking_results');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('spk_results'); }
};
