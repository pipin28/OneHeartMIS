<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part1_id' => ['required', 'integer', 'exists:part1s,id'],
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'midle_name' => ['nullable', 'string', 'max:255'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->subYears(60)->toDateString()],
            'age' => ['required', 'integer', 'min:60'],
            'sex_at_birth' => ['required', 'string', 'max:255'],
            'cellular_no' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'The member age must be not below 60 years old.',
            'age.min' => 'The member age must be not below 60 years old.',
        ];
    }
}
