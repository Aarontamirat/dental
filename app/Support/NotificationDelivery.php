<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;

final class NotificationDelivery
{
    public function available(): bool
    {
        return app(ClinicSettings::class)->get('email_enabled') && ! in_array(config('mail.default'), ['log', 'array'], true) && filled(config('mail.from.address'));
    }

    public function appointment(Appointment $appointment, bool $statusChange = false): void
    {
        if (! $this->available()) {
            $appointment->update(['notification_status' => 'disabled']);

            return;
        }
        $recipient = $statusChange ? $appointment->email : ($appointment->branch?->appointment_email ?: app(ClinicSettings::class)->get('notification_email'));
        if (! $recipient) {
            $appointment->update(['notification_status' => 'disabled']);

            return;
        }
        $body = $statusChange
            ? "Your request {$appointment->reference} has status: {$appointment->status}. Preferred visit: {$appointment->preferred_date->format('Y-m-d')} at {$appointment->preferred_time} (Africa/Addis_Ababa). Please contact the clinic with questions."
            : "A new appointment request {$appointment->reference} is waiting in your clinic dashboard. Sign in to review it. This is a request, not a confirmed booking.";
        $this->send($appointment, $recipient, 'Appointment '.$appointment->reference, $body);
    }

    public function contact(ContactMessage $message): void
    {
        $recipient = app(ClinicSettings::class)->get('notification_email');
        if (! $this->available() || ! $recipient) {
            return;
        }
        $this->send($message, $recipient, 'New website inquiry', 'A new contact message is waiting in your clinic dashboard. Sign in to review and respond.');
    }

    private function send($record, string $recipient, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($m) => $m->to($recipient)->subject($subject));
            $record->update(['notification_status' => 'sent']);
        } catch (\Throwable) {
            $record->update(['notification_status' => 'failed']);
        }
    }
}
