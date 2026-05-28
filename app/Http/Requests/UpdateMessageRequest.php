<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->id === $this->route('message')->sender_id;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:5000',
        ];
    }
}
