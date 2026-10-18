<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('skpd') === 'LAINNYA' && $this->filled('skpd_custom')) {
            $this->merge([
                'skpd' => trim((string) $this->input('skpd_custom')),
            ]);
        }

        // Penanganan otomatis jika pelapor mengetik kategori baru yang belum terdaftar di sistem
        if ($this->filled('custom_category_name')) {
            $customCat = trim((string) $this->input('custom_category_name'));
            if (!$this->filled('category_id')) {
                $fallback = \App\Models\Category::where('nama_kategori', 'like', '%Lainnya%')
                    ->orWhere('nama_kategori', 'like', '%Belum Tahu%')
                    ->first() ?? \App\Models\Category::first();
                if ($fallback) {
                    $this->merge(['category_id' => $fallback->id]);
                }
            }
            $currentDesc = (string) $this->input('deskripsi');
            if (!str_contains($currentDesc, '[Kategori Diajukan:')) {
                $this->merge([
                    'deskripsi' => "[Kategori Diajukan Pelapor: {$customCat}]\n\n" . $currentDesc,
                ]);
            }
        }

        $this->merge([
            'nip_pelapor' => preg_replace('/[^0-9]/', '', (string) $this->input('nip_pelapor')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nip_pelapor' => ['required', 'string', 'size:18', 'regex:/^[0-9]{18}$/', new \App\Rules\ValidNipBkn()],
            'nama_pelapor' => ['required', 'string', 'max:150'],
            'email_pelapor' => ['nullable', 'string', 'email', 'max:150'],
            'skpd' => ['required', 'string', 'max:150', 'not_in:LAINNYA'],
            'skpd_custom' => ['nullable', 'string', 'max:150'],
            'no_whatsapp' => ['required', 'string', 'regex:/^(08|628|\+628)[0-9]{8,13}$/'],
            'category_id' => ['required', 'exists:categories,id'],
            'custom_category_name' => ['nullable', 'string', 'max:150'],
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'lampiran_pelapor' => [
                'nullable',
                'array',
                'max:5',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        $totalBytes = 0;
                        foreach ($value as $file) {
                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                $totalBytes += $file->getSize();
                            }
                        }
                        // Total akumulasi seluruh berkas maksimal 10 MB (10.485.760 bytes)
                        if ($totalBytes > 10 * 1024 * 1024) {
                            $totalMb = round($totalBytes / (1024 * 1024), 2);
                            $fail("Total ukuran seluruh berkas lampiran ({$totalMb} MB) melebihi batas maksimal akumulasi 10 MB.");
                        }
                    }
                },
            ],
            'lampiran_pelapor.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // Maks 5MB per berkas
            'g-recaptcha-response' => [
                app()->environment('testing') || app()->runningUnitTests() ? 'nullable' : 'required',
                function ($attribute, $value, $fail) {
                    if (app()->environment('testing') || app()->runningUnitTests()) {
                        return;
                    }

                    $secret = config('services.recaptcha.secret_key');
                    // Google official test key always passes on local
                    if (empty($secret) || $secret === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe') {
                        if (empty($value)) {
                            $fail('Harap selesaikan verifikasi reCAPTCHA terlebih dahulu.');
                        }
                        return;
                    }

                    try {
                        $response = \Illuminate\Support\Facades\Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                            'secret' => $secret,
                            'response' => $value,
                            'remoteip' => request()->ip(),
                        ]);

                        if (!$response->successful() || !$response->json('success')) {
                            $fail('Verifikasi keamanan reCAPTCHA tidak valid. Silakan coba lagi.');
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('reCAPTCHA verification error: ' . $e->getMessage());
                        if (!app()->environment('local')) {
                            $fail('Gagal memverifikasi reCAPTCHA dengan server Google.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nip_pelapor.required' => 'NIP pelapor wajib diisi.',
            'nip_pelapor.size' => 'NIP pelapor wajib tepat berjumlah 18 digit angka tanpa spasi atau simbol.',
            'nip_pelapor.regex' => 'NIP pelapor hanya boleh memuat digit angka 0-9.',
            'nama_pelapor.required' => 'Nama lengkap pelapor wajib diisi.',
            'skpd.required' => 'Asal OPD / SKPD wajib dipilih atau diisi.',
            'skpd.not_in' => 'Silakan tuliskan nama OPD / Instansi Anda pada kolom isian yang disediakan jika memilih opsi Lainnya.',
            'no_whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'no_whatsapp.regex' => 'Format nomor WhatsApp tidak valid. Gunakan format diawali 08xxx.',
            'category_id.required' => 'Kategori layanan wajib dipilih.',
            'category_id.exists' => 'Kategori layanan yang dipilih tidak valid.',
            'judul.required' => 'Judul permohonan / laporan kendala wajib diisi.',
            'deskripsi.required' => 'Deskripsi detail kendala / kebutuhan layanan wajib diisi.',
            'lampiran_pelapor.array' => 'Berkas lampiran harus berupa daftar file yang valid.',
            'lampiran_pelapor.max' => 'Maksimal 5 berkas lampiran yang dapat diunggah.',
            'lampiran_pelapor.*.file' => 'Berkas lampiran harus berupa file yang valid.',
            'lampiran_pelapor.*.mimes' => 'Format berkas lampiran hanya diperbolehkan: JPG, JPEG, PNG, atau PDF.',
            'lampiran_pelapor.*.max' => 'Ukuran setiap berkas lampiran maksimal adalah 5 MB.',
            'g-recaptcha-response.required' => 'Harap centang verifikasi keamanan "Saya bukan robot" (reCAPTCHA) sebelum mengirim tiket.',
        ];
    }
}
