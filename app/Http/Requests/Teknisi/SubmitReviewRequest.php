<?php

namespace App\Http\Requests\Teknisi;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'catatan' => ['required', 'string', 'min:10', 'max:5000'],
            'bukti_foto' => ['nullable', 'file', 'max:10240', new \App\Rules\ValidSecureFile(['image', 'pdf', 'text'])],
            'row_version' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Catatan tindakan penyelesaian kendala wajib diisi.',
            'catatan.min' => 'Catatan penyelesaian minimal 10 karakter.',
            'bukti_foto.mimes' => 'Format berkas bukti hanya boleh berupa JPG, PNG, PDF, TXT, atau LOG.',
            'bukti_foto.max' => 'Ukuran berkas bukti maksimal 10 MB.',
        ];
    }
}
