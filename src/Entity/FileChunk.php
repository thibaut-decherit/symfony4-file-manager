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
     * FileChunk constructor.
     * @param int $id
     * @param array $metadata
     * @param UploadedFile $file
     */
    public function __construct(int $id, array $metadata, UploadedFile $file)
    {
        $this->name = $metadata['name'];
        $this->fingerprint = $metadata['sha256'];
        $this->size = $metadata['size'];
        $this->type = $metadata['type'];
        $this->file = $file;
        $this->id = $id;
    }
}
