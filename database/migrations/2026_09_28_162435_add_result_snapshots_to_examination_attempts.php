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
        Schema::table('examination_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('passing_percentage_snapshot')->nullable()->after('submission_reason');
        });
        Schema::table('examination_attempt_question', function (Blueprint $table) {
            $table->text('question_text_snapshot')->nullable()->after('position');
            $table->text('option_a_snapshot')->nullable();
            $table->text('option_b_snapshot')->nullable();
            $table->text('option_c_snapshot')->nullable();
            $table->text('option_d_snapshot')->nullable();
            $table->char('correct_option_snapshot', 1)->nullable();
            $table->text('explanation_snapshot')->nullable();
            $table->decimal('marks_snapshot', 8, 2)->nullable();
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->boolean('allow_answer_review')->default(false)->after('ends_at');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('marks', 8, 2)->default(1)->after('correct_option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('examination_attempts', function (Blueprint $table) {
            $table->dropColumn('passing_percentage_snapshot');
        });
        Schema::table('examination_attempt_question', function (Blueprint $table) {
            $table->dropColumn(['question_text_snapshot', 'option_a_snapshot', 'option_b_snapshot', 'option_c_snapshot', 'option_d_snapshot', 'correct_option_snapshot', 'explanation_snapshot', 'marks_snapshot']);
        });
        Schema::table('examinations', function (Blueprint $table) {
            $table->dropColumn('allow_answer_review');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('marks');
        });
    }
};
