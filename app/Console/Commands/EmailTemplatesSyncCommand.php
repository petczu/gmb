<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\Templates\EmailTemplateCatalog;
use App\Models\EmailTemplate;
use Illuminate\Console\Command;

/**
 * Seeds the email_templates table from the catalogue defaults. New rows are
 * created for any missing key+locale; existing rows are left untouched so admin
 * edits survive. Pass --force to reset every row back to the shipped default.
 */
class EmailTemplatesSyncCommand extends Command
{
    protected $signature = 'email-templates:sync {--force : Overwrite existing rows with catalogue defaults} {--key= : Limit the sync to one template key}';

    protected $description = 'Seed editable email templates from the catalogue defaults';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $created = 0;
        $reset = 0;

        $onlyKey = (string) $this->option('key');

        foreach (EmailTemplateCatalog::keys() as $key) {
            if ($onlyKey !== '' && $key !== $onlyKey) {
                continue;
            }

            foreach (EmailTemplateCatalog::locales() as $locale) {
                $defaults = [
                    'subject' => EmailTemplateCatalog::defaultSubject($key, $locale),
                    'body' => EmailTemplateCatalog::defaultBody($key, $locale),
                ];

                $existing = EmailTemplate::query()->where('key', $key)->where('locale', $locale)->first();

                if ($existing === null) {
                    EmailTemplate::query()->create(['key' => $key, 'locale' => $locale] + $defaults);
                    $created++;
                } elseif ($force) {
                    $existing->update($defaults);
                    $reset++;
                }
            }
        }

        $this->info("Email templates synced: {$created} created, {$reset} reset.");

        return self::SUCCESS;
    }
}
