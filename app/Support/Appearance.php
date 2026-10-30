<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class Appearance
{
    public const FONTS = [
        'dm-sans' => "'DM Sans','Noto Sans Ethiopic',Arial,sans-serif",
        'dm-serif' => "'DM Serif Display','Noto Sans Ethiopic',Georgia,serif",
        'humanist' => "'Segoe UI','Noto Sans Ethiopic',Arial,sans-serif",
        'classic' => "Georgia,'Noto Sans Ethiopic',serif",
        'ethiopic' => "'Noto Sans Ethiopic','DM Sans',sans-serif",
    ];

    public static function schema(): array
    {
        $n = fn (string $label, int|float $default, int|float $min, int|float $max, string $unit = 'px') => compact('label', 'default', 'min', 'max', 'unit') + ['type' => 'number'];
        $s = fn (string $label, string $default, array $options) => compact('label', 'default', 'options') + ['type' => 'select'];
        $b = fn (string $label, bool $default) => compact('label', 'default') + ['type' => 'boolean'];

        return [
            'typography' => [
                'body_font' => $s('Body font', 'dm-sans', array_keys(self::FONTS)), 'heading_font' => $s('Heading font', 'dm-serif', array_keys(self::FONTS)), 'nav_font' => $s('Navigation font', 'dm-sans', array_keys(self::FONTS)),
                'body_size' => $n('Base font size', 18, 16, 22), 'paragraph_size' => $n('Paragraph size', 18, 16, 24), 'small_size' => $n('Supporting text', 14, 13, 16),
                'nav_size' => $n('Navigation size', 16, 15, 19), 'button_size' => $n('Button text', 16, 15, 20), 'label_size' => $n('Form labels', 16, 15, 20), 'input_size' => $n('Input text', 17, 16, 22),
                'h1' => $n('H1 maximum size', 80, 44, 100), 'h2' => $n('H2 maximum size', 48, 32, 64), 'h3' => $n('H3 size', 25, 21, 34), 'h4' => $n('H4 size', 21, 18, 28),
                'heading_scale' => $n('Heading scale', 1, .85, 1.15, '×'), 'heading_weight' => $s('Heading weight', '400', ['400', '500', '600', '700']), 'body_weight' => $s('Body weight', '400', ['400', '500']),
                'line_height' => $n('Body line height', 1.75, 1.5, 2, '×'), 'heading_line' => $n('Heading line height', 1.12, 1.05, 1.5, '×'), 'letter_spacing' => $n('Heading letter spacing', -.035, -.05, .03, 'em'), 'text_width' => $n('Reading width', 65, 48, 80, 'ch'),
            ],
            'layout' => [
                'density' => $s('Density', 'balanced', ['compact', 'balanced', 'spacious']), 'content_width' => $n('Content width', 1240, 1000, 1440), 'wide_width' => $n('Wide width', 1400, 1200, 1600),
                'narrow_width' => $n('Narrow width', 820, 640, 960), 'sidebar_width' => $n('Sidebar width', 320, 260, 380), 'section_space' => $n('Section vertical space', 96, 56, 140),
                'page_gutter' => $n('Desktop horizontal padding', 48, 24, 80), 'mobile_gutter' => $n('Mobile horizontal padding', 20, 16, 32), 'element_space' => $n('Element spacing', 24, 16, 40),
                'grid_gap' => $n('Grid gap', 28, 16, 48), 'radius' => $n('Panel radius', 16, 0, 32), 'border_width' => $n('Border thickness', 1, 1, 2), 'shadow' => $n('Shadow intensity', 12, 0, 24, '%'), 'background_style' => $s('Background detail', 'soft', ['plain', 'soft']),
            ],
            'buttons' => [
                'style' => $s('Primary style', 'solid', ['solid', 'outline']), 'secondary' => $s('Secondary style', 'outline', ['outline', 'soft']), 'radius' => $n('Button radius', 8, 0, 32),
                'height' => $n('Minimum height', 52, 44, 64), 'padding_x' => $n('Horizontal padding', 24, 16, 36), 'padding_y' => $n('Vertical padding', 14, 10, 20),
                'weight' => $s('Button weight', '600', ['500', '600', '700']), 'shadow' => $b('Button shadow', false), 'icon_size' => $n('Button icon size', 20, 16, 24), 'icon_position' => $s('Icon position', 'end', ['start', 'end']),
            ],
            'cards' => [
                'style' => $s('Card surface', 'bordered', ['bordered', 'elevated', 'flat']), 'radius' => $n('Card radius', 14, 0, 28), 'padding' => $n('Card padding', 30, 20, 44),
                'image_radius' => $n('Image radius', 12, 0, 24), 'image_ratio' => $s('Card photograph ratio', 'landscape', ['landscape', 'square', 'portrait']), 'columns' => $s('Desktop grid columns', '3', ['2', '3', '4']),
                'hover' => $s('Card interaction', 'lift', ['lift', 'border', 'none']), 'input_style' => $s('Input style', 'outlined', ['outlined', 'filled']), 'input_radius' => $n('Input radius', 8, 0, 20),
            ],
            'header' => [
                'style' => $s('Header layout', 'classic', ['classic', 'centered', 'compact']), 'height' => $n('Header height', 96, 76, 120), 'logo_size' => $n('Logo height', 48, 32, 64), 'nav_gap' => $n('Navigation gap', 24, 12, 36),
                'alignment' => $s('Menu alignment', 'center', ['start', 'center', 'end']), 'sticky' => $b('Sticky header', true), 'shadow' => $b('Header shadow', false), 'mobile_style' => $s('Mobile menu', 'panel', ['panel', 'sheet']),
            ],
            'footer' => [
                'style' => $s('Footer layout', 'expanded', ['expanded', 'minimal', 'band']), 'columns' => $s('Footer columns', '4', ['2', '3', '4']), 'space' => $n('Footer spacing', 64, 40, 100), 'logo_size' => $n('Footer logo height', 44, 32, 64),
                'contact' => $b('Show contact details', true), 'branches' => $b('Show branch link', true), 'social_style' => $s('Social link presentation', 'text', ['text', 'pills']),
                'copyright' => ['label' => 'Copyright text (blank uses translated default)', 'type' => 'text', 'default' => ''],
            ],
            'motion' => [
                'enabled' => $b('Enable animation', true), 'intensity' => $s('Motion intensity', 'subtle', ['subtle', 'expressive']), 'duration' => $n('Transition duration', 220, 100, 400, 'ms'),
                'page_transitions' => $b('Page transitions', true), 'scroll_reveal' => $b('Scroll reveals', true), 'hover' => $b('Hover effects', true),
            ],
        ];
    }

    public static function palette(bool $dark = false): array
    {
        $keys = ['primary', 'secondary', 'accent', 'background', 'surface', 'card', 'heading', 'text', 'muted', 'border', 'link', 'button', 'button_text', 'button_hover', 'nav_background', 'nav_text', 'sticky_background', 'footer_background', 'footer_text', 'footer_link', 'input_background', 'input_border', 'success', 'warning', 'error', 'info'];
        $colors = $dark
            ? ['#92d5c4', '#152e2b', '#e7be87', '#101d1b', '#192b27', '#20342f', '#f1f5ef', '#dde7df', '#b4c6bb', '#4e685f', '#a4dfcf', '#92d5c4', '#102d26', '#b5e8d9', '#152622', '#e1ebe3', '#152622', '#0c1715', '#d2e2d7', '#d8e9dc', '#14241f', '#77978a', '#9ad5ae', '#f2cd89', '#ffb6ac', '#a5cee9']
            : ['#176d67', '#153d3a', '#dfb77b', '#fcfbf8', '#eef3ed', '#ffffff', '#183d36', '#304a40', '#53675b', '#c7d3c9', '#176d67', '#176d67', '#ffffff', '#124c47', '#fcfbf8', '#334f43', '#fcfbf8', '#153d3a', '#d7e5da', '#e2ece1', '#ffffff', '#6e8879', '#27653b', '#775208', '#98392b', '#235d85'];

        return array_combine($keys, $colors);
    }

    public static function sectionKeys(): array
    {
        return ['hero', 'trust', 'services', 'about', 'why', 'doctors', 'technology', 'testimonials', 'appointment', 'faq', 'articles', 'branches', 'before_after', 'statistics', 'announcements'];
    }

    public static function sectionLayouts(string $key): array
    {
        return match ($key) {
            'hero' => ['split', 'centered', 'immersive'], 'services' => ['default', 'rows'], 'doctors' => ['default', 'portrait'], 'testimonials' => ['default', 'quotes'], 'about' => ['default', 'editorial'], 'appointment' => ['default', 'centered'], default => ['default'],
        };
    }

    public static function sectionOptions(string $key): array
    {
        $options = ['layout' => self::sectionLayouts($key), 'background' => ['default', 'paper', 'surface', 'card'], 'alignment' => ['start', 'center'], 'spacing' => ['compact', 'normal', 'generous'], 'width' => ['content', 'wide', 'narrow']];
        if (in_array($key, ['hero', 'about', 'branches', 'technology'], true)) {
            $options['image'] = ['left', 'right'];
        }
        if (in_array($key, ['hero', 'appointment'], true)) {
            $options['cta'] = ['inline', 'stacked'];
        }

        return $options;
    }

    public static function defaults(array $legacy = []): array
    {
        $value = ['version' => 1, 'preset' => 'premium', 'mode' => 'light', 'colors' => ['light' => self::palette(), 'dark' => self::palette(true)], 'sections' => []];
        foreach (self::schema() as $group => $fields) {
            $value[$group] = array_map(fn ($field) => $field['default'], $fields);
        }
        foreach (['primary_color' => 'primary', 'secondary_color' => 'secondary', 'accent_color' => 'accent', 'background_color' => 'background'] as $old => $new) {
            if (isset($legacy[$old]) && preg_match('/^#[0-9a-fA-F]{6}$/', $legacy[$old])) {
                $value['colors']['light'][$new] = $legacy[$old];
            }
        }
        $value['colors']['light']['button'] = $value['colors']['light']['link'] = $value['colors']['light']['primary'];
        $value['colors']['light']['footer_background'] = $value['colors']['light']['secondary'];
        $value['header']['style'] = in_array($legacy['header_style'] ?? '', ['classic', 'centered'], true) ? $legacy['header_style'] : 'classic';
        $value['footer']['style'] = ($legacy['footer_style'] ?? '') === 'minimal' ? 'minimal' : 'expanded';
        if (($legacy['font'] ?? '') === 'modern') {
            $value['typography']['heading_font'] = 'dm-sans';
            $value['typography']['heading_weight'] = '500';
        }
        foreach (self::sectionKeys() as $key) {
            $value['sections'][$key] = array_intersect_key(['layout' => 'default', 'background' => 'default', 'alignment' => 'start', 'spacing' => 'normal', 'width' => 'content', 'image' => 'right', 'cta' => 'inline'], self::sectionOptions($key));
        }
        $value['sections']['hero']['layout'] = ($legacy['hero_layout'] ?? '') === 'immersive' ? 'immersive' : 'split';

        return $value;
    }

    public static function presets(): array
    {
        $presets = [];
        foreach (['clinical' => 'Modern Clinical', 'premium' => 'Premium Dental', 'minimal' => 'Minimal Contemporary', 'family' => 'Warm Family', 'technology' => 'Advanced Technology', 'elegant' => 'Elegant Medical'] as $key => $name) {
            $value = self::defaults();
            $value['preset'] = $key;
            if ($key !== 'premium') {
                [$primary,$secondary,$accent,$background,$surface] = match ($key) {
                    'clinical' => ['#155d91', '#15344d', '#83c1d1', '#f7fafc', '#edf4f8'], 'minimal' => ['#384b47', '#263330', '#cfb993', '#ffffff', '#f4f5f2'],
                    'family' => ['#8b4934', '#4b3128', '#e7b876', '#fffaf3', '#f7eee2'], 'technology' => ['#3e50a2', '#20294e', '#94c9de', '#f6f8fc', '#eaf0f9'], default => ['#604d78', '#342d45', '#c8af8b', '#fcfafc', '#f1edf4'],
                };
                $value['colors']['light'] = array_replace($value['colors']['light'], compact('primary', 'secondary', 'accent', 'background', 'surface'), ['button' => $primary, 'link' => $primary, 'button_hover' => $secondary, 'footer_background' => $secondary, 'nav_background' => $background, 'sticky_background' => $background, 'heading' => $secondary]);
                [$night, $nightSurface, $nightCard, $nightPrimary, $nightText, $nightMuted, $nightBorder] = match ($key) {
                    'clinical' => ['#101e2b', '#192c3d', '#20374a', '#a3d3f3', '#deebf5', '#b7cadb', '#536f86'],
                    'minimal' => ['#191e1c', '#242c28', '#2b3530', '#c1d6c8', '#e3eae5', '#bac9bf', '#5e7265'],
                    'family' => ['#241b17', '#34261f', '#403027', '#f2c1a1', '#f3e7db', '#d1beb0', '#80695a'],
                    'technology' => ['#141c30', '#202c43', '#293751', '#b7c8ff', '#e6ebfa', '#bcc8e4', '#60739d'],
                    default => ['#211b2b', '#30273c', '#3b3048', '#dac4f1', '#f0e7f7', '#cfbfdf', '#79668c'],
                };
                $value['colors']['dark'] = array_replace($value['colors']['dark'], ['primary' => $nightPrimary, 'secondary' => $nightSurface, 'accent' => $accent, 'background' => $night, 'surface' => $nightSurface, 'card' => $nightCard, 'heading' => '#faf8fc', 'text' => $nightText, 'muted' => $nightMuted, 'border' => $nightBorder, 'link' => $nightPrimary, 'button' => $nightPrimary, 'button_text' => $night, 'button_hover' => '#f4e9d8', 'nav_background' => $night, 'nav_text' => $nightText, 'sticky_background' => $nightSurface, 'footer_background' => $night, 'footer_text' => $nightText, 'footer_link' => $nightPrimary, 'input_background' => $night, 'input_border' => $nightMuted]);
                $value['typography']['heading_font'] = in_array($key, ['family', 'elegant']) ? 'classic' : 'dm-sans';
                $value['typography']['heading_weight'] = in_array($key, ['family', 'elegant']) ? '400' : '500';
                $value['layout']['radius'] = $key === 'minimal' ? 2 : ($key === 'family' ? 24 : 12);
                $value['cards']['radius'] = $value['layout']['radius'];
                $value['buttons']['radius'] = $key === 'family' ? 28 : ($key === 'minimal' ? 2 : 8);
                $value['layout']['density'] = $key === 'minimal' ? 'spacious' : 'balanced';
                $value['cards']['style'] = $key === 'minimal' ? 'flat' : ($key === 'technology' ? 'elevated' : 'bordered');
                $value['header']['style'] = $key === 'elegant' ? 'centered' : ($key === 'minimal' ? 'compact' : 'classic');
                $value['footer']['style'] = $key === 'minimal' ? 'band' : 'expanded';
                $value['sections']['hero']['layout'] = $key === 'minimal' ? 'centered' : ($key === 'technology' ? 'immersive' : 'split');
                $value['sections']['services']['layout'] = $key === 'minimal' ? 'rows' : 'default';
                $value['sections']['about']['layout'] = $key === 'elegant' ? 'editorial' : 'default';
            }
            $presets[$key] = ['name' => $name, 'configuration' => $value];
        }

        return $presets;
    }

    public static function rules(): array
    {
        $rules = ['configuration' => ['required', 'array:version,preset,mode,colors,sections,'.implode(',', array_keys(self::schema()))],
            'configuration.version' => ['required', 'integer', Rule::in([1])], 'configuration.preset' => ['required', Rule::in(array_keys(self::presets()))],
            'configuration.mode' => ['required', Rule::in(['light', 'dark', 'system'])], 'configuration.colors' => ['required', 'array:light,dark'], 'configuration.sections' => ['required', 'array:'.implode(',', self::sectionKeys())]];
        foreach (['light', 'dark'] as $mode) {
            $rules['configuration.colors.'.$mode] = ['required', 'array:'.implode(',', array_keys(self::palette()))];
            foreach (self::palette() as $key => $color) {
                $rules['configuration.colors.'.$mode.'.'.$key] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
            }
        }
        foreach (self::schema() as $group => $fields) {
            $rules['configuration.'.$group] = ['required', 'array:'.implode(',', array_keys($fields))];
            foreach ($fields as $key => $field) {
                $rules['configuration.'.$group.'.'.$key] = match ($field['type']) {
                    'number' => ['required', 'numeric', 'between:'.$field['min'].','.$field['max']], 'boolean' => ['required', 'boolean'], 'select' => ['required', Rule::in($field['options'])], default => ['nullable', 'string', 'max:200'],
                };
            }
        }
        foreach (self::sectionKeys() as $key) {
            $rules['configuration.sections.'.$key] = ['required', 'array:'.implode(',', array_keys(self::sectionOptions($key)))];
            foreach (self::sectionOptions($key) as $field => $options) {
                $rules['configuration.sections.'.$key.'.'.$field] = ['required', Rule::in($options)];
            }
        }

        return $rules;
    }

    public static function resolve(array $settings): array
    {
        $defaults = self::defaults($settings);
        $resolved = array_replace_recursive($defaults, is_array($settings['appearance'] ?? null) ? $settings['appearance'] : []);
        if (! is_array($resolved['sections'])) {
            return $defaults;
        }
        foreach (self::sectionKeys() as $key) {
            if (! is_array($resolved['sections'][$key] ?? null)) {
                return $defaults;
            }
            $resolved['sections'][$key] = array_intersect_key($resolved['sections'][$key], self::sectionOptions($key));
        }

        return Validator::make(['configuration' => $resolved], self::rules())->passes() ? self::normalize($resolved) : $defaults;
    }

    public static function normalize(array $configuration): array
    {
        $configuration['version'] = (int) $configuration['version'];
        foreach (self::schema() as $group => $fields) {
            foreach ($fields as $key => $field) {
                $value = $configuration[$group][$key] ?? null;
                $configuration[$group][$key] = match ($field['type']) {
                    'boolean' => (bool) $value,
                    'number' => $value + 0,
                    default => (string) $value,
                };
            }
        }

        return $configuration;
    }

    public static function warnings(array $configuration): array
    {
        $warnings = [];
        foreach ($configuration['colors'] as $mode => $colors) {
            foreach ([['text', 'background'], ['text', 'surface'], ['muted', 'background'], ['muted', 'surface'], ['heading', 'background'], ['heading', 'surface'], ['heading', 'card'], ['text', 'card'], ['muted', 'card'], ['primary', 'background'], ['primary', 'surface'], ['primary', 'card'], ['link', 'background'], ['link', 'surface'], ['link', 'card'], ['button_text', 'button'], ['button_text', 'button_hover'], ['nav_text', 'nav_background'], ['nav_text', 'sticky_background'], ['footer_text', 'footer_background'], ['footer_link', 'footer_background'], ['text', 'input_background'], ['muted', 'input_background'], ['success', 'surface'], ['warning', 'surface'], ['error', 'surface'], ['info', 'surface']] as [$foreground,$background]) {
                $ratio = BrandContrast::ratio($colors[$foreground], $colors[$background]);
                if ($ratio < 4.5) {
                    $warnings[] = ucfirst($mode).': '.str_replace('_', ' ', $foreground).' on '.str_replace('_', ' ', $background).' has '.round($ratio, 2).':1 contrast; use at least 4.5:1.';
                }
            }
            foreach (['input_background', 'background', 'surface'] as $background) {
                $ratio = BrandContrast::ratio($colors['input_border'], $colors[$background]);
                if ($ratio < 3) {
                    $warnings[] = ucfirst($mode).': input border on '.str_replace('_', ' ', $background).' has '.round($ratio, 2).':1 contrast; use at least 3:1.';
                }
            }
        }

        return $warnings;
    }

    public static function css(array $configuration): string
    {
        $variables = [];
        foreach (self::schema() as $group => $fields) {
            foreach ($fields as $key => $field) {
                if ($field['type'] === 'number') {
                    $variables[] = '--'.$group.'-'.str_replace('_', '-', $key).':'.(float) $configuration[$group][$key].(in_array($field['unit'], ['px', 'ms', 'em', 'ch']) ? $field['unit'] : '').';';
                }
            }
        }
        foreach (['body_font' => 'sans', 'heading_font' => 'serif', 'nav_font' => 'nav-font'] as $key => $token) {
            $variables[] = '--'.$token.':'.self::FONTS[$configuration['typography'][$key]].';';
        }
        foreach (['heading_weight', 'body_weight'] as $key) {
            $variables[] = '--'.str_replace('_', '-', $key).':'.$configuration['typography'][$key].';';
        }
        $variables[] = '--button-weight:'.$configuration['buttons']['weight'].';--density:'.['compact' => .82, 'balanced' => 1, 'spacious' => 1.16][$configuration['layout']['density']].';';
        $variables[] = '--columns:'.$configuration['cards']['columns'].';--footer-columns:'.$configuration['footer']['columns'].';';
        $variables[] = '--hover-y:'.($configuration['motion']['intensity'] === 'expressive' ? '-5px' : '-3px').';--shadow-alpha:'.($configuration['layout']['shadow'] / 100).';';
        $palette = function (array $colors): string {
            $css = '';
            foreach ($colors as $key => $value) {
                $css .= '--color-'.str_replace('_', '-', $key).':'.$value.';';
            }
            $onSecondary = BrandContrast::ratio('#ffffff', $colors['secondary']) >= BrandContrast::ratio('#000000', $colors['secondary']) ? '#ffffff' : '#000000';
            $css .= '--color-on-secondary:'.$onSecondary.';';

            return $css;
        };
        $mode = $configuration['mode'];
        $css = ':root{'.implode('', $variables).$palette($configuration['colors'][$mode === 'dark' ? 'dark' : 'light']).'color-scheme:'.($mode === 'dark' ? 'dark' : 'light').';}';
        if ($mode === 'system') {
            $css .= '@media(prefers-color-scheme:dark){:root{'.$palette($configuration['colors']['dark']).'color-scheme:dark;}}';
        }

        return $css;
    }
}
