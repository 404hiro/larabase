<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->link->user_id;

        return [
            'id' => $this->id,
            'sender_user_id' => $isOwner ? $this->sender_user_id : null,
            'body' => $this->body,
            'amount' => $this->amount,
            'sender_mode' => $this->sender_mode,
            'sender_display_name' => $this->sender_display_name,
            'is_public' => $this->is_public,
            'is_read' => $this->is_read,
            'published_at' => $this->published_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'status' => $this->status,
            'reply_body' => $this->publication?->reply_body,
            'can_manage' => $isOwner,
        ];
    }
}
