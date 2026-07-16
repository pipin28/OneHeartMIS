<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePart1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_assignment_id' => ['required', 'integer', 'exists:member_assignments,id'],
            'application_date' => ['required', 'date'],
            'approved_date' => ['required', 'date'],
            'mode_of_payment' => ['required', 'string', 'max:255'],
        ];
    }
}
