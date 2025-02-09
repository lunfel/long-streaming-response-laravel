<?php

namespace App\Jobs;

use App\ArtisanCommandRun;
use App\Enums\ArtisanCommandRunStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Redis;
use Symfony\Component\Console\Output\StreamOutput;

class RunArtisanCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $command;
    private ArtisanCommandRun $artisanRun;

    /**
     * Create a new job instance.
     */
    public function __construct(string $command, ArtisanCommandRun $artisanRun)
    {
        $this->command = $command;
        $this->artisanRun = $artisanRun;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->artisanRun->setStatus(ArtisanCommandRunStatus::Running);

        $streamContext = stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    return \Illuminate\Support\Facades\Redis::connection('cache')
                        ->client();
                }
            ]
        ]);

        $handle = $this->artisanRun->getStream('a', $streamContext);

        $result = Artisan::call($this->command, outputBuffer: new StreamOutput($handle));

        if ($result === 0) {
            $this->artisanRun->setStatus(ArtisanCommandRunStatus::Completed, 3600);
        } else {
            $this->artisanRun->setStatus(ArtisanCommandRunStatus::Failed, 3600);
        }
    }

    public function failed(?\Throwable $e): void
    {
        Cache::put(
            ArtisanCommandRun::getErrorCacheKey($this->artisanRun),
            $e->getMessage(),
            3600
        );

        Cache::put(
            ArtisanCommandRun::getErrorCacheKey($this->artisanRun),
            ArtisanCommandRunStatus::Failed->value,
            3600
        );
    }
}
