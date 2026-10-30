<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|min:2|max:120', 'email' => 'nullable|required_without:phone|email:rfc|max:254', 'phone' => ['nullable', 'required_without:email', 'string', 'regex:/^\+?[0-9 ()-]{7,25}$/'], 'subject' => 'required|string|max:150', 'message' => 'required|string|min:10|max:3000', 'consent' => 'required|accepted', 'website' => 'nullable|max:0', 'form_token' => 'required|string'];
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
        }];
    }
}
