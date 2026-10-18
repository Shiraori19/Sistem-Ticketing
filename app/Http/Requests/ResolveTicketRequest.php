<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bukti_resolusi' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // Maks 5MB (Opsional)
            'catatan_resolusi' => ['required', 'string', 'min:5'],
            'is_confidential_evidence' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bukti_resolusi.file' => 'Berkas bukti penyelesaian harus berupa file yang valid.',
            'bukti_resolusi.mimes' => 'Format berkas bukti resolusi hanya diperbolehkan: JPG, PNG, atau PDF.',
            'bukti_resolusi.max' => 'Ukuran berkas bukti resolusi maksimal 5 MB.',
            'catatan_resolusi.required' => 'Catatan teknis resolusi / penyelesaian pekerjaan wajib diisi.',
            'catatan_resolusi.min' => 'Catatan resolusi minimal berisi 5 karakter.',
        ];
    }
}
