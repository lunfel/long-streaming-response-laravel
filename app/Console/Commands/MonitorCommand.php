<?php

namespace App\Console\Commands;

use App\Output\RedisStreamWrapper;
use Illuminate\Console\Command;

class MonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $redisHandle = fopen('redis://artisan', 'r');

        $endToken = 'END OF OUTPUT LOG';

        do {
            $chunk = stream_get_contents($redisHandle);

            if (empty($chunk)) {
                $this->getOutput()->writeln("Nothing received");
            } else {
                $this->getOutput()->write("Received: " . $chunk);
            }

            sleep(1);
        } while (substr($chunk, -mb_strlen($endToken)) !== $endToken);

        fclose($redisHandle);
    }
}
