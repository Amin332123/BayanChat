<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');
        return $conversation && $conversation->participants()->where('user_id', $this->user()->id)->exists();
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:5000',
            'parent_id' => 'nullable|integer|exists:messages,id',
        ];
    }
}
