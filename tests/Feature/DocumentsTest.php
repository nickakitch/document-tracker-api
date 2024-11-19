<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function testItCanListDocuments()
    {
        $user = User::factory()
            ->has(Document::factory()->count(10))
            ->create();

        $this->actingAs($user);

        $this->getJson('/api/documents')
            ->assertJsonCount(10, 'data')
            ->assertSuccessful();
    }

    public function testItCanListADocument()
    {
        $user = User::factory()
            ->create();

        $document = Document::factory()
            ->for($user, 'owner')
            ->create();

        $this->actingAs($user);

        $this->getJson("/api/documents/{$document->id}")
            ->assertSuccessful();
    }

    //    public function testOnlyDocumentOwnersCanViewDocument()
    //    {
    //        $user = User::factory()
    //            ->create();
    //
    //        $user2 = User::factory()
    //            ->create();
    //
    //        $document = Document::factory()
    //            ->for($user2, 'owner')
    //            ->create();
    //
    //        $this->actingAs($user);
    //
    //        $this->get("/api/documents/{$document->id}")
    //            ->assertForbidden();
    //    }

    public function test_when_a_request_is_made_to_store_a_document_then_the_file_is_saved_in_the_database(): void
    {
        $uploadingUser = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $expiry = now()->addWeek();

        $this
            ->actingAs($uploadingUser)
            ->postJson(
                route('api.documents.store'),
                [
                    'name' => 'Contract',
                    'file' => $file,
                    'expires_at' => $expiry,
                ]
            )->assertCreated();

        $this->assertDatabaseHas((new Document())->getTable(), [
            'name' => 'Contract',
            'path' => 'documents/' . $file->hashName(),
            'owner_id' => $uploadingUser->id,
            'expires_at' => $expiry,
        ], (new Document())->getConnectionName());

        Storage::disk('local')->assertExists('documents/' . $file->hashName());
    }

    public function test_given_a_user_is_not_authenticated_when_they_request_to_store_a_document_then_an_unauthorised_response_is_returned(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $expiry = now()->addWeek();

        $this->assertGuest();

        $this
            ->postJson(
                route('api.documents.store'),
                [
                    'name' => 'Contract',
                    'file' => $file,
                    'expires_at' => $expiry,
                ]
            )->assertUnauthorized();
    }

    public function test_validation(): void
    {
        $this->markTestIncomplete();
    }

    //    public function testItCanNotStoreADocumentWithExpiryInThePast()
    //    {
    //        $user = User::factory()
    //            ->create();
    //
    //        $this->actingAs($user);
    //
    //        $this->postJson('/api/documents', [
    //            'name' => 'Contract',
    //            'expires_at' => now()->subWeek()
    //        ])->assertInvalid();
    //    }
}
