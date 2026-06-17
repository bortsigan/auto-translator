<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class SeedTranslationsCommand extends Command
{
    private const BASE_TRANSLATIONS = [
        'home.title'                  => 'Welcome to our platform',
        'home.subtitle'               => 'Manage your content in one place',
        'home.cta'                    => 'Get started for free',

        'nav.home'                    => 'Home',
        'nav.about'                   => 'About',
        'nav.contact'                 => 'Contact',
        'nav.pricing'                 => 'Pricing',
        'nav.dashboard'               => 'Dashboard',
        'nav.logout'                  => 'Log out',

        'auth.login.title'            => 'Sign in to your account',
        'auth.login.email_label'      => 'Email address',
        'auth.login.password_label'   => 'Password',
        'auth.login.submit'           => 'Sign in',
        'auth.login.forgot_password'  => 'Forgot your password?',
        'auth.register.title'         => 'Create an account',
        'auth.register.submit'        => 'Create account',
        'auth.register.name_label'    => 'Full name',

        'button.save'                 => 'Save',
        'button.cancel'               => 'Cancel',
        'button.delete'               => 'Delete',
        'button.edit'                 => 'Edit',
        'button.confirm'              => 'Confirm',
        'button.back'                 => 'Go back',
        'button.submit'               => 'Submit',
        'button.close'                => 'Close',

        'errors.404.title'            => 'Page not found',
        'errors.404.message'          => 'The page you are looking for does not exist.',
        'errors.500.title'            => 'Something went wrong',
        'errors.500.message'          => 'An unexpected error occurred. Please try again later.',
        'errors.403.title'            => 'Access denied',
        'errors.403.message'          => 'You do not have permission to view this page.',

        'dashboard.welcome'           => 'Welcome back, :name',
        'dashboard.stats.users'       => 'Total users',
        'dashboard.stats.revenue'     => 'Revenue this month',
        'dashboard.stats.orders'      => 'New orders',

        'form.required'               => 'This field is required.',
        'form.invalid_email'          => 'Please enter a valid email address.',
        'form.min_length'             => 'Must be at least :min characters.',
        'form.max_length'             => 'Cannot exceed :max characters.',
        'form.success'                => 'Your changes have been saved.',

        'notifications.new_message'   => 'You have a new message',
        'notifications.order_shipped' => 'Your order has been shipped',
        'notifications.welcome'       => 'Welcome to :app!',

        'footer.rights'               => 'All rights reserved.',
        'footer.privacy'              => 'Privacy Policy',
        'footer.terms'                => 'Terms of Service',
        'footer.contact'              => 'Contact us',

        'pagination.previous'         => 'Previous',
        'pagination.next'             => 'Next',
        'pagination.showing'          => 'Showing :from to :to of :total results',
    ];

    protected $signature = 'translations:seed
        {--count=100000 : Number of translations to insert}
        {--locales=en,fr,es,de : Comma-separated locale codes to seed}
        {--tags=mobile,desktop,web : Comma-separated tag names to attach}
        {--chunk=2000 : Insert chunk size}';

    protected $description = 'Seed the database with N translations for scalability testing.';

    public function handle(): int
    {
        $count   = (int) $this->option('count');
        $chunk   = max(100, (int) $this->option('chunk'));
        $locales = $this->splitOption((string) $this->option('locales'));
        $tagNames = $this->splitOption((string) $this->option('tags'));

        if ($locales === [] || $count <= 0) {
            $this->error('count must be > 0 and at least one locale must be provided.');
            return self::FAILURE;
        }

        $languages = $this->ensureLanguages($locales);
        $tagIds      = $this->ensureTags($tagNames);

        $this->info("Seeding {$count} translations across ".count($languages).' locales...');
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $now = now();
        $inserted = 0;
        $baseIndex = 0;
        $basePairs = $this->basePairsForLocales($languages);
        $baseCount = count($basePairs);

        while ($inserted < $count) {
            $batch = min($chunk, $count - $inserted);
            $rows = [];
            $pivot = [];

            for ($i = 0; $i < $batch; $i++) {
                $n = $inserted + $i;
                if ($baseIndex < $baseCount) {
                    $rows[] = $basePairs[$baseIndex] + [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $baseIndex++;
                    continue;
                }

                $lang = $languages[$n % count($languages)];
                $rows[] = [
                    'language_id' => $lang['id'],
                    'key'         => $this->randomKey($n),
                    'content'     => $this->randomSentence(),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            DB::transaction(function () use ($rows, $tagIds, &$pivot): void {
                DB::table('translations')->insert($rows);

                if ($tagIds === []) {
                    return;
                }

                $keys = array_column($rows, 'key');
                $languageIds = array_values(array_unique(array_column($rows, 'language_id')));
                $idMap = DB::table('translations')
                    ->select(['id', 'language_id', 'key'])
                    ->whereIn('key', $keys)
                    ->whereIn('language_id', $languageIds)
                    ->get()
                    ->mapWithKeys(static fn (object $row): array => [
                        $row->language_id.'|'.$row->key => (int) $row->id,
                    ]);

                foreach ($rows as $row) {
                    $mapKey = $row['language_id'].'|'.$row['key'];
                    $id = $idMap[$mapKey] ?? null;
                    if ($id === null) {
                        continue;
                    }
                    $tagSubset = array_slice($tagIds, 0, ($id % count($tagIds)) + 1);
                    foreach ($tagSubset as $tagId) {
                        $pivot[] = ['translation_id' => $id, 'tag_id' => $tagId];
                    }
                }

                if ($pivot !== []) {
                    foreach (array_chunk($pivot, 5000) as $chunkPivot) {
                        DB::table('tag_translation')->insertOrIgnore($chunkPivot);
                    }
                }
            });

            $inserted += $batch;
            $bar->advance($batch);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Inserted {$inserted} translations.");

        return self::SUCCESS;
    }

    private function ensureLanguages(array $codes): array
    {
        $names = [
            'en' => 'English', 'fr' => 'French', 'es' => 'Spanish',
            'de' => 'German',  'it' => 'Italian', 'pt' => 'Portuguese',
        ];

        foreach ($codes as $code) {
            Language::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $names[$code] ?? strtoupper($code), 'is_active' => true],
            );
        }

        return Language::query()
            ->whereIn('code', $codes)
            ->get(['id', 'code'])
            ->map(static fn (Language $language): array => [
                'id' => $language->id,
                'code' => $language->code,
            ])
            ->all();
    }


    private function ensureTags(array $names): array
    {
        foreach ($names as $name) {
            Tag::query()->firstOrCreate(['name' => $name]);
        }

        return $names === []
            ? []
            : Tag::query()->whereIn('name', $names)->pluck('id')->all();
    }


    private function splitOption(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }


    private function randomKey(int $n): string
    {
        static $namespaces = [
            'auth.login', 'auth.register', 'auth.password',
            'nav', 'nav.menu', 'nav.breadcrumb',
            'home', 'home.hero', 'home.features',
            'button', 'form', 'form.validation',
            'errors.404', 'errors.500', 'errors.403',
            'dashboard', 'dashboard.stats', 'dashboard.charts',
            'notifications', 'notifications.email', 'notifications.push',
            'footer', 'pagination', 'table', 'modal', 'toast',
            'profile', 'settings', 'billing', 'invoice',
            'product', 'product.detail', 'product.list',
            'cart', 'checkout', 'order', 'order.status',
            'search', 'filter', 'sort',
        ];

        static $terms = [
            'title', 'subtitle', 'label', 'placeholder', 'hint',
            'submit', 'cancel', 'confirm', 'back', 'close',
            'save', 'edit', 'delete', 'create', 'update', 'view',
            'message', 'description', 'heading', 'cta',
            'empty_state', 'loading', 'error', 'success',
        ];

        $ns   = $namespaces[$n % count($namespaces)];
        $term = $terms[intdiv($n, count($namespaces)) % count($terms)];

        return sprintf('%s.%s.%05d', $ns, $term, $n);
    }

    private function randomSentence(): string
    {
        static $words = [
            'welcome', 'hello', 'goodbye', 'please', 'thank', 'you', 'login', 'logout',
            'submit', 'cancel', 'delete', 'create', 'update', 'save', 'edit', 'view',
            'translation', 'message', 'error', 'success', 'warning', 'notice',
        ];
        $picked = array_rand($words, 6);
        $parts = [];
        foreach ($picked as $idx) {
            $parts[] = $words[$idx];
        }

        return ucfirst(implode(' ', $parts)).'.';
    }

    
    private function basePairsForLocales(array $languages): array
    {
        $rows = [];

        foreach ($languages as $lang) {
            foreach (self::BASE_TRANSLATIONS as $key => $content) {
                $rows[] = [
                    'language_id' => $lang['id'],
                    'key' => $key,
                    'content' => $this->localizeBaseContent($content, $lang['code']),
                ];
            }
        }

        return $rows;
    }

    private function localizeBaseContent(string $content, string $code): string
    {
        if ($code === 'en') {
            return $content;
        }

        return strtoupper($code).' '.$content;
    }
}
