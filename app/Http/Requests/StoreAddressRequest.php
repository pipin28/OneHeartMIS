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
            'lot_house_numer' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'barangay' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'max:255'],
            'sss_gsis_no' => ['required', 'string', 'max:255'],
            'tin_no' => ['required', 'string', 'max:255'],
            'source_of_funds_if_not_imployed' => ['required', 'string', 'max:255'],
        ];
    }
}
