<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBeneficiariesRequest extends FormRequest
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
            'par2_residential_address_id' => ['nullable', 'integer'],
            'type' => ['required', 'array', 'min:1'],
            'type.*' => ['required', 'string', 'max:255'],
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'age' => ['required', 'array', 'min:1'],
            'age.*' => ['required', 'integer'],
            'address' => ['required', 'array', 'min:1'],
            'address.*' => ['required', 'string', 'max:255'],
            'relationship_to_planholder' => ['required', 'array', 'min:1'],
            'relationship_to_planholder.*' => ['required', 'string', 'max:255'],
        ];
    }
}
