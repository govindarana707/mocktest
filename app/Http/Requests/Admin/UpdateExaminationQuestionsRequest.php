<?php

namespace App\Http\Requests\Admin;

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
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['required', 'integer', 'distinct', 'exists:questions,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('question_ids')) {
                return;
            }

            $examination = $this->route('examination');
            $count = Question::query()
                ->whereKey($this->input('question_ids', []))
                ->where('subject_id', $examination->subject_id)
                ->count();

            if ($count !== count($this->input('question_ids', []))) {
                $validator->errors()->add('question_ids', 'Only questions from this examination subject may be assigned.');
            }
        }];
    }
}
