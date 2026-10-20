<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanUrlRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Persiapkan dan bersihkan input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $url = trim((string) $this->input('url'));

        // Jika user mengetik tanpa skema (misal: google.com), tambahkan https://
        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $this->merge(['url' => $url]);
    }

    /**
     * Aturan validasi untuk masukan URL yang akan dipindai.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:2048',
                'regex:/^https?:\/\/\S+$/i',
            ],
        ];
    }

    /**
     * Pesan kustom validasi dalam bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'url.required' => 'URL website wajib diisi.',
            'url.max' => 'Panjang URL melebihi batas maksimum 2048 karakter.',
            'url.regex' => 'Format URL tidak valid. Pastikan diawali dengan http:// atau https://',
        ];
    }
}
