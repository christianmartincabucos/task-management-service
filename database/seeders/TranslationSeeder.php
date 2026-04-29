<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationSeeder extends Seeder
{
    /**
     * Seed the translations table with test data.
     */
    public function run(int $count = 100000): void
    {
        $this->command?->info("Seeding {$count} translations...");

        // Create predefined tags
        $tags = $this->createTags();
        $tagIds = $tags->pluck('id')->toArray();

        $this->command?->info('Created ' . count($tagIds) . ' tags.');

        // Define locales and key patterns
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'nl', 'ja', 'zh', 'ko'];
        $prefixes = [
            'auth', 'nav', 'form', 'error', 'success', 'button',
            'label', 'placeholder', 'message', 'title', 'description',
            'validation', 'notification', 'email', 'page', 'menu',
            'footer', 'header', 'sidebar', 'modal', 'tooltip',
            'dashboard', 'settings', 'profile', 'account', 'payment',
        ];
        $suffixes = [
            'login', 'logout', 'register', 'submit', 'cancel', 'save',
            'delete', 'edit', 'create', 'update', 'search', 'filter',
            'confirm', 'back', 'next', 'previous', 'home', 'settings',
            'profile', 'welcome', 'goodbye', 'error', 'success', 'warning',
            'info', 'help', 'close', 'open', 'loading', 'retry',
            'name', 'email', 'password', 'phone', 'address', 'city',
        ];

        $chunkSize = 2000;
        $inserted = 0;
        $pivotData = [];

        $this->command?->info('Inserting translations in chunks...');

        while ($inserted < $count) {
            $batch = [];
            $currentChunkSize = min($chunkSize, $count - $inserted);

            for ($i = 0; $i < $currentChunkSize; $i++) {
                $prefix = $prefixes[array_rand($prefixes)];
                $suffix = $suffixes[array_rand($suffixes)];
                $locale = $locales[array_rand($locales)];
                $uniqueId = $inserted + $i + 1;

                $batch[] = [
                    'key' => "{$prefix}.{$suffix}.{$uniqueId}",
                    'locale' => $locale,
                    'content' => $this->generateContent($locale, $prefix, $suffix),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('translations')->insert($batch);

            // Assign random tags to translations in this batch
            $lastId = DB::table('translations')->max('id');
            $firstId = $lastId - $currentChunkSize + 1;

            $pivotBatch = [];
            for ($id = $firstId; $id <= $lastId; $id++) {
                // Each translation gets 1-3 random tags
                $randomTagIds = array_rand(array_flip($tagIds), rand(1, min(3, count($tagIds))));
                if (! is_array($randomTagIds)) {
                    $randomTagIds = [$randomTagIds];
                }

                foreach ($randomTagIds as $tagId) {
                    $pivotBatch[] = [
                        'tag_id' => $tagId,
                        'translation_id' => $id,
                    ];
                }
            }

            // Insert pivot data in chunks
            foreach (array_chunk($pivotBatch, 5000) as $pivotChunk) {
                DB::table('tag_translation')->insert($pivotChunk);
            }

            $inserted += $currentChunkSize;

            if ($this->command) {
                $progress = round(($inserted / $count) * 100);
                $this->command->info("Progress: {$inserted}/{$count} ({$progress}%)");
            }
        }

        $this->command?->info("Successfully seeded {$inserted} translations.");
    }

    /**
     * Create predefined tags.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tag>
     */
    private function createTags(): \Illuminate\Database\Eloquent\Collection
    {
        $tagNames = ['mobile', 'desktop', 'web', 'api', 'email', 'sms', 'push', 'admin', 'public', 'internal'];

        foreach ($tagNames as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }

        return Tag::all();
    }

    /**
     * Generate realistic content based on locale and context.
     */
    private function generateContent(string $locale, string $prefix, string $suffix): string
    {
        $templates = [
            'en' => [
                'auth.login' => 'Please log in to continue',
                'auth.register' => 'Create your account',
                'button.submit' => 'Submit',
                'button.cancel' => 'Cancel',
                'error.required' => 'This field is required',
                'success.save' => 'Changes saved successfully',
                'nav.home' => 'Home',
            ],
            'fr' => [
                'auth.login' => 'Veuillez vous connecter pour continuer',
                'auth.register' => 'Créez votre compte',
                'button.submit' => 'Soumettre',
                'button.cancel' => 'Annuler',
                'error.required' => 'Ce champ est obligatoire',
                'success.save' => 'Modifications enregistrées avec succès',
                'nav.home' => 'Accueil',
            ],
            'es' => [
                'auth.login' => 'Por favor inicie sesión para continuar',
                'auth.register' => 'Cree su cuenta',
                'button.submit' => 'Enviar',
                'button.cancel' => 'Cancelar',
                'error.required' => 'Este campo es obligatorio',
                'success.save' => 'Cambios guardados exitosamente',
                'nav.home' => 'Inicio',
            ],
        ];

        $key = "{$prefix}.{$suffix}";

        if (isset($templates[$locale][$key])) {
            return $templates[$locale][$key];
        }

        // Generate a plausible content string for other combinations
        $words = [
            'en' => ['the', 'is', 'a', 'to', 'for', 'your', 'please', 'enter', 'select', 'click', 'view', 'update', 'save'],
            'fr' => ['le', 'est', 'un', 'pour', 'votre', 'veuillez', 'entrer', 'sélectionner', 'cliquer', 'voir', 'mettre', 'enregistrer'],
            'es' => ['el', 'es', 'un', 'para', 'su', 'por favor', 'ingresar', 'seleccionar', 'hacer clic', 'ver', 'actualizar', 'guardar'],
            'de' => ['der', 'ist', 'ein', 'für', 'Ihr', 'bitte', 'eingeben', 'auswählen', 'klicken', 'anzeigen', 'aktualisieren', 'speichern'],
            'it' => ['il', 'è', 'un', 'per', 'il tuo', 'per favore', 'inserire', 'selezionare', 'cliccare', 'visualizzare', 'aggiornare', 'salvare'],
            'pt' => ['o', 'é', 'um', 'para', 'seu', 'por favor', 'inserir', 'selecionar', 'clicar', 'visualizar', 'atualizar', 'salvar'],
            'nl' => ['de', 'is', 'een', 'voor', 'uw', 'alstublieft', 'invoeren', 'selecteren', 'klikken', 'bekijken', 'bijwerken', 'opslaan'],
            'ja' => ['の', 'は', 'を', 'に', 'する', 'してください', '入力', '選択', 'クリック', '表示', '更新', '保存'],
            'zh' => ['的', '是', '一个', '为', '您的', '请', '输入', '选择', '点击', '查看', '更新', '保存'],
            'ko' => ['의', '은', '하나', '위한', '당신의', '하세요', '입력', '선택', '클릭', '보기', '업데이트', '저장'],
        ];

        $langWords = $words[$locale] ?? $words['en'];
        $numWords = rand(3, 8);
        $sentence = [];

        for ($i = 0; $i < $numWords; $i++) {
            $sentence[] = $langWords[array_rand($langWords)];
        }

        return ucfirst(implode(' ', $sentence));
    }
}
