<?php

namespace Webkul\Pos\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Pos\Database\Seeders\DatabaseSeeder;
use Webkul\Pos\Events\ComposerEvents;
use Webkul\Pos\Providers\PosServiceProvider;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install & configure the POS extension';

    /**
     * Install & configure the POS extension
     */
    public function handle()
    {
        $this->call('migrate', [
            '--path' => 'packages/Webkul/Pos/src/Database/Migrations',
        ]);

        $this->call('db:seed', [
            '--class' => DatabaseSeeder::class,
        ]);

        $this->callSilently('vendor:publish', [
            '--provider' => PosServiceProvider::class,
            '--force'    => true,
        ]);

        $this->call('optimize:clear');

        ComposerEvents::postCreateProject();

        $this->components->info('🎉 Pos extension installed successfully!');
    }
}
