<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNipBkn implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nip = preg_replace('/[^0-9]/', '', (string) $value);

        // 1. Wajib tepat 18 digit angka
        if (strlen($nip) !== 18) {
            $fail('NIP pelapor wajib berjumlah tepat 18 digit angka sesuai standar BKN.');
            return;
        }

        // 2. Digit 1-8: Tanggal Lahir (YYYYMMDD)
        $tglLahirStr = substr($nip, 0, 8);
        $thnLahir = (int) substr($nip, 0, 4);
        $blnLahir = (int) substr($nip, 4, 2);
        $tglLahir = (int) substr($nip, 6, 2);

        if (!checkdate($blnLahir, $tglLahir, $thnLahir)) {
            $fail('Format tanggal lahir pada NIP tidak valid (digit 1-8 bukan tanggal kalender sah).');
            return;
        }

        $birthDate = Carbon::createFromDate($thnLahir, $blnLahir, $tglLahir)->startOfDay();
        $now = Carbon::now();

        if ($birthDate->isFuture()) {
            $fail('Tanggal lahir pada NIP tidak boleh berada di masa depan.');
            return;
        }

        $umur = $birthDate->diffInYears($now);
        if ($umur < 18 || $umur > 70) {
            $fail('Rentang usia ASN pada NIP tidak valid (usia aktif ASN minimal 18 tahun dan maksimal 70 tahun).');
            return;
        }

        // 3. Digit 9-14: Tahun dan Bulan Pengangkatan CPNS (YYYYMM)
        $thnCpns = (int) substr($nip, 8, 4);
        $blnCpns = (int) substr($nip, 12, 2);

        if ($blnCpns < 1 || $blnCpns > 12) {
            $fail('Bulan pengangkatan CPNS pada NIP tidak valid (digit 13-14 harus bernilai 01-12).');
            return;
        }

        if ($thnCpns < $thnLahir + 17) {
            $fail('Tahun pengangkatan CPNS tidak valid (pengangkatan tidak mungkin sebelum usia 17 tahun).');
            return;
        }

        if ($thnCpns > (int) $now->format('Y') + 1) {
            $fail('Tahun pengangkatan CPNS pada NIP melampaui tahun berjalan.');
            return;
        }

        // 4. Digit 15: Jenis Kelamin (1 = Laki-laki, 2 = Perempuan)
        $gender = substr($nip, 14, 1);
        if (!in_array($gender, ['1', '2'], true)) {
            $fail('Digit kode jenis kelamin pada NIP tidak valid (digit ke-15 harus berupa angka 1 untuk pria atau 2 untuk wanita).');
            return;
        }

        // 5. Digit 16-18: Nomor Urut (001-999)
        $nomorUrut = (int) substr($nip, 15, 3);
        if ($nomorUrut < 1) {
            $fail('Nomor urut kelulusan pada NIP tidak valid (digit 16-18 harus lebih besar dari 000).');
            return;
        }
    }
}
