<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookIndexRequest extends FormRequest
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
            'keyword' => [
                'nullable',
                'string',
                'max:255',
            ],
            'genre' => [
                'nullable',
                'integer',
                'exists:genres,id',
            ],
            'sort' => [
                'nullable',
                'string',
                Rule::in([
                    'newest',
                    'oldest',
                    'title',
                    'rating',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'keyword.max' => 'キーワードは255文字以内で入力してください。',
            'genre.integer' => 'ジャンルの指定が不正です。',
            'genre.exists' => '指定されたジャンルは存在しません。',
            'sort.string' => '並び替え条件の指定が不正です。',
            'sort.in' => '指定された並び替え条件は使用できません。',
        ];
    }
}
