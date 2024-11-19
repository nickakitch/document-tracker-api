<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        return DocumentResource::collection(
            resource: Document::all()
        );
    }

    public function store(StoreDocumentRequest $request)
    {
        $input = $request->validated();

        $document = $request
            ->user()
            ->documents()
            ->create([
                'name' => $input['name'],
                'expires_at' => $input['expires_at'],
                'path' => $input['file']->store('documents'),
            ]);

        return DocumentResource::make($document);
    }

    public function show(Document $document)
    {
        return DocumentResource::make($document);
    }
}
