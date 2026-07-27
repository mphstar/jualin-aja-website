<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StatusEbook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbahStatusEbookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusEbook::class)],
        ];
    }

    public function status(): StatusEbook
    {
        return StatusEbook::from((string) $this->string('status'));
    }
}
