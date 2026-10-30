<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function content(Blueprint $t): void
    {
        $t->id();
        $t->string('title');
        $t->string('slug')->unique();
        $t->text('excerpt')->nullable();
        $t->longText('content')->nullable();
        $t->string('image', 500)->nullable();
        $t->string('image_alt')->nullable();
        $t->json('translations')->nullable();
        $t->enum('status', ['draft', 'published'])->default('draft')->index();
        $t->boolean('featured')->default(false);
        $t->unsignedInteger('sort_order')->default(0)->index();
        $t->string('seo_title')->nullable();
        $t->text('seo_description')->nullable();
        $t->timestamps();
    }

    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->enum('role', ['super_admin', 'clinic_admin', 'content_editor', 'appointment_manager'])->default('content_editor');
            $t->boolean('active')->default(true)->index();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->longText('value');
            $t->timestamp('updated_at')->nullable();
        });
        Schema::create('categories', function (Blueprint $t) {
            $this->content($t);
            $t->enum('type', ['service', 'article', 'gallery'])->index();
        });
        Schema::create('branches', function (Blueprint $t) {
            $this->content($t);
            $t->string('address');
            $t->string('city')->default('Addis Ababa');
            $t->string('region')->nullable();
            $t->string('locality')->nullable();
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('appointment_email')->nullable();
            $t->string('map_url', 1000)->nullable();
            $t->json('hours')->nullable();
        });
        Schema::create('services', function (Blueprint $t) {
            $this->content($t);
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('icon')->default('tooth');
            $t->json('benefits')->nullable();
            $t->json('procedure')->nullable();
            $t->json('faq')->nullable();
            $t->decimal('price', 12, 2)->nullable();
        });
        Schema::create('professionals', function (Blueprint $t) {
            $this->content($t);
            $t->string('professional_title')->nullable();
            $t->enum('kind', ['dentist', 'specialist', 'hygienist', 'staff'])->default('dentist');
            foreach (['qualifications', 'specializations', 'languages', 'affiliations', 'links'] as $column) {
                $t->json($column)->nullable();
            }
            $t->unsignedSmallInteger('years_experience')->nullable();
        });
        Schema::create('branch_service', function (Blueprint $t) {
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_id')->constrained()->cascadeOnDelete();
            $t->primary(['branch_id', 'service_id']);
        });
        Schema::create('branch_professional', function (Blueprint $t) {
            $t->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $t->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $t->primary(['branch_id', 'professional_id']);
        });
        Schema::create('related_service', function (Blueprint $t) {
            $t->foreignId('service_id')->constrained()->cascadeOnDelete();
            $t->foreignId('related_id')->constrained('services')->cascadeOnDelete();
            $t->primary(['service_id', 'related_id']);
        });
        Schema::create('articles', function (Blueprint $t) {
            $this->content($t);
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->foreignId('author_id')->nullable()->constrained('professionals')->nullOnDelete();
            $t->timestamp('published_at')->nullable()->index();
        });
        Schema::create('tags', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('article_tag', function (Blueprint $t) {
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['article_id', 'tag_id']);
        });
        Schema::create('pages', function (Blueprint $t) {
            $this->content($t);
            $t->enum('kind', ['page', 'announcement', 'offer'])->default('page');
        });
        Schema::create('gallery_items', function (Blueprint $t) {
            $this->content($t);
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->enum('kind', ['clinic', 'equipment', 'team', 'treatment', 'before_after'])->default('clinic');
            $t->string('after_image', 500)->nullable();
            $t->boolean('consent')->default(false);
            $t->timestamp('consent_recorded_at')->nullable();
            $t->text('consent_reference')->nullable();
        });
        Schema::create('testimonials', function (Blueprint $t) {
            $this->content($t);
            $t->unsignedTinyInteger('rating')->nullable();
            $t->boolean('approved')->default(false);
            $t->boolean('consent')->default(false);
            $t->boolean('is_demo')->default(false);
        });
        Schema::create('faqs', fn (Blueprint $t) => $this->content($t));
        Schema::create('homepage_sections', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->string('title');
            $t->text('subtitle')->nullable();
            $t->json('translations')->nullable();
            $t->boolean('enabled')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('navigation_items', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('url', 500);
            $t->enum('location', ['header', 'footer'])->default('header');
            $t->json('translations')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('enabled')->default(true);
            $t->timestamps();
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('path')->unique();
            $t->string('original_path')->nullable();
            $t->string('mime');
            $t->unsignedInteger('size');
            $t->unsignedInteger('width');
            $t->unsignedInteger('height');
            $t->string('alt')->nullable();
            $t->string('category')->default('general');
            $t->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('professional_id')->nullable()->constrained()->nullOnDelete();
            $t->date('preferred_date')->index();
            $t->string('preferred_time', 5);
            $t->text('message')->nullable();
            $t->boolean('consent');
            $t->timestamp('consented_at');
            $t->text('consent_text');
            $t->enum('status', ['pending', 'confirmed', 'rescheduled', 'completed', 'cancelled', 'rejected'])->default('pending')->index();
            $t->text('internal_notes')->nullable();
            $t->enum('notification_status', ['disabled', 'sent', 'failed'])->default('disabled');
            $t->timestamps();
        });
        Schema::create('appointment_status_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('from_status')->nullable();
            $t->string('to_status');
            $t->text('note')->nullable();
            $t->timestamp('created_at');
        });
        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->string('subject');
            $t->text('message');
            $t->boolean('consent');
            $t->timestamp('consented_at');
            $t->text('consent_text');
            $t->enum('status', ['new', 'read', 'resolved'])->default('new')->index();
            $t->text('internal_notes')->nullable();
            $t->enum('notification_status', ['disabled', 'sent', 'failed'])->default('disabled');
            $t->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('resource');
            $t->unsignedBigInteger('resource_id')->nullable();
            $t->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'contact_messages', 'appointment_status_history', 'appointments', 'media', 'navigation_items', 'homepage_sections', 'faqs', 'testimonials', 'gallery_items', 'pages', 'article_tag', 'tags', 'articles', 'related_service', 'branch_professional', 'branch_service', 'professionals', 'services', 'branches', 'categories', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'active']));
    }
};
