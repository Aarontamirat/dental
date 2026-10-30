<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class SettingsRegistry
{
    public static function fields(): array
    {
        return [
            ['name', 'Clinic name', 'text', true], ['tagline', 'Tagline', 'text'], ['demo_mode', 'Show demonstration notice', 'boolean'],
            ['logo', 'Clinic logo', 'image'], ['favicon', 'Favicon', 'image'], ['primary_color', 'Primary brand color', 'color', true], ['secondary_color', 'Dark brand color', 'color', true], ['accent_color', 'Accent color', 'color', true], ['background_color', 'Page background', 'color', true],
            ['font', 'Typography', 'select', true, ['editorial', 'modern']], ['header_style', 'Header preset', 'select', true, ['classic', 'centered']], ['footer_style', 'Footer preset', 'select', true, ['expanded', 'minimal']], ['hero_layout', 'Hero preset', 'select', true, ['split', 'immersive']],
            ['hero_eyebrow', 'Hero eyebrow', 'text'], ['hero_title', 'Hero heading (use a new line)', 'textarea', true], ['hero_description', 'Hero introduction', 'textarea'], ['hero_image', 'Hero photograph', 'image'],
            ['about_title', 'About heading', 'text'], ['about_description', 'About introduction', 'textarea'], ['about_image', 'Clinic photograph', 'image'],
            ['phone', 'Main phone', 'tel'], ['email', 'Public email', 'email'], ['address', 'Main address', 'textarea'], ['whatsapp', 'WhatsApp number', 'tel'], ['emergency_phone', 'Emergency phone (only if offered)', 'tel'], ['social_links', 'Social links', 'links'],
            ['default_branch_id', 'Default branch', 'relation', false, 'branches'], ['appointment_cta', 'Appointment button text', 'text', true], ['appointment_enabled', 'Accept appointment requests', 'boolean'],
            ['appointment_days_ahead', 'Request window in days', 'number', true], ['appointment_start', 'Default opening time', 'time', true], ['appointment_end', 'Default closing time', 'time', true],
            ['notification_email', 'Clinic notification email', 'email'], ['email_enabled', 'Send email using configured transport', 'boolean'],
            ['locale', 'Default language', 'select', true, ['en', 'am']], ['languages', 'Enabled languages', 'multiselect', true, ['en', 'am']], ['timezone', 'Operating timezone', 'select', true, ['Africa/Addis_Ababa']],
            ['currency', 'Currency', 'select', true, ['ETB']], ['show_prices', 'Show optional treatment prices', 'boolean'], ['seo_title', 'Default SEO title', 'text', true], ['seo_description', 'Default SEO description', 'textarea', true],
            ['privacy_consent', 'Consent wording (review before launch)', 'textarea', true], ['retention_days', 'Inquiry retention in days', 'number', true],
            ['modules', 'Enabled website modules', 'multiselect', true, ['services', 'doctors', 'branches', 'articles', 'gallery', 'testimonials']],
            ['trust_items', 'Trust messages (optional; defaults appear when empty)', 'cards'], ['statistics', 'Verified clinic statistics (optional)', 'stats'],
        ];
    }

    public static function rules(): array
    {
        $rules = [];
        foreach (self::fields() as $f) {
            [$name,,$type] = $f;
            $required = ($f[3] ?? false) ? 'required' : 'nullable';
            $rules[$name] = match ($type) {
                'boolean' => ['sometimes', 'boolean'], 'color' => [$required, 'regex:/^#[0-9a-fA-F]{6}$/'],
                'email' => [$required, 'email:rfc', 'max:254'], 'tel' => [$required, 'string', 'regex:/^\+?[0-9 ()-]{7,25}$/', 'max:32'],
                'image' => [$required, 'string', 'max:500', new ImageReference],
                'number' => [$required, 'integer', 'between:1,3650'], 'select' => [$required, Rule::in($f[4])],
                'multiselect' => [$required, 'array', 'min:1', 'max:10'], 'time' => [$required, 'date_format:H:i'],
                'relation' => [$required, 'integer', 'exists:branches,id'], 'links','cards','stats' => [$required, 'array', 'max:10'],
                default => [$required, 'string', 'max:'.($type === 'textarea' ? 5000 : 255)],
            };
            if ($type === 'multiselect') {
                $rules[$name.'.*'] = [Rule::in($f[4]), 'distinct'];
            }
            if ($type === 'links') {
                $rules[$name.'.*'] = ['array:label,url'];
                $rules[$name.'.*.label'] = ['required', 'string', 'max:100'];
                $rules[$name.'.*.url'] = ['required', 'url:https', 'max:500'];
            }
            if ($type === 'cards') {
                $rules[$name.'.*'] = ['array:icon,title,description'];
                $rules[$name.'.*.icon'] = ['required', Rule::in(['heart', 'shield', 'smile', 'scan', 'tooth', 'sparkles'])];
                $rules[$name.'.*.title'] = ['required', 'string', 'max:120'];
                $rules[$name.'.*.description'] = ['nullable', 'string', 'max:500'];
            }
            if ($type === 'stats') {
                $rules[$name.'.*'] = ['array:label,value,verified'];
                $rules[$name.'.*.label'] = ['required', 'string', 'max:120'];
                $rules[$name.'.*.value'] = ['required', 'string', 'max:24'];
                $rules[$name.'.*.verified'] = ['required', 'boolean'];
            }
        }
        $rules['appointment_days_ahead'] = ['required', 'integer', 'between:1,365'];
        $rules['modules'] = ['present', 'array', 'max:6'];
        $rules['translations'] = ['nullable', 'array:am'];
        $rules['translations.am'] = ['nullable', 'array:tagline,hero_eyebrow,hero_title,hero_description,appointment_cta,about_title,about_description,privacy_consent,seo_title,seo_description'];
        foreach (['tagline', 'hero_eyebrow', 'hero_title', 'hero_description', 'appointment_cta', 'about_title', 'about_description', 'privacy_consent', 'seo_title', 'seo_description'] as $key) {
            $rules['translations.am.'.$key] = ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }
}
