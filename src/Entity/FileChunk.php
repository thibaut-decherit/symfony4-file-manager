<?php

namespace App\Entity;

/**
 * Class FileChunk
 * @package App\Entity
 */
class FileChunk
{
   private $name;

   private $fingerprint;

   private $size;

   private $type;

   private $file;

   private $number;

    /**
     * CspViolation constructor.
     * @param array $fileChunkRawData
     */
    public function __construct(array $fileChunkRawData)
    {
        $this->name = $fileChunkRawData['violated-directive'];
        $this->fingerprint = $fileChunkRawData['document-uri'];
        $this->size = $fileChunkRawData['blocked-uri'];
        $this->type = $fileChunkRawData['route'];
        $this->file = 1;
        $this->number = 1;
    }
}
