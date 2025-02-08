<?php

namespace App\Console\Commands;

use App\Output\RedisStreamWrapper;
use Illuminate\Console\Command;

class LongRunningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:long-running-command';

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
        $context = stream_context_create([
            'credentials' => [
                'username' => 'happy',
                'password' => 'secret'
            ]
        ]);

//        unlink('redis://test');
//
//        $redisHandle = fopen('redis://test', 'a', context: $context);
//
//        $fakeDataHandle = fopen('data/output.log', 'r');
//
//        while (($line = fgets($fakeDataHandle)) !== false) {
//            fwrite($redisHandle, sprintf('[%s] %s', date('Y-m-d H:i:s'), $line));
//
//            fflush($redisHandle);
//
//            $this->getOutput()->write("Writing to redis: " . $line);
//
//            // sleep(5);
//        }
//
//        fclose($redisHandle);


        $fakeDataHandle = fopen('data/output.log', 'r');

        while (($line = fgets($fakeDataHandle)) !== false) {
            $this->getOutput()->write(sprintf('[%s] %s', date('Y-m-d H:i:s'), $line));

            sleep(3);
        }

        fclose($fakeDataHandle);
    }
}
