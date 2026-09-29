<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isInstructor() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'question_text' => ['required', 'string', 'max:5000'],
            'option_a' => ['required', 'string', 'max:1000'],
            'option_b' => ['required', 'string', 'max:1000'],
            'option_c' => ['required', 'string', 'max:1000'],
            'option_d' => ['required', 'string', 'max:1000'],
            'correct_option' => ['required', 'in:a,b,c,d'],
            'explanation' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('subject_id')) {
                return;
            }

            $isAssigned = $this->user()?->subjects()
                ->where('subjects.id', $this->integer('subject_id'))
                ->exists() ?? false;

            if (! $isAssigned) {
                $validator->errors()->add('subject_id', 'You may only create or move questions within your assigned subjects.');
            }
        }];
    }
}
