<?php

namespace App\Http\Requests\Instructor;

use App\Models\Examination;
use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExaminationQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $examination = $this->route('examination');

        return $this->user()?->isInstructor()
            && $examination instanceof Examination
            && $examination->created_by === $this->user()->id
            && $this->user()->subjects()->where('subjects.id', $examination->subject_id)->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_ids' => ['sometimes', 'array'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:questions,id'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $examination = $this->route('examination');

            if (! $examination instanceof Examination) {
                return;
            }

            if ($examination->attempts()->exists()) {
                $validator->errors()->add('question_ids', 'Question assignments cannot be changed after a student attempt has started.');

                return;
            }

            if ($validator->errors()->has('question_ids') || $validator->errors()->has('question_ids.*')) {
                return;
            }

            $questionIds = $this->input('question_ids', []);
            $eligibleCount = Question::query()
                ->eligibleForInstructor($this->user(), $examination)
                ->whereKey($questionIds)
                ->count();

            if ($eligibleCount !== count($questionIds)) {
                $validator->errors()->add('question_ids', 'Only your own, Admin/shared, or legacy/shared questions from this examination subject may be assigned.');
            }
        }];
    }
}
