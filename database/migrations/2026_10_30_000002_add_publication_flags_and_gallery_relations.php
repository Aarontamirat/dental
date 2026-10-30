<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['services', 'professionals', 'branches', 'articles', 'pages', 'gallery_items', 'faqs', 'categories'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->boolean('is_demo')->default(false)->index());
        }
        Schema::table('gallery_items', function (Blueprint $t) {
            $t->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('service_id');
            $t->dropConstrainedForeignId('branch_id');
        });
        foreach (['services', 'professionals', 'branches', 'articles', 'pages', 'gallery_items', 'faqs', 'categories'] as $name) {
            Schema::table($name, fn (Blueprint $t) => $t->dropColumn('is_demo'));
        }
    }
};
