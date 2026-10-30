<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Faq;
use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tag;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $common = ['status' => 'published', 'featured' => true, 'is_demo' => true];
        $branch = Branch::firstOrCreate(['slug' => 'addis-ababa-demo'], [...$common, 'title' => 'Addis Ababa · demo branch', 'address' => 'Bole, Addis Ababa — demonstration address', 'city' => 'Addis Ababa', 'region' => 'Addis Ababa', 'locality' => 'Bole', 'excerpt' => 'A calm, welcoming place for your next visit. This is a demonstration location.', 'image' => '/images/clinic.webp', 'image_alt' => 'Demonstration dental clinic interior', 'hours' => array_map(fn ($day) => ['day' => $day, 'open' => '08:00', 'close' => '18:00', 'closed' => $day === 'Sunday'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])]);
        $categories = [];
        foreach (['General dentistry', 'Cosmetic dentistry', 'Restorative dentistry', 'Orthodontics'] as $name) {
            $categories[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], [...$common, 'title' => $name, 'type' => 'service']);
        }
        $treatments = [
            ['General dentistry', 'general-dentistry', 'A healthy smile starts with the basics. Thoughtful check-ups and preventive care for every stage of life.', 'General dentistry', 'tooth', 'አጠቃላይ የጥርስ ሕክምና'],
            ['Teeth whitening', 'teeth-whitening', 'Explore options for a brighter smile, with advice tailored to your teeth and your comfort.', 'Cosmetic dentistry', 'sparkles', 'የጥርስ ማንጣት'],
            ['Dental implants', 'dental-implants', 'Learn about a considered approach to replacing missing teeth and restoring everyday confidence.', 'Restorative dentistry', 'shield', 'የጥርስ ተከላ'],
            ['Braces & aligners', 'braces-and-aligners', 'Discover orthodontic options, with a clear explanation of what may work for your smile.', 'Orthodontics', 'smile', 'የጥርስ ማስተካከያ'],
            ['Children’s dentistry', 'childrens-dentistry', 'Gentle introductions and supportive care to help little smiles feel at ease.', 'General dentistry', 'heart', 'የልጆች የጥርስ ሕክምና'],
            ['Root canal care', 'root-canal-care', 'Understand treatment options for an affected tooth, starting with an individual assessment.', 'Restorative dentistry', 'scan', 'የጥርስ ሥር ሕክምና'],
        ];
        foreach ($treatments as $i => [$title,$slug,$excerpt,$category,$icon,$am]) {
            $service = Service::firstOrCreate(['slug' => $slug], [...$common, 'title' => $title, 'excerpt' => $excerpt, 'category_id' => $categories[$category]->id, 'icon' => $icon, 'sort_order' => $i,
                'content' => '<h2>Care that starts with a conversation</h2><p>This is demonstration treatment information. At a real clinic, your dentist will explain the available options following an examination. Treatment suitability, risks, costs and outcomes vary by person.</p><h2>What to expect</h2><p>Bring your questions to a consultation. Your clinician will explain next steps and discuss a plan with you before any procedure.</p>',
                'benefits' => ['A personal consultation', 'Clear explanations of your options', 'An individual care plan'], 'procedure' => ['Discuss your questions and concerns', 'Receive an assessment from your clinician', 'Review the options, costs and next steps'],
                'faq' => [['question' => 'Is this treatment suitable for everyone?', 'answer' => 'Suitability depends on an individual examination. Contact a qualified dental professional for personal advice.']],
                'translations' => ['am' => ['title' => $am, 'excerpt' => 'ይህ የማሳያ ይዘት ነው። ለግል ምክር የጥርስ ሐኪም ያማክሩ።']]]);
            $service->branches()->syncWithoutDetaching([$branch->id]);
        }
        foreach ([['Your general dentist', 'demo-general-dentist', '/images/team-one.webp', 'General dental care'], ['Your dental specialist', 'demo-dental-specialist', '/images/team-two.webp', 'Specialist care']] as [$title,$slug,$image,$specialty]) {
            $person = Professional::firstOrCreate(['slug' => $slug], [...$common, 'title' => $title, 'professional_title' => 'Demonstration profile', 'kind' => 'dentist', 'excerpt' => 'An illustrative team profile. Replace with a real clinician’s approved biography and verified credentials before publishing.', 'image' => $image, 'image_alt' => 'Stock portrait used for a clearly labelled demonstration profile', 'specializations' => [$specialty], 'languages' => ['English', 'Amharic'], 'content' => '<p>This is a demonstration profile using a stock photograph. No qualifications or clinical experience are asserted. Add the actual professional’s biography and verified credentials through the administration panel.</p>']);
            $person->branches()->syncWithoutDetaching([$branch->id]);
        }
        $articleCategory = Category::firstOrCreate(['slug' => 'oral-health'], [...$common, 'title' => 'Oral health', 'type' => 'article']);
        $tag = Tag::firstOrCreate(['slug' => 'prevention'], ['title' => 'Prevention']);
        foreach ([['Small habits, healthier smiles', 'small-habits-healthier-smiles', '/images/care.webp', 'A few everyday habits can support oral health. Start with a conversation with your dentist.'], ['Your first visit, made a little easier', 'your-first-visit', '/images/clinic.webp', 'What to bring, what to ask, and how to feel more prepared for a dental visit.'], ['Caring for your child’s first teeth', 'caring-for-first-teeth', '/images/smile.webp', 'Make room for questions and a gentle introduction to dental care for your child.']] as $i => [$title,$slug,$image,$excerpt]) {
            $article = Article::firstOrCreate(['slug' => $slug], [...$common, 'title' => $title, 'excerpt' => $excerpt, 'image' => $image, 'image_alt' => 'Illustrative dental health photograph', 'category_id' => $articleCategory->id, 'published_at' => now()->subDays($i + 1), 'content' => '<p><strong>Demonstration educational content.</strong> Have this article reviewed and replaced by a qualified clinician before your clinic publishes it as guidance.</p><h2>Make time for a conversation</h2><p>Ask your dentist about a routine that suits your needs. Share concerns, sensitivities and questions so they can help you understand the available options.</p><h2>Prepare for your visit</h2><p>Write down your questions in advance. Ask the clinic about preparation and aftercare instructions specific to the procedure you are considering.</p><p>This information does not replace professional diagnosis or a consultation. For severe pain, swelling, bleeding or injury, seek timely care from a qualified professional.</p>']);
            $article->tags()->syncWithoutDetaching([$tag->id]);
        }
        foreach ([['What happens after I request a visit?', 'Your request is sent to the clinic team. They will contact you to agree a date and time. Submitting this form does not confirm an appointment.'], ['What should I bring to my first appointment?', 'Bring your questions and any documents the clinic asks you to provide. Contact the clinic directly for instructions relevant to your visit.'], ['Can I choose a particular dentist?', 'You can request an available professional at your chosen branch. The clinic will let you know whether they can accommodate your preference.'], ['Do you offer care for children?', 'The demonstration lists children’s dentistry as an example. A real clinic should publish only services it provides.'], ['What if I need urgent dental care?', 'Contact a local dental professional promptly for urgent symptoms. This website does not provide emergency triage or immediate clinical advice.']] as $i => [$q,$a]) {
            Faq::firstOrCreate(['slug' => 'question-'.($i + 1)], [...$common, 'title' => $q, 'excerpt' => $a, 'content' => '<p>'.e($a).'</p>', 'sort_order' => $i]);
        }
        foreach ([['A space to feel at ease', 'clinic-space', '/images/clinic.webp'], ['Thoughtful care, every visit', 'dental-consultation', '/images/hero.webp']] as [$title,$slug,$image]) {
            GalleryItem::firstOrCreate(['slug' => $slug], [...$common, 'title' => $title, 'image' => $image, 'image_alt' => 'Demonstration stock photograph of dental facilities', 'kind' => 'clinic']);
        }
        Testimonial::firstOrCreate(['slug' => 'sample-patient-story'], [...$common, 'title' => 'Sample patient story', 'excerpt' => '“The team took the time to explain each step and make me feel comfortable. It made all the difference.”', 'approved' => true, 'consent' => true, 'is_demo' => true]);
        $pages = [
            'about' => ['Care with a human touch', '<h2>Our story</h2><p>Selam Dental is a demonstration brand for a configurable dental clinic website. Replace this story with your clinic’s history, mission and approved photographs.</p><h2>Our philosophy</h2><p>A welcoming environment, clear communication and respect for patient choice. These are example brand values for review by the clinic.</p><h2>Facilities and technology</h2><p>Describe only equipment and facilities available at your real clinic. Upload current photographs and verified affiliations through the CMS.</p>'],
            'privacy' => ['Privacy notice', '<h2>Demonstration notice — review before launch</h2><p>This website collects the name, phone number, optional email and visit preferences you submit, together with your consent, to handle your inquiry. Please do not submit sensitive medical history or diagnosis information.</p><h2>How requests are used</h2><p>Authorized clinic staff access requests to arrange a visit or respond to a message. A request does not confirm an appointment. Optional email notifications may use the clinic’s configured mail provider.</p><h2>Retention and your choices</h2><p>The demonstration retention period is 365 days. Your clinic must review this period, identify its contact details and legal responsibilities, and provide a process for access, correction and deletion requests.</p><h2>Cookies</h2><p>Essential session and security cookies support language selection, form security and administrator authentication. No advertising or analytics cookies are enabled by default.</p>'],
            'terms' => ['Website terms', '<h2>Demonstration terms — review before launch</h2><p>This site provides general clinic information and an appointment request form. Information does not replace professional advice. The clinic must confirm an appointment separately. Contact your clinic for its actual service terms, cancellation policies and prices.</p>'],
            'cookies' => ['Cookie information', '<p>This site uses essential session and CSRF security cookies. Your language preference is stored in your session. Optional tracking is not enabled. Update this notice before adding third-party tracking.</p>'],
            'payment-information' => ['Payment & insurance', '<p>Contact the clinic to discuss accepted payment methods, consultation fees and insurance. This demonstration makes no claim about coverage or accepted providers. Treatment prices, when enabled, are indicative and should be confirmed with the clinic.</p>'],
            'careers' => ['Careers', '<p>There are no published vacancies in this demonstration installation. Contact the clinic through the inquiry form to ask about opportunities.</p>'],
        ];
        foreach ($pages as $slug => [$title,$content]) {
            Page::firstOrCreate(['slug' => $slug], [...$common, 'title' => $title, 'content' => $content, 'kind' => 'page']);
        }
        $this->call(ClinicFoundationSeeder::class);
    }
}
