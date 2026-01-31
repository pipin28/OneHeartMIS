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
            'lpaf_no' => ['required', 'integer'],
            'application_date' => ['required', 'date'],
            'sales_counselor_code' => ['required', 'string', 'max:255'],
            'plan_type' => ['required', 'string', 'max:255'],
            'gross_contact_price' => ['required', 'integer'],
            'mode_of_payment' => ['required', 'string', 'max:255'],
            'terms_of_payment' => ['required', 'string', 'max:255'],
            'due_date' => ['required_unless:plan_type,Legacy Care', 'date', 'nullable'],
            'amount' => ['required', 'integer'],
        ];
    }
}
