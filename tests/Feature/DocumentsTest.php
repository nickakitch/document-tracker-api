<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function testItCanStoreADocument()
    {
        $user = User::factory()
            ->create();

        $this->actingAs($user);

        $this->postJson('/api/documents', [
            'name' => 'Contract',
            'expires_at' => now()->addWeek(),
        ])->assertSuccessful();
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
