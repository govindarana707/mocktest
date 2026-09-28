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
        Schema::create('examination_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_attempt_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedInteger('total_questions');
            $table->unsignedInteger('answered_questions');
            $table->unsignedInteger('correct_answers');
            $table->unsignedInteger('incorrect_answers');
            $table->unsignedInteger('unanswered_questions');
            $table->decimal('maximum_marks', 10, 2);
            $table->decimal('obtained_marks', 10, 2);
            $table->decimal('percentage', 5, 2);
            $table->unsignedTinyInteger('passing_percentage');
            $table->boolean('passed');
            $table->timestamp('graded_at');
            $table->timestamps();
            $table->index(['passed', 'graded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examination_results');
    }
};
