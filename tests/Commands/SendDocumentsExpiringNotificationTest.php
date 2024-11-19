<?php

namespace Tests\Commands;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentsExpiringNotification;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendDocumentsExpiringNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_the_command_is_executed_then_a_notification_is_sent_to_the_users_with_documents_expiring_in_7_days(): void
    {
        Notification::fake();

        $userWithoutDocumentsExpiring = User::factory()->create();
        $userWithDocumentsExpiring = User::factory()->create();

        Document::factory()
            ->create(['owner_id' => $userWithoutDocumentsExpiring->id, 'expires_at' => now()->addDays(8)]);

        $documentExpiring = Document::factory()
            ->create(['owner_id' => $userWithDocumentsExpiring->id, 'expires_at' => now()->addDays(6)]);

        $this
            ->artisan('app:send-documents-expiring-notification')
            ->assertSuccessful();

        Notification::assertSentTo(
            $userWithDocumentsExpiring,
            DocumentsExpiringNotification::class,
            function (DocumentsExpiringNotification $notification, $channels) use ($documentExpiring) {
                return $notification->documents->contains($documentExpiring);
            }
        );

        Notification::assertNotSentTo($userWithoutDocumentsExpiring, DocumentsExpiringNotification::class);
    }

    public function test_archived_documents_are_not_included_in_the_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $documentExpiring = Document::factory()
            ->create(['owner_id' => $user->id, 'expires_at' => now()->addDays(6)]);

        $documentArchived = Document::factory()
            ->create(['owner_id' => $user->id, 'expires_at' => now()->addDays(6), 'archived_at' => now()]);

        $this
            ->artisan('app:send-documents-expiring-notification')
            ->assertSuccessful();

        Notification::assertSentTo(
            $user,
            DocumentsExpiringNotification::class,
            function (DocumentsExpiringNotification $notification, $channels) use ($documentExpiring) {
                return $notification->documents->contains($documentExpiring);
            }
        );

        Notification::assertNotSentTo(
            $user,
            DocumentsExpiringNotification::class,
            function (DocumentsExpiringNotification $notification, $channels) use ($documentArchived) {
                return $notification->documents->contains($documentArchived);
            });
    }

    public function test_the_command_is_in_the_task_scheduler_for_every_morning_at_9am(): void
    {
        /** @var Schedule $schedule */
        $schedule = app()->make(Schedule::class);

        $events = collect($schedule->events())->filter(function (Event $event) {
            return stripos($event->command, 'app:send-documents-expiring-notification');
        });

        if ($events->count() == 0) {
            $this->fail('No events found');
        }

        $events->each(fn (Event $event) => $this->assertEquals('0 9 * * *', $event->expression));
    }
}
