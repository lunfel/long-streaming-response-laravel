<?php

namespace App\Output;

use Redis;

class RedisStreamWrapper
{
    /* Properties */

    /** @var resource $context */
    public $context;

    private string $path;

    private int $remote_write_position = 0;
    private $internal_stream;

    /* Methods */
    private string $mode;
    private int $options;
    private Redis $redis;
    private string $last_id = "0";

    public function __construct() {

    }

    public function dir_closedir(): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }
    public function dir_opendir(string $path, int $options): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function dir_readdir(): string
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function dir_rewinddir(): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function rename(string $path_from, string $path_to): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function rmdir(string $path, int $options): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    /**
     * @return resource
     */
    public function stream_cast(int $cast_as)
    {
        return $this->internal_stream;
    }

    public function stream_close(): void
    {
        $options = stream_context_get_options($this->context);

        if (is_callable($callback = $options['redis']['events']['before_stream_close'])) {
            $callback($this->redis, $this->getRedisKey());
        }

        if ($this->redis) {
            $this->redis->close();
        }

        fclose($this->internal_stream);
    }

    public function stream_eof(): bool
    {
        $feof = feof($this->internal_stream);

        return $feof;
    }

    public function stream_flush(): bool
    {
        $initialPosition = ftell($this->internal_stream);

        fseek($this->internal_stream, $this->remote_write_position);

        $writeBuffer = [];
        while (!feof($this->internal_stream)) {
            $writeBuffer[] = fread($this->internal_stream, 8192);
        }

        try {
            $this->redis->xadd($this->getRedisKey(), '*', $writeBuffer);

            fseek($this->internal_stream, $initialPosition);
            $this->remote_write_position = ftell($this->internal_stream);

            return true;
        } catch (\RedisException $e) {
            return false;
        }
    }

    public function stream_lock(int $operation): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function stream_metadata(string $path, int $option, mixed $value): bool
    {
        // If option is not implemented, false should be returned (https://www.php.net/manual/en/streamwrapper.stream-metadata.php)
        return false;
    }

    public function stream_open(
        string $path,
        string $mode,
        int $options,
        ?string &$opened_path
    ): bool
    {
        $this->path = $path;
        $this->mode = $mode;
        $this->options = $options;

        $this->initializeRedisConnection();

        $this->internal_stream = fopen("php://temp", "r+");

        return true;
    }

    public function stream_read(int $count): string|false
    {
        return $this->receiveMessages();
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return fseek($this->internal_stream, $offset, $whence);
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function stream_stat(): array|false
    {
        return fstat($this->internal_stream);
    }

    public function stream_tell(): int
    {
        return ftell($this->internal_stream);
    }

    public function stream_truncate(int $new_size): bool
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function stream_write(string $data): int
    {
        return fwrite($this->internal_stream, $data);
    }

    public function unlink(string $path): bool
    {
        $this->initializeRedisConnection();

        return (bool) $this->redis->del($this->getKeyFromPath($path));
    }

    public function url_stat(string $path, int $flags): array|false
    {
        throw new \RuntimeException('Method not supported for ' . __CLASS__);
    }

    public function __destruct()
    {
        $this->closeRedisConnection();
    }

    private function getRedisKey(): string
    {
        $options = stream_context_get_options($this->context);

        $prefix = $options['redis']['configuration']['key_prefix'] ?? '';

        return $prefix . $this->getKeyFromPath($this->path);
    }

    private function getKeyFromPath(string $path): string
    {
        return mb_substr($path, strpos($path, '://') + strlen('://'));
    }

    /**
     * @return void
     */
    protected function initializeRedisConnection(): void
    {
        if (! isset($this->redis)) {
            $options = stream_context_get_options($this->context);

            $factory = $options['redis']['client'];
            $this->redis = is_callable($factory) ? $factory() : new Redis();
        }
    }

    /**
     * @return void
     */
    protected function closeRedisConnection(): void
    {
        if (isset($this->redis) && $this->redis->isConnected()) {
            $this->redis->close();
        }
    }

    /**
     * @return string
     */
    protected function receiveMessages(): string
    {
        $currentPosition = ftell($this->internal_stream);

        $messages = $this->redis->xread([
            $this->getRedisKey() => $this->last_id
        ]);

        $data = "";
        if (is_array($messages) && array_key_exists($this->getRedisKey(), $messages)) {
            foreach ($messages[$this->getRedisKey()] as $entryName => $fields) {
                foreach ($fields as $fieldName => $value) {
                    $this->last_id = $entryName;

                    $data .= $value;
                }
            }
        }

        fwrite($this->internal_stream, $data);

        fseek($this->internal_stream, $currentPosition);

        return $data;
    }
}
