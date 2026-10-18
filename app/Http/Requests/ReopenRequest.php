<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReopenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:5', 'max:1000'],
            'bukti_reopen' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // WAJIB ada berkas bukti
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pembukaan kembali tiket wajib diisi.',
            'alasan.min' => 'Alasan pembukaan tiket minimal 5 karakter.',
            'bukti_reopen.required' => 'Berkas bukti pendukung pembukaan kembali tiket WAJIB diunggah (tidak boleh kosong).',
            'bukti_reopen.file' => 'Bukti pendukung harus berupa file yang valid.',
            'bukti_reopen.mimes' => 'Format file bukti reopen hanya diperbolehkan: JPG, PNG, atau PDF.',
            'bukti_reopen.max' => 'Ukuran file bukti reopen maksimal 5 MB.',
        ];
    }
}
