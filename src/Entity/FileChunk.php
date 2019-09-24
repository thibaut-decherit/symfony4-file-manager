<?php

namespace App\Entity;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileChunk
 * @package App\Entity
 */
class FileChunk
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $fingerprint;

    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $type;

    /**
     * @var UploadedFile
     */
    private $file;

    /**
     * @var int
     */
    private $id;

    /**
     * @var bool
     */
    private $isLastChunk;

    /**
     * FileChunk constructor.
     * @param int $id
     * @param array $metadata
     * @param UploadedFile $file
     * @param bool $isLastChunk
     */
    public function __construct(int $id, array $metadata, UploadedFile $file, bool $isLastChunk)
    {
        $this->name = (string)$metadata['name'];
        $this->fingerprint = (string)$metadata['sha256'];
        $this->size = (int)$metadata['size'];
        $this->type = (string)$metadata['type'];
        $this->file = $file;
        $this->id = (int)$id;
        $this->isLastChunk = (bool)$isLastChunk;
    }
}
