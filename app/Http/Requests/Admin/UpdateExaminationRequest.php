<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateExaminationRequest extends FormRequest
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
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'passing_percentage' => ['required', 'integer', 'between:1,100'],
            'status' => ['required', 'in:draft,published'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $examination = $this->route('examination');

            if ($this->input('status') === 'published' && $examination->questions()->doesntExist()) {
                $validator->errors()->add('status', 'Assign at least one question before publishing this examination.');
            }

            if ($this->integer('subject_id') !== $examination->subject_id && $examination->questions()->exists()) {
                $validator->errors()->add('subject_id', 'Remove the assigned questions before changing the examination subject.');
            }
        }];
    }
}
