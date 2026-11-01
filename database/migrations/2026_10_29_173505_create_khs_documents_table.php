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
        Schema::create('khs_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('semester');

            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_hash', 64);

            $table->string('status')->default('uploaded');
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'semester'],
                'khs_user_semester_unique'
            );

            $table->unique(
                ['user_id', 'file_hash'],
                'khs_user_file_hash_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khs_documents');
    }
};
