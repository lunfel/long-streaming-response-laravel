<?php

namespace App;

use App\Enums\ArtisanCommandRunStatus;
use Illuminate\Support\Facades\Cache;

class ArtisanCommandRun
{
    private string $artisanRunId;

    public function __construct(string $artisanRunId = null)
    {
        $this->artisanRunId = $artisanRunId ?? md5(uniqid(more_entropy: true));
    }

    public function getStream(string $mode, $streamContext)
    {
        return fopen(sprintf('redis://streams:artisan:%s', $this->artisanRunId), $mode, context: $streamContext);
    }

    public function getStatusCacheKey(): string
    {
        return sprintf('run-artisan-command:%s:status', $this->artisanRunId);
    }

    public function getErrorCacheKey(): string
    {
        return sprintf('run-artisan-command:%s:error', $this->artisanRunId);
    }

    public function setStatus(ArtisanCommandRunStatus $status, ?int $ttlSeconds = null): void
    {
        if (is_null($ttlSeconds)) {
            Cache::forever($this->getStatusCacheKey(), $status->value);
        } else {
            Cache::put($this->getStatusCacheKey(), $status->value, $ttlSeconds);
        }
    }

    public function getStatus(): ?ArtisanCommandRunStatus
    {
        return ArtisanCommandRunStatus::tryFrom(Cache::get($this->getStatusCacheKey()));
    }

    public function exists(): bool
    {
        return $this->getStatus() !== null;
    }

    public function __toString(): string
    {
        return $this->artisanRunId;
    }
}