<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePart2Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part1_id' => ['required', 'integer'],
            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'midle_name' => ['nullable', 'string', 'max:255'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'age' => ['required', 'integer'],
            'sex_at_birth' => ['required', 'string', 'max:255'],
            'civil_status' => ['required', 'string', 'max:255'],
            'cellular_no' => ['required', 'string', 'max:255'],
            'email_address' => ['required', 'email', 'max:255'],
            'nationality' => ['required', 'string', 'max:255'],
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_no' => ['required', 'integer'],
            'occupation' => ['required', 'string', 'max:255'],
            'name_of_employer' => ['required', 'string', 'max:255'],
            'office_address' => ['required', 'string', 'max:255'],
            'office_no' => ['required', 'integer'],
        ];
    }
}
