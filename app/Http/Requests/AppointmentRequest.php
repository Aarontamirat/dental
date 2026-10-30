<?php

namespace App\Http\Requests;

use App\Models\Branch;
use App\Models\Professional;
use App\Support\ClinicSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) app(ClinicSettings::class)->get('appointment_enabled');
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s()-]+/', '', (string) $this->input('phone'));
        if (preg_match('/^0([79][0-9]{8})$/', $phone, $m)) {
            $phone = '+251'.$m[1];
        } $this->merge(['phone' => $phone]);
    }

    public function rules(): array
    {
        $clinic = app(ClinicSettings::class);
        $today = CarbonImmutable::now($clinic->get('timezone'));
        $published = fn (string $table) => Rule::exists($table, 'id')->where(fn ($query) => $query->where('status', 'published')->when(! $clinic->get('demo_mode'), fn ($q) => $q->where('is_demo', false)));

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'], 'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'], 'email' => ['nullable', 'email:rfc', 'max:254'],
            'service_id' => ['nullable', 'integer', $published('services')], 'branch_id' => ['required', 'integer', $published('branches')],
            'professional_id' => ['nullable', 'integer', $published('professionals')],
            'preferred_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today->format('Y-m-d'), 'before_or_equal:'.$today->addDays($clinic->get('appointment_days_ahead'))->format('Y-m-d')],
            'preferred_time' => ['required', 'date_format:H:i'], 'message' => ['nullable', 'string', 'max:2000'], 'consent' => ['required', 'accepted'], 'website' => ['nullable', 'max:0'], 'form_token' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            try {
                $started = (int) Crypt::decryptString((string) $this->form_token);
                if (time() - $started < 2 || time() - $started > 14400) {
                    throw new \RuntimeException;
                }
            } catch (\Throwable) {
                $validator->errors()->add('form_token', __('ui.form_expired'));
            }
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $clinic = app(ClinicSettings::class);
            $branch = Branch::find($this->branch_id);
            if ($this->service_id && ! $branch->services()->where('services.id', $this->service_id)->exists()) {
                $validator->errors()->add('service_id', __('ui.service_branch'));
            }
            if ($this->professional_id && ! Professional::find($this->professional_id)->branches()->where('branches.id', $this->branch_id)->exists()) {
                $validator->errors()->add('professional_id', __('ui.doctor_branch'));
            }
            $date = CarbonImmutable::createFromFormat('Y-m-d H:i', $this->preferred_date.' '.$this->preferred_time, $clinic->get('timezone'));
            if ($date->isPast()) {
                $validator->errors()->add('preferred_time', __('ui.future_time'));
            }
            $hours = collect($branch->hours ?? [])->firstWhere('day', $date->format('l'));
            $start = $hours['open'] ?? $clinic->get('appointment_start');
            $end = $hours['close'] ?? $clinic->get('appointment_end');
            if (($hours['closed'] ?? false) || $this->preferred_time < $start || $this->preferred_time >= $end) {
                $validator->errors()->add('preferred_time', __('ui.outside_hours'));
            }
        }];
    }
}
