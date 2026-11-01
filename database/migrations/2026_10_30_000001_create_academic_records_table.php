<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('khs_document_id')->constrained('khs_documents')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->string('periode')->nullable();
            $table->string('course_name');
            $table->unsignedTinyInteger('sks');
            $table->string('grade_letter', 3);
            $table->unsignedTinyInteger('grade_numeric');
            $table->timestamps();
            $table->unique(['user_id', 'course_name'], 'academic_user_course_unique');
            $table->index(['user_id', 'semester']);
        });
    }

    public function down(): void { Schema::dropIfExists('academic_records'); }
};
