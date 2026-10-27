<?php

namespace App\Http\Requests\Admin;

use App\Models\Loan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Tanggal pengembalian default hari ini dan baris yang dikosongkan
     * dianggap sebagai "tidak dikembalikan" (jumlah 0).
     */
    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (is_array($items)) {
            $this->merge([
                'items' => array_map(static function (array $item): array {
                    $quantity = $item['quantity'] ?? 0;

                    return [
                        ...$item,
                        'quantity' => $quantity === '' || $quantity === null ? 0 : $quantity,
                    ];
                }, array_values($items)),
            ]);
        }

        $this->merge([
            'return_date' => $this->filled('return_date')
                ? $this->input('return_date')
                : now()->toDateString(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Tanggal pengembalian wajib, tidak boleh lebih awal dari tanggal
     * peminjaman, dan tidak boleh di masa depan.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $loan = $this->route('peminjaman');
        $loanDate = $loan instanceof Loan ? $loan->loan_date?->toDateString() : null;

        return [
            'return_date' => [
                'required',
                'date',
                'before_or_equal:today',
                ...($loanDate !== null ? ['after_or_equal:'.$loanDate] : []),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.loan_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
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
            'return_date.required' => 'Tanggal pengembalian wajib diisi.',
            'return_date.date' => 'Tanggal pengembalian harus berupa tanggal yang valid.',
            'return_date.before_or_equal' => 'Tanggal pengembalian tidak boleh di masa depan.',
            'return_date.after_or_equal' => 'Tanggal pengembalian tidak boleh lebih awal dari tanggal peminjaman.',
            'items.required' => 'Isi jumlah pengembalian untuk minimal satu judul buku.',
            'items.array' => 'Daftar rincian pengembalian tidak valid.',
            'items.min' => 'Isi jumlah pengembalian untuk minimal satu judul buku.',
            'items.*.loan_item_id.required' => 'Rincian peminjaman tidak valid.',
            'items.*.loan_item_id.integer' => 'Rincian peminjaman tidak valid.',
            'items.*.quantity.required' => 'Jumlah pengembalian wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah pengembalian harus berupa angka bulat.',
            'items.*.quantity.min' => 'Jumlah pengembalian tidak boleh negatif.',
        ];
    }
}
