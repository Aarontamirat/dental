<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Support\ClinicSettings;
use Illuminate\Console\Command;

class PruneInquiries extends Command
{
    protected $signature = 'clinic:prune-inquiries {--execute : Permanently delete matching expired inquiries}';

    protected $description = 'Preview retention cleanup; explicit --execute is required to delete';

    public function handle(): int
    {
        $cutoff = now()->subDays(app(ClinicSettings::class)->get('retention_days', 365));
        $appointments = Appointment::whereIn('status', ['completed', 'cancelled', 'rejected'])->where('updated_at', '<', $cutoff);
        $messages = ContactMessage::where('status', 'resolved')->where('updated_at', '<', $cutoff);
        $this->line('Expired closed appointments: '.$appointments->count());
        $this->line('Expired resolved messages: '.$messages->count());
        if ($this->option('execute')) {
            $appointments->delete();
            $messages->delete();
            $this->info('Expired closed inquiries deleted. Status history was deleted by referential integrity.');
        } else {
            $this->info('Preview only. Nothing deleted. Use --execute after reviewing the counts and backups.');
        }

return self::SUCCESS;
    }
}
