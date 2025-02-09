<?php

namespace App\Http\Controllers;

use App\ArtisanCommandRun;
use App\Enums\ArtisanCommandRunStatus;
use App\Http\Requests\ArtisanCommandRequest;
use App\Jobs\RunArtisanCommand;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Redis;

class ExplorationController extends Controller
{
    public function __invoke()
    {
        //
//        $command = new \Fiber(function () use ($output) {
//            Cache::put('command-status', 'running');
//
//            Artisan::call('app:long-running-command', outputBuffer: $output);
//
//            Cache::put('command-status', 'complete');
//        });

        return response()->stream(function (): void {
            $context = stream_context_set_default([
                'redis' => [
                    'client' => function (): Redis {
                        return RedisFacade::client();
                    },
                    'configuration' => [
                        // prefix to add to stream key
                        // For example, with prefix set to "mystreams:"
                        // The path redis://my/important/file.log would be
                        // translated into redis key "mystreams:my/important/file.log"
                        'key_prefix' => 'streams:'
                    ],
                    'events' => [
                        'before_stream_close' => function (Redis $redis, string $redisKeyToStream) {
                            // You can do whatever you want before closing the stream. You
                            // can set an expiration time on the key or delete the stream
                            //
                            // Ex: Expire the key after 60 seconds
                            // $redis->expire($redisKeyToStream, 60);
                        }
                    ]
                ],
            ]);

            $redisHandle = fopen('redis://response', 'r', context: $context);
            $endToken = 'END OF OUTPUT LOG';

            while (true) {
                $data = stream_get_contents($redisHandle);

                $hasEndToken = substr($data, -mb_strlen($endToken)) === $endToken;

                if ($hasEndToken) {
                    echo substr($data, 0, strlen($data) - strlen($endToken));

                    break;
                } else {
                    echo $data;
                }

                ob_flush();
                flush();

                sleep(1);
            }
        });
    }

    public function run(ArtisanCommandRequest $request)
    {
        $artisanRun = new ArtisanCommandRun();

        $artisanRun->setStatus(ArtisanCommandRunStatus::Queued);

        RunArtisanCommand::dispatch(
            $request->getCommand(),
            $artisanRun
        );

        return response()->json([
            'artisanRunId' => $artisanRun->__toString()
        ], Response::HTTP_ACCEPTED);
    }

    public function tail(string $artisanRunId, Request $request)
    {
        $artisanRun = new ArtisanCommandRun($artisanRunId);

        $streamContext = stream_context_create([
            'redis' => [
                'client' => function (): Redis {
                    return \Illuminate\Support\Facades\Redis::connection('cache')
                        ->client();
                }
            ]
        ]);

        $handle = $artisanRun->getStream('r', $streamContext);
    }

    public function status(string $artisanRunId)
    {
        $artisanRun = new ArtisanCommandRun($artisanRunId);

        if (!$artisanRun->exists()) {
            abort(404);
        }

        return response()->json([
            'status' => $artisanRun->getStatus()
        ]);
    }
}
