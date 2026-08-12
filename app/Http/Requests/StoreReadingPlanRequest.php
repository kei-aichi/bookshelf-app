<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingPlanRequest extends FormRequest
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
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
                Rule::unique('reading_plans')
                    ->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
            'target_date' => [
                'required',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '書籍の指定が不正です。',
            'book_id.exists' => '選択した書籍が存在しません。',
            'book_id.unique' => 'この書籍は既に読書計画があります。',

            'target_date.required' => '期日を選択してください。',
            'target_date.date' => '正しい日付を入力してください。',
        ];
    }
}
