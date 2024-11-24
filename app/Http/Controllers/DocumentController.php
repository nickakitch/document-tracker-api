<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListDocumentsRequest;
use App\Http\Requests\ShowDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(ListDocumentsRequest $request): AnonymousResourceCollection
    {
        $documents = $request->user()->documents()->whereNull('archived_at');

        if ($request->has('expires_before')) {
            $documents->where('expires_at', '<', Carbon::createFromTimestamp($request->input('expires_before')));
        }

        return DocumentResource::collection(
            resource: $documents->get(),
        );
    }

    public function store(StoreDocumentRequest $request): DocumentResource
    {
        // another improvement would be multi-upload support (probably handled on the frontend to send multiple requests to this endpoint)

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

    public function show(Document $document, ShowDocumentRequest $request): StreamedResponse
    {
        // an improvement I'd look at making would be to have a uuid for each document,
        // so that they can't easily be identified as existing by incrementing the id

        return Storage::disk('local')->download($document->path);
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
