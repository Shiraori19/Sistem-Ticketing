<?php

namespace App\Http\Requests\Teknisi;

use Illuminate\Foundation\Http\FormRequest;

class AcceptAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'row_version' => ['nullable', 'integer'],
        ];
    }
}
