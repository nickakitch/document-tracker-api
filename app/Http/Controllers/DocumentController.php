<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return DocumentResource::collection(
            resource: Document::all()
        );
    }

    public function store(StoreDocumentRequest $request): DocumentResource
    {
        $input = $request->validated();

        $document = $request
            ->user()
            ->documents()
            ->create([
                'name' => $input['name'],
                'expires_at' => $input['expires_at'] ?? null,
                'path' => $input['file']->store('documents'),
            ]);

        return DocumentResource::make($document);
    }

    public function show(Document $document, ShowDocumentRequest $request): DocumentResource
    {
        return DocumentResource::make($document);
    }

    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $input = $request->validated();

        $document->update([
            'archived_at' => $input['archived_at'] ? Carbon::createFromTimestamp($input['archived_at']) : null,
        ]);

        return DocumentResource::make($document);
    }
}
