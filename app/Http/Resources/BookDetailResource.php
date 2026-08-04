<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'image_url' => $this->image_url,
            'description' => $this->description,
            'genres' => $this->genres->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                ];
            }),
            'reviews' => $this->reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user->name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
}
