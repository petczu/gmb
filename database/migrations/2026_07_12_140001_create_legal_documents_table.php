<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central: versioned, admin-editable legal documents plus per-user Terms
 * acceptance. Seeds the Terms (EN/DE) as version 1 from the lang files and
 * grandfathers every existing user onto it so nobody is re-prompted at
 * deploy; only future "publish new version" bumps trigger re-acceptance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->string('locale', 5);
            $table->longText('body');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['key', 'locale']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('terms_version')->nullable()->after('locale');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_version');
        });

        foreach (['en', 'de'] as $locale) {
            DB::table('legal_documents')->insert([
                'key' => 'terms',
                'locale' => $locale,
                'body' => $this->termsMarkdown($locale),
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')->update(['terms_version' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['terms_version', 'terms_accepted_at']);
        });
    }

    /** The current static Terms (lang/legal.php) converted to markdown. */
    private function termsMarkdown(string $locale): string
    {
        $sections = trans('legal.terms.sections', [], $locale);

        if (! is_array($sections) || $sections === []) {
            return '_No content yet._';
        }

        $md = [];
        foreach ($sections as $section) {
            if (! empty($section['h'])) {
                $md[] = '## '.$section['h'];
            }
            $md[] = (string) ($section['p'] ?? '');
        }

        return implode("\n\n", $md);
    }
};
