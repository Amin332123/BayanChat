<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:private,group',
            'name' => 'required_if:type,group|string|max:255|nullable',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id|different:user_id',
        ];
    }

    public function messages(): array
    {
        return [
            'participant_ids.*.different' => 'Cannot add yourself as a participant',
        ];
    }
}
