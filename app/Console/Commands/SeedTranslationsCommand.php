<?php

namespace App\Console\Commands;

use Database\Seeders\TranslationSeeder;
use Illuminate\Console\Command;

class SeedTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:seed {--count=100000 : Number of translations to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with translation records for performance testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');

        if ($count < 1) {
            $this->error('Count must be a positive integer.');

            return self::FAILURE;
        }

        $this->info("Starting translation seeding with {$count} records...");

        $startTime = microtime(true);

        $seeder = new TranslationSeeder();
        $seeder->setCommand($this);
        $seeder->run($count);

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info("Completed in {$elapsed} seconds.");

        return self::SUCCESS;
    }
}
