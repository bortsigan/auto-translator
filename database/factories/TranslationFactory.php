<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    private static array $pool = [
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


    public static function resetPool(): void
    {
        static::$pool = (new \ReflectionClass(static::class))
            ->getDefaultProperties()['pool'];
    }

    public function forLocale(string $code): static
    {
        return $this->state(function (array $attributes) use ($code): array {

            if ($code === 'en') {
                return [];
            }

            return ['content' => $code . ' ' . $attributes['content']];
        });
    }

    public function definition(): array
    {
        if (static::$pool !== []) {
            $key     = array_key_first(static::$pool);
            $content = static::$pool[$key];
            unset(static::$pool[$key]);
        } else {
            $key     = fake()->unique()->slug(3);
            $content = fake()->sentence();
        }

        return [
            'language_id' => Language::factory(),
            'key'         => $key,
            'content'     => $content,
        ];
    }
}
