<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTechnicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lead_user_id' => ['required', 'integer', 'exists:users,id'],
            'member_user_ids' => ['nullable', 'array'],
            'member_user_ids.*' => ['integer', 'exists:users,id', 'different:lead_user_id'],
            'override_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'lead_user_id.required' => 'Koordinator Teknisi Lapangan (Lead PIC) wajib dipilih dari daftar akun teknisi.',
            'lead_user_id.exists' => 'Akun teknisi yang dipilih tidak ditemukan dalam sistem.',
            'member_user_ids.*.exists' => 'Salah satu teknisi pendamping yang dipilih tidak valid.',
            'member_user_ids.*.different' => 'Teknisi pendamping tidak boleh sama dengan Koordinator Teknisi Lapangan.',
            'override_reason.max' => 'Alasan override batas kapasitas maksimal 1000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Mendukung input teknisi_utama_id atau teknisi_utama jika dikirimkan oleh form
        if (!$this->has('lead_user_id') && $this->has('teknisi_utama_id')) {
            $this->merge(['lead_user_id' => $this->input('teknisi_utama_id')]);
        } elseif (!$this->has('lead_user_id') && $this->has('teknisi_utama')) {
            $val = $this->input('teknisi_utama');
            if (is_numeric($val)) {
                $this->merge(['lead_user_id' => (int) $val]);
            }
        }

        if ($this->has('anggota_tim') && !$this->has('member_user_ids')) {
            $members = $this->input('anggota_tim');
            if (!is_array($members)) {
                $members = !empty($members) ? [$members] : [];
            }
            $this->merge(['member_user_ids' => array_map('intval', array_filter($members, 'is_numeric'))]);
        }
    }
}
