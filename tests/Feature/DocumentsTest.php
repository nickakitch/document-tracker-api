<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_a_request_is_made_to_view_a_list_of_documents_then_the_documents_are_returned(): void
    {
        $user = User::factory()
            ->has(Document::factory()->count(10))
            ->create();

        $this
            ->actingAs($user)
            ->getJson(route('api.documents.index'))
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'path',
                        'ownerId',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ])
            ->assertSuccessful();
    }

    public function test_when_a_request_includes_a_filter_to_filter_by_expiry_date_then_only_documents_that_expire_before_the_date_are_returned(): void
    {
        $user = User::factory()
            ->has(Document::factory([
                'expires_at' => now()->addDay(),
            ])->count(10))
            ->create();

        Document::factory()->count(10)->create([
            'owner_id' => $user->id,
            'expires_at' => now()->addMonth(),
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('api.documents.index', ['expires_before' => now()->addWeek()->timestamp]))
            ->assertJsonCount(10, 'data')
            ->assertSuccessful();
    }

    public function test_a_user_can_only_view_a_list_of_their_own_documents(): void
    {
        $user = User::factory()
            ->has(Document::factory())
            ->create();

        $anotherUser = User::factory()
            ->has(Document::factory())
            ->create();

        $this
            ->actingAs($user)
            ->getJson(route('api.documents.index'))
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'data' => [
                    '*' => [
                        'ownerId' => $anotherUser->id,
                    ],
                ],
            ])
            ->assertSuccessful();
    }

    public function test_when_a_request_is_made_to_view_a_single_document_then_the_document_is_returned(): void
    {
        $user = User::factory()->create();

        $document = Document::factory()->for($user, 'owner')->create();

        $this
            ->actingAs($user)
            ->getJson(route('api.documents.show', ['document' => $document->id]))
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'path',
                    'ownerId',
                    'createdAt',
                    'updatedAt',
                ],
            ]);
    }

    public function test_given_a_user_does_not_own_a_document_when_a_request_is_made_to_view_a_single_document_then_a_forbidden_response_is_returned()
    {
        $user = User::factory()->create();

        $document = Document::factory()->create();

        $this
            ->actingAs($user)
            ->getJson(route('api.documents.show', ['document' => $document->id]))
            ->assertForbidden();
    }

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
                ]
            )->assertCreated();

        $this->assertDatabaseHas((new Document())->getTable(), [
            'name' => 'Contract',
            'path' => 'documents/' . $file->hashName(),
            'owner_id' => $uploadingUser->id,
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

    public static function storeValidationProvider(): array
    {
        return [
            'name is required' => [
                ['name' => ''],
                ['name' => ['The name field is required.']],
            ],
            'name must be a string' => [
                ['name' => ['not a string']],
                ['name' => ['The name field must be a string.']],
            ],
            'name must be at most 255 characters' => [
                ['name' => str_repeat('a', 256)],
                ['name' => ['The name field must not be greater than 255 characters.']],
            ],
            'file is required' => [
                ['file' => ''],
                ['file' => ['The file field is required.']],
            ],
            'file must be a file' => [
                ['file' => 'not a file'],
                ['file' => ['The file field must be a file.']],
            ],
            'file must be a pdf' => [
                ['file' => UploadedFile::fake()->create('document.txt')],
                ['file' => ['The file field must be a file of type: pdf.']],
            ],
            'file must be at most 10MB' => [
                ['file' => UploadedFile::fake()->create('document.pdf', 10241)],
                ['file' => ['The file field must not be greater than 10240 kilobytes.']],
            ],
            'expires_at must be a date' => [
                ['expires_at' => 'not a date'],
                ['expires_at' => ['The expires at field must be a valid date.']],
            ],
            'expires_at must be after a week from now' => [
                ['expires_at' => now()->subWeek()],
                ['expires_at' => ['The expires at field must be a date after ' . now()->addWeek()->startOfDay()->toDateTimeString() . '.']],
            ],
            'expires_at must be before 5 years from now' => [
                ['expires_at' => now()->addYears(5)->addDay()],
                ['expires_at' => ['The expires at field must be a date before ' . now()->addYears(5)->endOfDay()->toDateTimeString() . '.']],
            ],
        ];
    }

    /**
     * @dataProvider storeValidationProvider
     */
    public function test_store_validation(array $requestData, array $expectedErrors): void
    {
        $uploadingUser = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this
            ->actingAs($uploadingUser)
            ->postJson(route('api.documents.store'), $requestData)
            ->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);

        $this->assertDatabaseMissing((new Document())->getTable(), [
            'path' => 'documents/' . $file->hashName(),
        ], (new Document())->getConnectionName());

        Storage::disk('local')->assertMissing('documents/' . $file->hashName());
    }

    public function test_when_a_request_is_made_to_archive_a_document_then_the_document_is_archived(): void
    {
        $user = User::factory()->create();

        $document = Document::factory()->for($user, 'owner')->create();

        $this
            ->actingAs($user)
            ->putJson(
                route('api.documents.update', ['document' => $document->id]),
                ['archived_at' => now()->timestamp]
            )
            ->assertSuccessful();

        $this
            ->assertDatabaseHas(
                (new Document())->getTable(),
                [
                    'id' => $document->id,
                    'archived_at' => now()->toDateTimeString(),
                ],
                (new Document())->getConnectionName()
            );
    }

    public function test_given_a_user_doesnt_own_a_document_when_a_request_is_made_to_archive_a_document_then_a_forbidden_response_is_returned(): void
    {
        $user = User::factory()->create();

        $document = Document::factory()->create();

        $this
            ->actingAs($user)
            ->putJson(
                route('api.documents.update', ['document' => $document->id]),
                ['archived_at' => now()->subWeek()->timestamp]
            )
            ->assertForbidden();

        $this
            ->assertDatabaseHas(
                (new Document())->getTable(),
                [
                    'id' => $document->id,
                    'archived_at' => null,
                ],
                (new Document())->getConnectionName()
            );
    }

    public function test_given_a_user_is_not_authenticated_when_a_request_is_made_to_archive_a_document_then_an_unauthorised_response_is_returned(): void
    {
        $document = Document::factory()->create();

        $this->assertGuest();

        $this
            ->putJson(
                route('api.documents.update', ['document' => $document->id]),
                ['archived_at' => now()->subWeek()->timestamp]
            )
            ->assertUnauthorized();

        $this
            ->assertDatabaseHas(
                (new Document())->getTable(),
                [
                    'id' => $document->id,
                    'archived_at' => null,
                ],
                (new Document())->getConnectionName()
            );
    }

    public static function updateValidationProvider(): array
    {
        return [
            'archived_at must be an integer' => [
                ['archived_at' => 'not an integer'],
                ['archived_at' => ['The archived at field must be an integer.']],
            ],
            'archived_at must be at least 0' => [
                ['archived_at' => -1],
                ['archived_at' => ['The archived at field must be at least 0.']],
            ],
            'archived_at must be at most the current timestamp' => [
                ['archived_at' => 1732063223],
                ['archived_at' => ['The archived at field must not be greater than 1609459200.']],
            ],
        ];
    }

    /**
     * @dataProvider updateValidationProvider
     */
    public function test_update_validation(array $requestData, array $expectedErrors): void
    {
        $this->travelTo(Carbon::parse('2021-01-01 00:00:00'));

        $user = User::factory()->create();

        $document = Document::factory()
            ->for($user, 'owner')
            ->create(['archived_at' => now()->subWeek()]);

        $this
            ->actingAs($user)
            ->putJson(
                route('api.documents.update', ['document' => $document->id]),
                $requestData
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);

        $this
            ->assertDatabaseHas(
                (new Document())->getTable(),
                [
                    'id' => $document->id,
                    'archived_at' => now()->subWeek()->toDateTimeString(),
                ],
                (new Document())->getConnectionName()
            );
    }
}
