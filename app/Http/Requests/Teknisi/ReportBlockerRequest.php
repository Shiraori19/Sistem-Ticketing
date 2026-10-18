<?php

namespace App\Http\Requests\Teknisi;

use Illuminate\Foundation\Http\FormRequest;

class ReportBlockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isTeknisi();
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', 'in:isp,vendor,opd_lain,suku_cadang'],
            'bukti' => ['required', 'string', 'max:500'],
            'keterangan' => ['required', 'string', 'min:10', 'max:3000'],
            'berkas_bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,txt,log', 'max:10240'],
            'row_version' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'jenis.required' => 'Jenis hambatan wajib dipilih.',
            'jenis.in' => 'Jenis hambatan harus salah satu dari: ISP, Vendor, OPD Lain, atau Suku Cadang.',
            'bukti.required' => 'Bukti nomor tiket vendor/ISP atau referensi hambatan wajib diisi.',
            'keterangan.required' => 'Rincian penjelasan hambatan wajib diisi.',
            'keterangan.min' => 'Keterangan hambatan minimal 10 karakter.',
            'berkas_bukti.mimes' => 'Format berkas hanya boleh berupa JPG, PNG, PDF, TXT, atau LOG.',
            'berkas_bukti.max' => 'Ukuran berkas maksimal 10 MB.',
        ];
    }
}
