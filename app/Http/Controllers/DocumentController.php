<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $document = $request
            ->user()
            ->documents()
            ->create(
                $request->all()
            );

        return DocumentResource::make($document);
    }

    public function show(Document $document)
    {
        return DocumentResource::make($document);
    }
}
