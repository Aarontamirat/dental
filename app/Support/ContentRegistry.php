<?php

namespace App\Support;

use App\Models;
use Illuminate\Validation\Rule;

final class ContentRegistry
{
    public static function modules(): array
    {
        $base = [
            ['title', 'Name / title', 'text', true], ['slug', 'URL slug', 'slug', true], ['excerpt', 'Short introduction', 'textarea'],
            ['content', 'Content', 'richtext'], ['image', 'Featured image', 'image'], ['image_alt', 'Image description', 'text'],
            ['status', 'Publication', 'select', true, ['draft', 'published']], ['featured', 'Featured', 'boolean'],
            ['sort_order', 'Display order', 'number'], ['seo_title', 'SEO title', 'text'], ['seo_description', 'SEO description', 'textarea'],
            ['is_demo', 'Demonstration content (hidden when demo mode is off)', 'boolean'],
        ];
        $module = fn ($label, $model, $extra = [], $relations = []) => ['label' => $label, 'model' => $model, 'fields' => array_merge($base, $extra), 'relations' => $relations, 'ability' => 'content', 'translated' => true];

        return [
            'services' => $module('Services', Models\Service::class, [
                ['category_id', 'Category', 'relation', false, 'service_categories'], ['icon', 'Icon', 'select', false, ['tooth', 'sparkles', 'shield', 'smile', 'heart', 'scan']],
                ['benefits', 'Benefits (one per line)', 'lines'], ['procedure', 'Procedure steps (one per line)', 'lines'], ['faq', 'Treatment questions', 'faq'], ['price', 'Optional price (ETB)', 'number'],
                ['branches', 'Available at branches', 'multirelation', false, 'branches'], ['related', 'Related services', 'multirelation', false, 'services'],
            ], ['branches', 'related']),
            'professionals' => $module('Doctors & staff', Models\Professional::class, [
                ['professional_title', 'Professional title', 'text'], ['kind', 'Team category', 'select', true, ['dentist', 'specialist', 'hygienist', 'staff']],
                ['qualifications', 'Verified qualifications', 'lines'], ['specializations', 'Specializations', 'lines'], ['languages', 'Languages spoken', 'lines'],
                ['affiliations', 'Verified affiliations', 'lines'], ['years_experience', 'Verified experience in years', 'number'], ['links', 'Professional links', 'links'],
                ['branches', 'Assigned branches', 'multirelation', false, 'branches'],
            ], ['branches']),
            'branches' => $module('Branches', Models\Branch::class, [
                ['address', 'Street address', 'text', true], ['city', 'City', 'text', true], ['region', 'Region', 'text'], ['locality', 'Sub-city / locality', 'text'],
                ['phone', 'Phone', 'tel'], ['email', 'Email', 'email'], ['appointment_email', 'Appointment notification email', 'email'], ['map_url', 'Google Maps HTTPS link', 'url'],
                ['hours', 'Opening hours', 'hours'], ['services', 'Available services', 'multirelation', false, 'services'], ['professionals', 'Assigned professionals', 'multirelation', false, 'professionals'],
            ], ['services', 'professionals']),
            'articles' => $module('Articles', Models\Article::class, [
                ['category_id', 'Category', 'relation', false, 'article_categories'], ['author_id', 'Clinical author', 'relation', false, 'professionals'],
                ['published_at', 'Publication date', 'datetime'], ['tags', 'Tags', 'multirelation', false, 'tags'],
            ], ['tags']),
            'pages' => $module('Pages & announcements', Models\Page::class, [['kind', 'Page type', 'select', true, ['page', 'announcement', 'offer']]]),
            'gallery' => $module('Gallery', Models\GalleryItem::class, [
                ['category_id', 'Category', 'relation', false, 'gallery_categories'], ['kind', 'Image type', 'select', true, ['clinic', 'equipment', 'team', 'treatment', 'before_after']],
                ['after_image', 'After photograph', 'image'], ['consent', 'Publication consent verified', 'boolean'], ['consent_reference', 'Private consent record reference', 'textarea'], ['service_id', 'Related service', 'relation', false, 'services'], ['branch_id', 'Related branch', 'relation', false, 'branches'],
            ]),
            'testimonials' => $module('Testimonials', Models\Testimonial::class, [
                ['rating', 'Rating (optional, 1–5)', 'number'], ['approved', 'Approved for publication', 'boolean'], ['consent', 'Publication consent verified', 'boolean'],
            ]),
            'faqs' => $module('Frequently asked questions', Models\Faq::class),
            'categories' => $module('Categories', Models\Category::class, [['type', 'Category type', 'select', true, ['service', 'article', 'gallery']]]),
            'tags' => ['label' => 'Article tags', 'model' => Models\Tag::class, 'fields' => [['title', 'Name', 'text', true], ['slug', 'Slug', 'slug', true]], 'relations' => [], 'ability' => 'content', 'translated' => false],
            'navigation' => ['label' => 'Navigation', 'model' => Models\NavigationItem::class, 'fields' => [
                ['title', 'Label', 'text', true], ['url', 'Destination (local path or HTTPS)', 'navigation', true], ['location', 'Menu', 'select', true, ['header', 'footer']], ['sort_order', 'Order', 'number'], ['enabled', 'Visible', 'boolean'],
            ], 'relations' => [], 'ability' => 'content', 'translated' => true],
        ];
    }

    public static function get(string $key): array
    {
        return self::modules()[$key] ?? abort(404);
    }

    public static function tableFor(string $key): string
    {
        return match ($key) {
            'service_categories', 'article_categories', 'gallery_categories' => 'categories', default => (new (self::get($key)['model']))->getTable()
        };
    }

    public static function rules(string $key, ?int $id = null): array
    {
        $module = self::get($key);
        $table = (new $module['model'])->getTable();
        $rules = [];
        foreach ($module['fields'] as $field) {
            [$name, , $type] = $field;
            $required = ($field[3] ?? false) ? 'required' : 'nullable';
            $rules[$name] = match ($type) {
                'boolean' => ['sometimes', 'boolean'],
                'number' => [$required, 'numeric', 'min:0', 'max:10000000'],
                'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, $name)->ignore($id)],
                'select' => [$required, Rule::in($field[4])],
                'email' => [$required, 'email:rfc', 'max:254'],
                'image' => [$required, 'string', 'max:500', new ImageReference],
                'url' => [$required, 'url:https', 'max:1000'],
                'navigation' => [$required, 'string', 'max:500', 'regex:#^(?:/(?!/)[a-zA-Z0-9/?=&_%\-\.\#]*|https://[^\s]+)$#'],
                'tel' => [$required, 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,25}$/'],
                'relation' => [$required, 'integer', Rule::exists(self::tableFor($field[4]), 'id')->when(str_ends_with($field[4], '_categories'), fn ($rule) => $rule->where('type', explode('_', $field[4])[0]))],
                'multirelation', 'lines', 'faq', 'links', 'hours' => [$required, 'array', 'max:100'],
                'datetime' => [$required, 'date'],
                default => [$required, 'string', 'max:'.($type === 'richtext' ? 100000 : ($type === 'textarea' ? 5000 : 255))],
            };
            if ($type === 'multirelation') {
                $rules[$name.'.*'] = ['integer', 'distinct', Rule::exists(self::tableFor($field[4]), 'id')];
            }
            if ($type === 'lines') {
                $rules[$name.'.*'] = ['string', 'max:1000'];
            }
            if ($type === 'faq') {
                $rules[$name.'.*'] = ['array:question,answer'];
                $rules[$name.'.*.question'] = ['required', 'string', 'max:500'];
                $rules[$name.'.*.answer'] = ['required', 'string', 'max:3000'];
            }
            if ($type === 'links') {
                $rules[$name.'.*'] = ['array:label,url'];
                $rules[$name.'.*.label'] = ['required', 'string', 'max:100'];
                $rules[$name.'.*.url'] = ['required', 'url:https', 'max:500'];
            }
            if ($type === 'hours') {
                $rules[$name.'.*'] = ['array:day,open,close,closed'];
                $rules[$name.'.*.day'] = ['required', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])];
                $rules[$name.'.*.closed'] = ['boolean'];
                $rules[$name.'.*.open'] = ['nullable', 'date_format:H:i'];
                $rules[$name.'.*.close'] = ['nullable', 'date_format:H:i'];
            }
        }
        if (isset($rules['sort_order'])) {
            $rules['sort_order'] = ['nullable', 'integer', 'min:0', 'max:10000'];
        }
        if ($key === 'testimonials') {
            $rules['rating'] = ['nullable', 'integer', 'between:1,5'];
        }
        if ($key === 'professionals') {
            $rules['years_experience'] = ['nullable', 'integer', 'between:0,80'];
        }
        if ($module['translated']) {
            $rules['translations'] = ['nullable', 'array:am'];
            $rules['translations.am'] = ['nullable', 'array:title,excerpt,content,seo_title,seo_description'];
            foreach (['title', 'excerpt', 'content', 'seo_title', 'seo_description'] as $name) {
                $rules['translations.am.'.$name] = ['nullable', 'string', 'max:'.($name === 'content' ? 100000 : 5000)];
            }
        }

        return $rules;
    }
}
