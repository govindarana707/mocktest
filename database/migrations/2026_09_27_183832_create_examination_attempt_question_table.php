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
        if (! Schema::hasTable('examination_attempt_question')) {
            Schema::create('examination_attempt_question', function (Blueprint $table) {
                $table->foreignId('examination_attempt_id')->constrained()->cascadeOnDelete();
                $table->foreignId('question_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('position');

                $table->primary(['examination_attempt_id', 'question_id']);
            });
        }

        Schema::table('examination_attempt_question', function (Blueprint $table) {
            $table->unique(['examination_attempt_id', 'position'], 'attempt_question_position_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('examination_attempt_question');
    }
};
