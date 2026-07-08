<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ark-asa-manager')]
#[Description('Command description')]
class ArkAsaManager extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Ark ASA Manager command executed successfully');

        while (true) {
            $this->info('Ark ASA Manager loop');
            sleep(10);
        }
    }
}
