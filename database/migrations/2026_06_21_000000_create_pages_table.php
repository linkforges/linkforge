<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Simple CMS pages (Terms, Privacy, Contact, or any custom page). Rendered at
 | /page/{slug}; the published ones flagged "show in footer" appear in the
 | public footer. Markdown body, same shape as blog posts / help articles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft|published
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
