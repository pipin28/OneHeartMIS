<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part1_id' => ['required', 'integer'],
            'part2_id' => ['required', 'integer'],
            'complete_address' => ['required', 'string', 'max:1000'],
            'contact_no' => ['required', 'string', 'max:255'],
            'religion' => ['required', 'string', 'max:255'],
            'occupation_livelihood' => ['required', 'string', 'max:255'],
            'valid_id' => ['required', 'string', 'max:255'],
            'valid_id_no' => ['required', 'string', 'max:255'],
        ];
    }
}
