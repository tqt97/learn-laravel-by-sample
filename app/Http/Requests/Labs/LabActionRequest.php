<?php

namespace App\Http\Requests\Labs;

use Illuminate\Foundation\Http\FormRequest;

final class LabActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_key' => ['nullable', 'string', 'max:100'],
            'delay_microseconds' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'run_mode' => ['nullable', 'string', 'in:single,batch_race'],
            'count' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
