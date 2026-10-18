<?php

namespace App\Http\Requests\Teknisi;

use Illuminate\Foundation\Http\FormRequest;

class DeclineAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
            'row_version' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan penolakan penugasan wajib diisi.',
            'alasan.min' => 'Alasan penolakan minimal 10 karakter.',
        ];
    }
}
