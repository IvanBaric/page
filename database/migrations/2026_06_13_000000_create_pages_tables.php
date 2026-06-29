<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('pages.tables.pages', 'pages'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->string('slug');
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('content')->nullable();
            $table->string('status')->index();
            $table->string('template')->nullable()->index();
            $table->boolean('is_home')->default(false)->index();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['team_id', 'slug']);
        });

        Schema::create(config('pages.tables.sections', 'sections'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->string('slug');
            $table->foreignId('page_id')->constrained(config('pages.tables.pages', 'pages'))->cascadeOnDelete();
            $table->string('type')->index();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            $table->string('image')->nullable();
            $table->json('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['team_id', 'slug']);
        });

        Schema::create(config('pages.tables.section_items', 'section_items'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->string('slug');
            $table->foreignId('section_id')->constrained(config('pages.tables.sections', 'sections'))->cascadeOnDelete();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('description')->nullable();
            $table->json('content')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('url')->nullable();
            $table->json('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['team_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('pages.tables.section_items', 'section_items'));
        Schema::dropIfExists(config('pages.tables.sections', 'sections'));
        Schema::dropIfExists(config('pages.tables.pages', 'pages'));
    }
};
