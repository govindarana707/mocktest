<?php

namespace App\Http\Requests\Instructor;

use App\Http\Requests\Admin\StoreExaminationRequest as AdminStoreExaminationRequest;
use Illuminate\Validation\Validator;

class StoreExaminationRequest extends AdminStoreExaminationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isInstructor() ?? false;
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
                $validator->errors()->add('subject_id', 'You may only create or move examinations within your assigned subjects.');
            }
        }];
    }
}
