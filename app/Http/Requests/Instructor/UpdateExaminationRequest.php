<?php

namespace App\Http\Requests\Instructor;

use App\Http\Requests\Admin\UpdateExaminationRequest as AdminUpdateExaminationRequest;
use App\Models\Examination;
use Illuminate\Validation\Validator;

class UpdateExaminationRequest extends AdminUpdateExaminationRequest
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

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->has('subject_id')) {
                    return;
                }

                $isAssigned = $this->user()?->subjects()
                    ->where('subjects.id', $this->integer('subject_id'))
                    ->exists() ?? false;

                if (! $isAssigned) {
                    $validator->errors()->add('subject_id', 'You may only create or move examinations within your assigned subjects.');
                }
            },
        ];
    }
}
