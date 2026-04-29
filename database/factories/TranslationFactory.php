<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Translation>
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    /**
     * Available locales for generating translations.
     *
     * @var list<string>
     */
    private static array $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'ja', 'zh', 'ko'];

    /**
     * Sample translation key prefixes for realistic data.
     *
     * @var list<string>
     */
    private static array $keyPrefixes = [
        'auth', 'nav', 'form', 'error', 'success', 'button',
        'label', 'placeholder', 'message', 'title', 'description',
        'validation', 'notification', 'email', 'page', 'menu',
        'footer', 'header', 'sidebar', 'modal', 'tooltip',
    ];

    /**
     * Sample translation key suffixes for realistic data.
     *
     * @var list<string>
     */
    private static array $keySuffixes = [
        'login', 'logout', 'register', 'submit', 'cancel', 'save',
        'delete', 'edit', 'create', 'update', 'search', 'filter',
        'confirm', 'back', 'next', 'previous', 'home', 'settings',
        'profile', 'welcome', 'goodbye', 'error', 'success', 'warning',
        'info', 'help', 'close', 'open', 'loading', 'retry',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = $this->faker->randomElement(self::$keyPrefixes);
        $suffix = $this->faker->randomElement(self::$keySuffixes);

        return [
            'key' => $prefix . '.' . $suffix . '.' . $this->faker->unique()->numberBetween(1, 999999),
            'locale' => $this->faker->randomElement(self::$locales),
            'content' => $this->faker->sentence(rand(3, 10)),
        ];
    }

    /**
     * Set the locale for the translation.
     */
    public function locale(string $locale): static
    {
        return $this->state(fn () => ['locale' => $locale]);
    }

    /**
     * Set the key for the translation.
     */
    public function key(string $key): static
    {
        return $this->state(fn () => ['key' => $key]);
    }
}
