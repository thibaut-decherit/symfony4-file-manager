<?php

namespace App\Service\FileChunkUploaderService;

use InvalidArgumentException;

/**
 * Class FileChunkUploaderResponse
 * @package App\Service\ChunkUploaderService
 */
class FileChunkUploaderResponse
{
    const STATUS_CHUNK_UPLOAD_SUCCESS = 'chunk upload success';
    const STATUS_FILE_TOO_LARGE = 'file too large';
    const STATUS_FILE_UPLOAD_SUCCESS = 'file upload success';
    const STATUS_RESTART_UPLOAD = 'restart upload';

    private const STATUSES = [
        self::STATUS_CHUNK_UPLOAD_SUCCESS,
        self::STATUS_FILE_TOO_LARGE,
        self::STATUS_FILE_UPLOAD_SUCCESS,
        self::STATUS_RESTART_UPLOAD
    ];

    /**
     * @var string
     */
    private $status;

    /**
     * @var FileChunk
     */
    private $fileChunk;

    /**
     * @var array|null
     */
    private $payload;

    /**
     * FileChunkUploaderResponse constructor.
     * @param string $status
     * @param FileChunk $fileChunk
     * @param array|null $payload
     */
    public function __construct(string $status, FileChunk $fileChunk, array $payload = null)
    {
        $this->status = $this->validateStatus($status);
        $this->fileChunk = $fileChunk;
        $this->payload = $payload;
    }

    /**
     * @param string $status
     * @return string
     */
    private function validateStatus(string $status): string
    {
        if (in_array($status, self::STATUSES) === false) {
            throw new InvalidArgumentException(
                "$status is not a valid status, see private constant STATUSES for valid ones"
            );
        }

        return $status;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return FileChunkUploaderResponse
     */
    public function setStatus(string $status): FileChunkUploaderResponse
    {
        $this->status = $this->validateStatus($status);

        return $this;
    }

    /**
     * @return FileChunk
     */
    public function getFileChunk(): FileChunk
    {
        return $this->fileChunk;
    }

    /**
     * @return array|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * @param array|null $payload
     * @return FileChunkUploaderResponse
     */
    public function setPayload(?array $payload): FileChunkUploaderResponse
    {
        $this->payload = $payload;

        return $this;
    }
}
