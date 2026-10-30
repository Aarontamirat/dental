<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Support\ClinicSettings;
use Tests\ClinicTestCase;

class PatientWorkflowTest extends ClinicTestCase
{
    public function test_appointment_submission_stores_a_pending_request_and_reference(): void
    {
        $this->post('/appointments', $this->requestData())->assertRedirect('/appointments')->assertSessionHas('appointment_reference');
        $record = Appointment::firstOrFail();
        $this->assertSame('pending', $record->status);
        $this->assertSame('+251912345678', $record->phone);
        $this->assertSame('disabled', $record->notification_status);
        $this->assertTrue($record->consent);
        $this->assertNotEmpty($record->consent_text);
        $this->assertMatchesRegularExpression('/^SD-[A-Z0-9]{10}$/', $record->reference);
        $this->assertSame('pending', $record->history()->first()->to_status);
    }

    public function test_validation_rejects_past_dates_absent_consent_and_spam(): void
    {
        $base = $this->requestData();
        $this->post('/appointments', [...$base, 'preferred_date' => now()->subDay()->format('Y-m-d'), 'consent' => false, 'website' => 'spam'])->assertSessionHasErrors(['preferred_date', 'consent', 'website']);
        $this->post('/appointments', [...$base, 'form_token' => 'tampered'])->assertSessionHasErrors('form_token');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_requests_cannot_select_services_outside_the_branch_or_closed_times(): void
    {
        $base = $this->requestData();
        $service = Service::create(['title' => 'Unassigned service', 'slug' => 'unassigned', 'status' => 'published']);
        $this->post('/appointments', [...$base, 'service_id' => $service->id])->assertSessionHasErrors('service_id');
        $this->post('/appointments', [...$base, 'preferred_time' => '23:00'])->assertSessionHasErrors('preferred_time');
        $this->post('/appointments', [...$base, 'preferred_date' => now()->next('Sunday')->format('Y-m-d')])->assertSessionHasErrors('preferred_time');
    }

    public function test_inactive_services_are_rejected_and_disabled_booking_refuses_submissions(): void
    {
        $base = $this->requestData();
        Service::whereKey($base['service_id'])->update(['status' => 'draft']);
        $this->post('/appointments', $base)->assertSessionHasErrors('service_id');
        app(ClinicSettings::class)->update(['appointment_enabled' => false]);
        $this->post('/appointments', $base)->assertForbidden();
    }

    public function test_contact_submission_and_administration_are_database_backed(): void
    {
        $this->post('/contact', ['name' => 'Test Sender', 'email' => 'sender@example.test', 'subject' => 'A question', 'message' => 'I would like more information.', 'consent' => true, 'form_token' => $this->requestData()['form_token']])->assertRedirect('/contact')->assertSessionHas('contact_success');
        $message = ContactMessage::firstOrFail();
        $this->assertSame('new', $message->status);
        $this->actingAs($this->user('appointment_manager'));
        $this->getJson('/admin/api/messages/'.$message->id)->assertOk();
        $this->putJson('/admin/api/messages/'.$message->id, ['status' => 'resolved', 'internal_notes' => 'Replied by phone.'])->assertOk();
        $this->assertSame('resolved', $message->fresh()->status);
    }

    public function test_contact_validation_requires_a_contact_channel_and_consent(): void
    {
        $this->post('/contact', ['name' => 'Test Sender', 'subject' => 'Question', 'message' => 'A valid inquiry message', 'form_token' => $this->requestData()['form_token']])->assertSessionHasErrors(['email', 'phone', 'consent']);
    }

    public function test_dashboard_uses_actual_database_counts_and_csv_export_is_safe(): void
    {
        $this->post('/appointments', [...$this->requestData(), 'name' => '=FORMULA()']);
        $this->actingAs($this->user());
        $this->getJson('/admin/api/dashboard')->assertOk()->assertJsonPath('metrics.Total requests', 1)->assertJsonPath('metrics.Pending requests', 1);
        $export = $this->get('/admin/api/appointments/export')->assertOk();
        $this->assertStringContainsString("'=FORMULA()",$export->streamedContent());
    }
}
