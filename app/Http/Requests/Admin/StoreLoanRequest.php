<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Tanggal peminjaman default hari ini dan kolom opsional dikosongkan
     * menjadi null sebelum divalidasi (FR-LN-04).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'loan_date' => $this->filled('loan_date')
                ? $this->input('loan_date')
                : now()->toDateString(),
            'due_date' => $this->filled('due_date') ? $this->input('due_date') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
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
            'borrower_id' => ['required', 'integer', 'exists:borrowers,id'],
            'loan_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer', 'distinct', 'exists:books,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
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
            'borrower_id.required' => 'Peminjam wajib dipilih.',
            'borrower_id.integer' => 'Peminjam tidak valid.',
            'borrower_id.exists' => 'Peminjam yang dipilih tidak ditemukan.',
            'loan_date.required' => 'Tanggal peminjaman wajib diisi.',
            'loan_date.date' => 'Tanggal peminjaman harus berupa tanggal yang valid.',
            'due_date.date' => 'Tanggal jatuh tempo harus berupa tanggal yang valid.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo tidak boleh lebih awal dari tanggal peminjaman.',
            'notes.string' => 'Catatan harus berupa teks.',
            'notes.max' => 'Catatan maksimal :max karakter.',
            'items.required' => 'Minimal satu buku harus dipilih.',
            'items.array' => 'Daftar buku tidak valid.',
            'items.min' => 'Minimal satu buku harus dipilih.',
            'items.*.book_id.required' => 'Buku wajib dipilih pada setiap baris.',
            'items.*.book_id.integer' => 'Buku yang dipilih tidak valid.',
            'items.*.book_id.distinct' => 'Buku tidak boleh dipilih lebih dari sekali dalam satu transaksi.',
            'items.*.book_id.exists' => 'Buku yang dipilih tidak ditemukan.',
            'items.*.quantity.required' => 'Jumlah eksemplar wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah eksemplar harus berupa angka bulat.',
            'items.*.quantity.min' => 'Jumlah eksemplar minimal 1.',
        ];
    }
}
