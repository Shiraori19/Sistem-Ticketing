<?php

namespace App\Http\Requests\Teknisi;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'availability' => ['required', 'in:tersedia,sibuk,cuti'],
            'cuti_until' => ['nullable', 'date', 'required_if:availability,cuti', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'availability.required' => 'Status ketersediaan wajib dipilih.',
            'availability.in' => 'Status ketersediaan harus tersedia, sibuk, atau cuti.',
            'cuti_until.required_if' => 'Tanggal akhir cuti wajib diisi jika memilih status Cuti.',
            'cuti_until.after_or_equal' => 'Tanggal akhir cuti tidak boleh di masa lalu.',
        ];
    }
}
