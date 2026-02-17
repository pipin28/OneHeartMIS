<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignment_id' => ['nullable', 'integer', 'exists:member_assignments,id'],
            'collector_user_id' => ['required', 'integer', 'exists:users,id'],
            'agent_user_id' => ['required', 'integer', 'exists:users,id'],
            'manager_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
