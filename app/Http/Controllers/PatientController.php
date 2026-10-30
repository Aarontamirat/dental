<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\ContactRequest;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Support\ClinicSettings;
use App\Support\NotificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function appointment(AppointmentRequest $request, NotificationDelivery $notifications)
    {
        $data = $request->safe()->except(['website', 'form_token']);
        $appointment = DB::transaction(function () use ($data) {
            $record = Appointment::create([...$data, 'reference' => 'SD-'.Str::upper(Str::random(10)), 'consent' => true, 'consented_at' => now(), 'consent_text' => app(ClinicSettings::class)->get('privacy_consent'), 'status' => 'pending']);
            $record->history()->create(['from_status' => null, 'to_status' => 'pending', 'created_at' => now()]);

            return $record;
        });
        $notifications->appointment($appointment);

        return redirect('/appointments')->with('appointment_reference', $appointment->reference);
    }

    public function contact(ContactRequest $request, NotificationDelivery $notifications)
    {
        $message = ContactMessage::create([...$request->safe()->except(['website', 'form_token']), 'consent' => true, 'consented_at' => now(), 'consent_text' => app(ClinicSettings::class)->get('privacy_consent')]);
        $notifications->contact($message);

        return redirect('/contact')->with('contact_success', true);
    }
}
