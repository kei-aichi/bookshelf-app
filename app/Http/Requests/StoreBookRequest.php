<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'string',
                'regex:/^[0-9]+$/',
                'size:13',
                'unique:books,isbn',
            ],
            'published_date' => ['required', 'date'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '書籍タイトルを入力してください。',

            'author.required' => '著者を入力してください。',

            'isbn.required' => 'ISBNを入力してください。',
            'isbn.regex' => 'ISBNは半角数字で入力してください。',
            'isbn.size' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => 'このISBNは既に登録されています。',

            'published_date.required' => '出版日を入力してください。',

            'image_url.url' => '画像URLは正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',

            'description.max' => '説明は500文字以内で入力してください。',

            'genres.required' => 'ジャンルは必ず1つ以上にチェックを入れてください。',
        ];
    }
}
