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
            'owner_id' => $document->owner_id,
            'name' => $document->name,
            'path' => $document->path,
            'expires_at' => $document->expires_at?->timestamp,
            'archived_at' => $document->archived_at?->timestamp,
            'created_at' => $document->created_at?->timestamp,
            'updated_at' => $document->updated_at?->timestamp,
        ];
    }
}
