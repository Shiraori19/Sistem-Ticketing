<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware handles role checking
    }

    public function rules(): array
    {
        return [
            'tlp_level' => ['required', 'in:RED,AMBER,GREEN,CLEAR'],
            'bidang_id' => ['required_unless:tlp_level,RED', 'nullable', 'exists:bidang,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_parent' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'exists:tickets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tlp_level.required' => 'Tingkat kerahasiaan TLP (Traffic Light Protocol) wajib ditentukan.',
            'tlp_level.in' => 'TLP Level harus salah satu dari: RED, AMBER, GREEN, atau CLEAR.',
            'bidang_id.required_unless' => 'Bidang penanggung jawab wajib dipilih, kecuali untuk tingkat kerahasiaan TLP RED.',
            'bidang_id.exists' => 'Bidang tujuan disposisi tidak ditemukan.',
        ];
    }
}
