<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Page;
use App\Models\Professional;
use App\Models\Service;
use App\Support\ClinicSettings;

class SeoController extends Controller
{
    public function sitemap()
    {
        $clinic = app(ClinicSettings::class);
        $urls = ['/', '/about', '/contact', '/faq'];
        foreach (['services' => Service::class, 'doctors' => Professional::class, 'articles' => Article::class, 'branches' => Branch::class] as $module => $model) {
            if ($clinic->enabled($module)) {
                $urls[] = '/'.$module;
                foreach ($model::published()->pluck('slug') as $slug) {
                    $urls[] = '/'.$module.'/'.$slug;
                }
            }
        }
        foreach (['gallery', 'testimonials'] as $module) {
            if ($clinic->enabled($module)) {
                $urls[] = '/'.$module;
            }
        }
        foreach (Page::published()->where('slug', '!=', 'about')->pluck('slug') as $slug) {
            $urls[] = '/pages/'.$slug;
        }

        return response()->view('seo.sitemap', ['urls' => $urls, 'languages' => $clinic->get('languages')])->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /login\nDisallow: /forgot-password\nDisallow: /reset-password\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
