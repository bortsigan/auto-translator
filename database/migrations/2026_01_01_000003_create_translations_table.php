<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('language_id')
                ->constrained('languages')
                ->cascadeOnDelete();
            $table->string('key', 191);
            $table->text('content');
            $table->timestamps();

            $table->unique(['language_id', 'key'], 'translations_lang_key_unique');
            $table->index('key', 'translations_key_idx');
            $table->index(['language_id', 'updated_at'], 'translations_lang_updated_idx');
        });

        DB::statement('ALTER TABLE translations ADD FULLTEXT translations_content_ft (content)');
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
