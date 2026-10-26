<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Ubah kolom opsional yang dikirim string kosong menjadi null.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'publisher' => $this->filled('publisher') ? $this->input('publisher') : null,
            'publication_year' => $this->filled('publication_year') ? $this->input('publication_year') : null,
            'shelf_location' => $this->filled('shelf_location') ? $this->input('shelf_location') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:'.date('Y')],
            'shelf_location' => ['nullable', 'string', 'max:255'],
            'total_copies' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Judul buku wajib diisi.',
            'title.string' => 'Judul buku harus berupa teks.',
            'title.max' => 'Judul buku maksimal :max karakter.',
            'author.required' => 'Pengarang wajib diisi.',
            'author.string' => 'Pengarang harus berupa teks.',
            'author.max' => 'Pengarang maksimal :max karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.integer' => 'Kategori tidak valid.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'publisher.string' => 'Penerbit harus berupa teks.',
            'publisher.max' => 'Penerbit maksimal :max karakter.',
            'publication_year.integer' => 'Tahun terbit harus berupa angka.',
            'publication_year.min' => 'Tahun terbit minimal tahun :min.',
            'publication_year.max' => 'Tahun terbit tidak boleh melebihi tahun :max.',
            'shelf_location.string' => 'Lokasi rak harus berupa teks.',
            'shelf_location.max' => 'Lokasi rak maksimal :max karakter.',
            'total_copies.required' => 'Jumlah eksemplar wajib diisi.',
            'total_copies.integer' => 'Jumlah eksemplar harus berupa angka bulat.',
            'total_copies.min' => 'Jumlah eksemplar minimal :min.',
        ];
    }
}
