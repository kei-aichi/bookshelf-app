<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
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
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'url', 'max:255'],
            'genre_ids' => ['required', 'array', 'min:1'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => '書籍タイトルを入力してください。',
            'title.string' => '書籍タイトルは文字列で入力してください。',
            'title.max' => '書籍タイトルは255文字以内で入力してください。',

            'author.required' => '著者名を入力してください。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.required' => 'ISBNを入力してください。',
            'isbn.digits' => 'ISBNは半角数字13桁で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',

            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '出版日は日付形式で入力してください。',

            'description.string' => '書籍の説明は文字列で入力してください。',
            'description.max' => '書籍の説明は500文字以内で入力してください。',

            'image_url.string' => '画像URLは文字列で入力してください。',
            'image_url.url' => '画像URLは正しいURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',

            'genre_ids.required' => 'ジャンルを選択してください。',
            'genre_ids.array' => 'ジャンルは配列で指定してください。',
            'genre_ids.min' => 'ジャンルを1件以上選択してください。',
            'genre_ids.*.integer' => 'ジャンルIDは整数で指定してください。',
            'genre_ids.*.exists' => '指定されたジャンルは存在しません。',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => '指定されたパラメーターが不正です。',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
