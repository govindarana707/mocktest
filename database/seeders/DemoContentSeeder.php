<?php

namespace Database\Seeders;

use App\ExaminationStatus;
use App\Models\Examination;
use App\Models\ExaminationCategory;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Demo content cannot be seeded in production.');

        $mathematics = Subject::updateOrCreate(
            ['code' => 'MATH-101'],
            ['name' => 'Mathematics', 'description' => 'Foundational numerical reasoning and algebra.'],
        );
        $science = Subject::updateOrCreate(
            ['code' => 'SCI-101'],
            ['name' => 'Science', 'description' => 'Core scientific concepts and observation.'],
        );

        $categories = collect([
            ['name' => 'Academic', 'slug' => 'academic', 'description' => 'Academic subject practice examinations.'],
            ['name' => 'Entrance Preparation', 'slug' => 'entrance-preparation', 'description' => 'Preparation for competitive entrance examinations.'],
            ['name' => 'Practice Test', 'slug' => 'practice-test', 'description' => 'Short, focused practice examinations.'],
            ['name' => 'Scholarship', 'slug' => 'scholarship', 'description' => 'Scholarship examination preparation.'],
            ['name' => 'General Knowledge', 'slug' => 'general-knowledge', 'description' => 'General knowledge practice examinations.'],
        ])->mapWithKeys(fn (array $category) => [$category['slug'] => ExaminationCategory::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true])]);

        $demoInstructor = User::query()
            ->where('email', 'instructor@mocktest.test')
            ->where('role', UserRole::Instructor)
            ->first();

        $demoInstructor?->subjects()->sync([$mathematics->id, $science->id]);

        $questions = collect([
            [$mathematics, 'What is 12 × 8?', '96', '84', '88', '108', 'a', 'Multiply 12 by 8 to get 96.'],
            [$mathematics, 'Which value is equal to 3² + 4²?', '7', '12', '25', '49', 'c', 'Three squared plus four squared is 9 + 16 = 25.'],
            [$science, 'Which gas do plants absorb during photosynthesis?', 'Oxygen', 'Carbon dioxide', 'Nitrogen', 'Hydrogen', 'b', 'Plants use carbon dioxide to make glucose.'],
            [$science, 'What is the center of an atom called?', 'Electron', 'Orbit', 'Nucleus', 'Molecule', 'c', 'Protons and neutrons are found in the nucleus.'],
        ])->map(function (array $data): Question {
            [$subject, $questionText, $optionA, $optionB, $optionC, $optionD, $correctOption, $explanation] = $data;

            return Question::updateOrCreate(
                ['subject_id' => $subject->id, 'question_text' => $questionText],
                [
                    'option_a' => $optionA,
                    'option_b' => $optionB,
                    'option_c' => $optionC,
                    'option_d' => $optionD,
                    'correct_option' => $correctOption,
                    'explanation' => $explanation,
                ],
            );
        });

        $mathematicsExam = Examination::updateOrCreate(
            ['subject_id' => $mathematics->id, 'title' => 'Mathematics Foundations'],
            [
                'description' => 'A short practice catalog examination for foundational mathematics.',
                'duration_minutes' => 30,
                'passing_percentage' => 60,
                'status' => ExaminationStatus::Published,
                'starts_at' => now()->subDay(),
                'ends_at' => null,
                'category_id' => $categories['academic']->id,
            ],
        );

        $mathematicsExam->questions()->sync(
            $questions->where('subject_id', $mathematics->id)->values()->mapWithKeys(
                fn (Question $question, int $index) => [$question->id => ['position' => $index + 1]],
            )->all(),
        );

        $demoQuestion = $demoInstructor?->questions()->updateOrCreate(
            ['subject_id' => $science->id, 'question_text' => 'Which planet is known as the Red Planet?'],
            [
                'option_a' => 'Earth',
                'option_b' => 'Mars',
                'option_c' => 'Jupiter',
                'option_d' => 'Venus',
                'correct_option' => 'b',
                'explanation' => 'Iron minerals on the surface give Mars its reddish appearance.',
            ],
        );

        $demoExamination = $demoInstructor?->examinations()->updateOrCreate(
            ['subject_id' => $science->id, 'title' => 'Instructor Science Practice'],
            [
                'description' => 'An instructor-owned draft with deterministic practice content.',
                'duration_minutes' => 25,
                'passing_percentage' => 60,
                'status' => ExaminationStatus::Draft,
                'starts_at' => null,
                'ends_at' => null,
                'allow_answer_review' => false,
                'category_id' => $categories['practice-test']->id,
            ],
        );

        if ($demoQuestion && $demoExamination && $demoExamination->questions()->whereKey($demoQuestion->id)->doesntExist()) {
            $nextPosition = ((int) $demoExamination->questions()->max('examination_question.position')) + 1;
            $demoExamination->questions()->attach($demoQuestion, ['position' => $nextPosition]);
        }
    }
}
