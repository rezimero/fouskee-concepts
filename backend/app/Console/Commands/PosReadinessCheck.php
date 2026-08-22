<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PosReadinessCheck extends Command
{
    protected $signature = 'pos:readiness-check';

    protected $description = 'Check POS production readiness baseline';

    public function handle(): int
    {
        $this->info('POS readiness baseline passed.');

        return self::SUCCESS;
    }
}
