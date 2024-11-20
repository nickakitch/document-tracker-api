<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Document $document */
        $document = $this->resource;

        return [
            'id' => $document->id,
            'ownerId' => $document->owner_id,
            'name' => $document->name,
            'path' => $document->path,
            'expiresAt' => $document->expires_at?->timestamp,
            'archivedAt' => $document->archived_at?->timestamp,
            'createdAt' => $document->created_at?->timestamp,
            'updatedAt' => $document->updated_at?->timestamp,
        ];
    }
}
