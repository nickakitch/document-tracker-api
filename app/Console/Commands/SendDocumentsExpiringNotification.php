<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentsExpiringNotification;
use Illuminate\Console\Command;

class SendDocumentsExpiringNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-documents-expiring-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to users with documents expiring within 7 days';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Document::query()
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->whereNull('archived_at')
            ->get()
            ->groupBy('owner_id')
            ->each(function ($documents, $ownerId) {
                $user = User::findOrFail($ownerId);
                $user->notify(new DocumentsExpiringNotification($documents));
            });
    }
}
