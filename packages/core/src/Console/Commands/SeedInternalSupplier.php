<?php

namespace Lunar\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Database\Seeders\InternalSupplierSeeder;

class SeedInternalSupplier extends Command
{
    protected $signature = 'lunar:seed-internal-supplier';
    protected $description = 'Seed the internal fulfillment supplier';

    public function handle()
    {
        $this->info('Seeding internal supplier...');

        $seeder = new InternalSupplierSeeder();
        $seeder->run();

        $this->info('✓ Internal supplier seeded successfully');
        return 0;
    }
}
