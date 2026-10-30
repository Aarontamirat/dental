<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Support\ClinicSettings;
use App\Support\NotificationDelivery;
use Illuminate\Support\Facades\Mail;
use Tests\ClinicTestCase;

class AppointmentAdministrationTest extends ClinicTestCase
{
    private function appointment(): Appointment
    {
        $this->post('/appointments', $this->requestData())->assertRedirect();

        return Appointment::firstOrFail();
    }

    private function changes(Appointment $a, array $override = []): array
    {
        return array_replace(['status' => 'confirmed', 'preferred_date' => $a->preferred_date->format('Y-m-d'), 'preferred_time' => $a->preferred_time, 'expected_version' => $a->version, 'internal_notes' => 'Contacted patient by phone.'], $override);
    }

    public function test_confirming_a_request_records_history_and_internal_notes(): void
    {
        $a = $this->appointment();
        $user = $this->user('appointment_manager');
        $this->actingAs($user);
        $this->putJson('/admin/api/appointments/'.$a->id, $this->changes($a))->assertOk();
        $a->refresh();
        $this->assertSame('confirmed', $a->status);
        $this->assertSame(1, $a->version);
        $this->assertSame(2, $a->history()->count());
        $this->assertDatabaseHas('appointment_status_history', ['user_id' => $user->id, 'from_status' => 'pending', 'to_status' => 'confirmed']);
        $this->get('/appointments')->assertDontSee('Contacted patient by phone.');
    }

    public function test_invalid_transitions_and_stale_updates_are_rejected(): void
    {
        $a = $this->appointment();
        $this->actingAs($this->user());
        $this->putJson('/admin/api/appointments/'.$a->id, $this->changes($a, ['status' => 'completed']))->assertUnprocessable()->assertJsonValidationErrors('status');
        $payload = $this->changes($a);
        $this->putJson('/admin/api/appointments/'.$a->id, $payload)->assertOk();
        $this->putJson('/admin/api/appointments/'.$a->id, $payload)->assertConflict();
    }

    public function test_rescheduling_requires_a_new_future_visit_and_explanation(): void
    {
        $a = $this->appointment();
        $this->actingAs($this->user());
        $this->putJson('/admin/api/appointments/'.$a->id, $this->changes($a, ['status' => 'rescheduled']))->assertUnprocessable()->assertJsonValidationErrors('note');
        $this->putJson('/admin/api/appointments/'.$a->id, $this->changes($a, ['status' => 'rescheduled', 'preferred_date' => $a->preferred_date->addWeek()->format('Y-m-d'), 'note' => 'Patient requested next week.']))->assertOk();
        $a->refresh();
        $this->assertSame('rescheduled', $a->status);
        $this->putJson('/admin/api/appointments/'.$a->id, $this->changes($a, ['status' => 'rescheduled', 'internal_notes' => 'Follow-up noted.']))->assertOk();
    }

    public function test_status_email_is_sent_only_when_transport_is_configured(): void
    {
        $a = $this->appointment();
        app(ClinicSettings::class)->update(['email_enabled' => true]);
        config(['mail.default' => 'smtp']);
        Mail::fake();
        app(NotificationDelivery::class)->appointment($a, true);
        $this->assertSame('sent', $a->fresh()->notification_status);
    }

    public function test_delivery_failure_does_not_lose_an_appointment(): void
    {
        $a = $this->appointment();
        app(ClinicSettings::class)->update(['email_enabled' => true, 'notification_email' => 'clinic@example.test']);
        config(['mail.default' => 'smtp']);
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Delivery failed'));
        app(NotificationDelivery::class)->appointment($a);
        $this->assertSame('failed', $a->fresh()->notification_status);
        $this->assertDatabaseCount('appointments',1);
    }
}
