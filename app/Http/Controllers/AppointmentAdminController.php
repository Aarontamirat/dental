<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Support\Access;
use App\Support\Audit;
use App\Support\ClinicSettings;
use App\Support\NotificationDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentAdminController extends Controller
{
    private function authorizeRequest(Request $request): void
    {
        abort_unless(Access::allows($request->user(), 'appointments'), 403);
    }

    private function query(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200', 'status' => ['nullable', Rule::in(Appointment::STATUSES)], 'branch_id' => 'nullable|integer|exists:branches,id', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d', 'page' => 'nullable|integer|min:1']);

        return Appointment::with(['service:id,title', 'branch:id,title', 'professional:id,title'])
            ->when($request->q, fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$request->q.'%')->orWhere('reference', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->from, fn ($q) => $q->whereDate('preferred_date', '>=', $request->from))->when($request->to, fn ($q) => $q->whereDate('preferred_date', '<=', $request->to));
    }

    public function index(Request $request)
    {
        $this->authorizeRequest($request);

        return $this->query($request)->latest()->paginate(15);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $this->authorizeRequest($request);

        return $appointment->load(['service', 'branch', 'professional', 'history.user:id,name']);
    }

    public function update(Request $request, Appointment $appointment, NotificationDelivery $notifications)
    {
        $this->authorizeRequest($request);
        $data = $request->validate(['status' => ['required', Rule::in(Appointment::STATUSES)], 'internal_notes' => 'nullable|string|max:5000', 'note' => 'nullable|string|max:2000', 'preferred_date' => 'required|date_format:Y-m-d', 'preferred_time' => 'required|date_format:H:i', 'expected_version' => 'required|integer|min:0']);
        $transitions = ['pending' => ['confirmed', 'rescheduled', 'cancelled', 'rejected'], 'confirmed' => ['rescheduled', 'completed', 'cancelled'], 'rescheduled' => ['confirmed', 'rescheduled', 'cancelled', 'rejected'], 'completed' => [], 'cancelled' => [], 'rejected' => []];
        $changed = false;
        DB::transaction(function () use ($appointment, $data, $transitions, &$changed) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if ((int) $data['expected_version'] !== (int) $locked->version) {
                abort(409, 'This request changed. Reload before saving.');
            }
            $old = $locked->status;
            if ($data['status'] !== $old && ! in_array($data['status'], $transitions[$old], true)) {
                throw ValidationException::withMessages(['status' => 'This status transition is not available.']);
            }
            $dateChanged = $data['preferred_date'] !== $locked->preferred_date->format('Y-m-d') || $data['preferred_time'] !== $locked->preferred_time;
            if ($dateChanged && $data['status'] !== 'rescheduled') {
                throw ValidationException::withMessages(['status' => 'Select Rescheduled when changing the requested date or time.']);
            }
            if ($data['status'] === 'rescheduled' && ($old !== 'rescheduled' || $dateChanged) && (! filled($data['note'] ?? null) || ! $dateChanged)) {
                throw ValidationException::withMessages(['note' => 'Rescheduling needs a new date/time and an explanation.']);
            }
            if ($dateChanged && CarbonImmutable::parse($data['preferred_date'].' '.$data['preferred_time'], app(ClinicSettings::class)->get('timezone'))->isPast()) {
                throw ValidationException::withMessages(['preferred_date' => 'Choose a future visit.']);
            }
            $locked->update([...array_diff_key($data, array_flip(['note', 'expected_version'])), 'version' => $locked->version + 1]);
            $changed = $old !== $data['status'] || $dateChanged;
            if ($changed) {
                $locked->history()->create(['from_status' => $old, 'to_status' => $data['status'], 'note' => $data['note'] ?? null, 'user_id' => auth()->id(), 'created_at' => now()]);
            }
            Audit::record('updated', 'appointments', $locked->id);
        });
        $appointment->refresh();
        if ($changed) {
            $notifications->appointment($appointment, true);
        }

        return response()->json(['message' => 'Appointment request updated.', 'record' => $appointment->load('history.user:id,name')]);
    }

    public function export(Request $request)
    {
        $this->authorizeRequest($request);
        $query = $this->query($request);
        Audit::record('exported', 'appointments');

        return response()->streamDownload(function () use ($query) {
            $s = fopen('php://output', 'w');
            fputcsv($s, ['Reference', 'Name', 'Phone', 'Email', 'Branch', 'Service', 'Date', 'Time', 'Status'], ',', '"', '');
            foreach ($query->orderBy('id')->lazy(200) as $r) {
                $values = [$r->reference, $r->name, $r->phone, $r->email, $r->branch?->title, $r->service?->title, $r->preferred_date->format('Y-m-d'), $r->preferred_time, $r->status];
                fputcsv($s, array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v, $values), ',', '"', '');
            } fclose($s);
        }, 'appointment-requests.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
