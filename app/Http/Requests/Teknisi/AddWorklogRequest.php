<?php

namespace App\Http\Requests\Teknisi;

use Illuminate\Foundation\Http\FormRequest;

class AddWorklogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string', 'min:5', 'max:3000'],
            'berkas' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,txt,log', 'max:10240'], // Max 10MB
            'row_version' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan progres lapangan wajib diisi.',
            'catatan.min' => 'Catatan progres minimal 5 karakter.',
            'berkas.mimes' => 'Format berkas hanya boleh berupa JPG, PNG, PDF, TXT, atau LOG.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
        ];
    }
}
