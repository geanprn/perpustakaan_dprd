<?php

namespace App\Http\Requests\Admin;

use App\Models\Borrower;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBorrowerRequest extends FormRequest
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
            'institution' => $this->filled('institution') ? $this->input('institution') : null,
            'phone' => $this->filled('phone') ? $this->input('phone') : null,
            'address' => $this->filled('address') ? $this->input('address') : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $borrower = $this->route('peminjam');

        $borrowerId = $borrower instanceof Borrower ? $borrower->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'identity_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('borrowers', 'identity_number')->ignore($borrowerId),
            ],
            'institution' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
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
            'name.required' => 'Nama peminjam wajib diisi.',
            'name.string' => 'Nama peminjam harus berupa teks.',
            'name.max' => 'Nama peminjam maksimal :max karakter.',
            'identity_number.required' => 'Nomor identitas wajib diisi.',
            'identity_number.string' => 'Nomor identitas harus berupa teks.',
            'identity_number.max' => 'Nomor identitas maksimal :max karakter.',
            'identity_number.unique' => 'Nomor identitas sudah terdaftar.',
            'institution.string' => 'Instansi/unit harus berupa teks.',
            'institution.max' => 'Instansi/unit maksimal :max karakter.',
            'phone.string' => 'Nomor telepon harus berupa teks.',
            'phone.max' => 'Nomor telepon maksimal :max karakter.',
            'address.string' => 'Alamat harus berupa teks.',
            'address.max' => 'Alamat maksimal :max karakter.',
        ];
    }
}
