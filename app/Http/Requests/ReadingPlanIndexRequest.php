<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingPlanIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'integer',
                Rule::enum(ReadingPlanStatus::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.integer' => '読書状態の指定が不正です。',
            'status.enum' => '指定された読書状態は存在しません。',
        ];
    }
}
