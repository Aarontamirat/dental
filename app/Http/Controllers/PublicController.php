<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\HomepageSection;
use App\Models\Page;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Testimonial;
use App\Support\ClinicSettings;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    private function enabled(string $module): void
    {
        abort_unless(app(ClinicSettings::class)->enabled($module), 404);
    }

    public function home()
    {
        return view('public.home', [
            'sections' => app(ClinicSettings::class)->homepageSections(),
            'services' => Service::published()->ordered()->where('featured', true)->with('category')->limit(6)->get(),
            'professionals' => Professional::published()->ordered()->where('featured', true)->limit(3)->get(),
            'articles' => Article::published()->orderByDesc('published_at')->with('category')->limit(3)->get(),
            'branches' => Branch::published()->ordered()->get(), 'faqs' => Faq::published()->ordered()->limit(6)->get(),
            'testimonials' => Testimonial::publishable()->ordered()->limit(3)->get(), 'gallery' => GalleryItem::publishable()->ordered()->limit(5)->get(),
            'beforeAfter' => GalleryItem::publishable()->where('kind', 'before_after')->ordered()->limit(3)->get(),
            'announcements' => Page::published()->whereIn('kind', ['announcement', 'offer'])->ordered()->limit(3)->get(),
        ]);
    }

    public function about()
    {
        return view('public.about', ['page' => Page::published()->where('slug', 'about')->first(), 'professionals' => Professional::published()->ordered()->limit(3)->get()]);
    }

    public function services(Request $request)
    {
        $this->enabled('services');
        $request->validate(['q' => 'nullable|string|max:200', 'category' => 'nullable|string|max:180']);
        $items = Service::published()->with('category')->ordered()->when($request->q, fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))->when($request->category, fn ($q) => $q->whereHas('category', fn ($s) => $s->where('slug', $request->category)))->paginate(9)->withQueryString();

        return view('public.list', ['kind' => 'services', 'title' => __('ui.services_title'), 'eyebrow' => __('ui.our_services'), 'description' => __('ui.services_intro'), 'items' => $items, 'categories' => Category::published()->where('type', 'service')->ordered()->get()]);
    }

    public function service(string $slug)
    {
        $this->enabled('services');
        $item = Service::published()->where('slug', $slug)->with(['category', 'branches' => fn ($q) => $q->published(), 'related' => fn ($q) => $q->published(), 'gallery' => fn ($q) => $q->publishable()])->firstOrFail();

        return view('public.detail', ['kind' => 'services', 'item' => $item]);
    }

    public function doctors(Request $request)
    {
        $this->enabled('doctors');
        $request->validate(['kind' => 'nullable|in:dentist,specialist,hygienist,staff']);

        return view('public.list', ['kind' => 'doctors', 'title' => __('ui.team_title'), 'eyebrow' => __('ui.our_team'), 'description' => __('ui.team_intro'), 'items' => Professional::published()->ordered()->when($request->kind, fn ($q) => $q->where('kind', $request->kind))->paginate(9)->withQueryString(), 'categories' => collect()]);
    }

    public function doctor(string $slug)
    {
        $this->enabled('doctors');

        return view('public.detail', ['kind' => 'doctors', 'item' => Professional::published()->where('slug', $slug)->with(['branches' => fn ($q) => $q->published()])->firstOrFail()]);
    }

    public function articles(Request $request)
    {
        $this->enabled('articles');
        $request->validate(['q' => 'nullable|string|max:200', 'category' => 'nullable|string|max:180', 'tag' => 'nullable|string|max:180']);
        $items = Article::published()->with(['category', 'author' => fn ($q) => $q->published()])->when($request->q, fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))->when($request->category, fn ($q) => $q->whereHas('category', fn ($s) => $s->where('slug', $request->category)))->when($request->tag, fn ($q) => $q->whereHas('tags', fn ($s) => $s->where('slug', $request->tag)))->orderByDesc('published_at')->paginate(9)->withQueryString();

        return view('public.list', ['kind' => 'articles', 'title' => __('ui.resources_title'), 'eyebrow' => __('ui.patient_resources'), 'description' => __('ui.resources_intro'), 'items' => $items, 'categories' => Category::published()->where('type', 'article')->ordered()->get()]);
    }

    public function article(string $slug)
    {
        $this->enabled('articles');
        $item = Article::published()->where('slug', $slug)->with(['category', 'author' => fn ($q) => $q->published(), 'tags'])->firstOrFail();

        return view('public.detail', ['kind' => 'articles', 'item' => $item, 'related' => Article::published()->where('id', '!=', $item->id)->where('category_id', $item->category_id)->limit(3)->get()]);
    }

    public function branches()
    {
        $this->enabled('branches');

        return view('public.branches', ['branches' => Branch::published()->ordered()->get()]);
    }

    public function branch(string $slug)
    {
        $this->enabled('branches');

        return view('public.detail', ['kind' => 'branches', 'item' => Branch::published()->where('slug', $slug)->with(['services' => fn ($q) => $q->published(), 'professionals' => fn ($q) => $q->published()])->firstOrFail()]);
    }

    public function gallery(Request $request)
    {
        $this->enabled('gallery');
        $request->validate(['category' => 'nullable|integer|exists:categories,id']);

        return view('public.gallery', ['items' => GalleryItem::publishable()->when($request->category, fn ($q) => $q->where('category_id', $request->category))->ordered()->paginate(12)->withQueryString(), 'categories' => Category::published()->where('type', 'gallery')->ordered()->get()]);
    }

    public function appointments()
    {
        abort_unless(app(ClinicSettings::class)->get('appointment_enabled'), 404);

        return view('public.appointments', ['branches' => Branch::published()->ordered()->with(['services' => fn ($q) => $q->published(), 'professionals' => fn ($q) => $q->published()])->get(), 'services' => Service::published()->ordered()->get(), 'professionals' => Professional::published()->ordered()->get()]);
    }

    public function contact()
    {
        return view('public.contact', ['branches' => Branch::published()->ordered()->get()]);
    }

    public function page(string $slug)
    {
        return view('public.detail', ['kind' => 'pages', 'item' => Page::published()->where('slug', $slug)->firstOrFail()]);
    }

    public function faq()
    {
        return view('public.faq', ['faqs' => Faq::published()->ordered()->get()]);
    }

    public function testimonials()
    {
        $this->enabled('testimonials');

        return view('public.testimonials',['testimonials' => Testimonial::publishable()->ordered()->paginate(9)]);
    }
}
