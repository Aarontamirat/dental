<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use App\Models\NavigationItem;
use Illuminate\Database\Seeder;

class ClinicFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [['hero', 'Good care. Great smiles.', '', true], ['trust', 'Thoughtful care, from the start.', '', true], ['statistics', 'Verified clinic figures', 'Publish only figures verified by your clinic.', false], ['services', 'A little care for every smile.', 'From everyday essentials to specialist treatments, explore care centered around you.', true], ['about', 'Your comfort comes first.', 'A welcoming space. A listening ear. A plan you understand.', true], ['why', 'Dentistry that feels different.', 'The little things make a big difference.', true], ['doctors', 'People who care.', 'Meet the people behind a more comfortable dental visit.', true], ['technology', 'A calm space. Thoughtful details.', 'Explore the place where your care begins.', true], ['testimonials', 'Every smile has a story.', '', true], ['before_after', 'A closer look at care.', 'Published only with verified patient consent.', false], ['appointment', 'Let’s care for your smile.', 'Your next step can start with a simple conversation.', true], ['faq', 'A few things you may be wondering.', 'We’re here to make your next visit feel easier.', true], ['articles', 'A little knowledge. A healthier smile.', 'Thoughts, tips, and information for life between visits.', true], ['branches', 'Close to you. Ready to listen.', 'Find your preferred branch and plan your visit.', true], ['announcements', 'What’s new at the clinic.', '', false]];
        foreach ($sections as $i => [$key,$title,$subtitle,$enabled]) {
            HomepageSection::firstOrCreate(['key' => $key], ['title' => $title, 'subtitle' => $subtitle, 'enabled' => $enabled, 'sort_order' => $i]);
        }
        foreach ([['About us', '/about', 'header', 'ስለ እኛ'], ['Our services', '/services', 'header', 'አገልግሎቶች'], ['Our team', '/doctors', 'header', 'ቡድናችን'], ['Patient resources', '/articles', 'header', 'መረጃ'], ['Contact', '/contact', 'header', 'አግኙን'], ['Privacy notice', '/pages/privacy', 'footer', 'ግላዊነት'], ['Terms', '/pages/terms', 'footer', 'ደንቦች'], ['Cookies', '/pages/cookies', 'footer', 'ኩኪዎች']] as $i => [$title,$url,$location,$am]) {
            NavigationItem::firstOrCreate(['url' => $url, 'location' => $location], ['title' => $title, 'sort_order' => $i, 'enabled' => true, 'translations' => ['am' => ['title' => $am]]]);
        }
    }
}
