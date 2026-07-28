<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class UbahIsiTransaksiRequest extends FormRequest
{
    use PunyaItemKeranjang;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->aturanItem();
    }
}
