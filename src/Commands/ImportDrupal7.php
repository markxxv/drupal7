<?php

namespace Knowfox\Drupal7\Commands;

use Illuminate\Console\Command;

class ImportDrupal7 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drupal7:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Drupal7 nodes from a MySQL database';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->info("Importing from database " . env('DB_D7_DATABASE') . '...');
        $this->info('Done.');
    }
}
