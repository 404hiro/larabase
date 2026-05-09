<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Link $resource
 */
class LinkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'slug' => $this->resource->slug,
            'display_name' => $this->resource->display_name,
            'title' => $this->resource->title?->only(['id', 'name']),
            'bio' => $this->resource->bio,
            'avatar_url' => $this->resource->avatar_url,
            'is_published' => $this->resource->is_published,
            'widgets_count' => (int) $this->resource->widgets_count,
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
