<?php

namespace App\Http\Requests\Instructor;

use App\Models\Question;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $question = $this->route('question');

        return parent::authorize()
            && $question instanceof Question
            && $question->created_by === $this->user()->id
            && $this->user()->subjects()->where('subjects.id', $question->subject_id)->exists();
    }
}
