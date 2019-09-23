<?php

namespace App\Model;

use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Interface FileInterface
 * @package App\Model
 *
 * Interface for abstract classes requiring file upload and file metadata support.
 */
interface FileInterface
{
    /**
     * @return string
     */
    public function getUploadDir(): string;

    /**
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public function generateRandomName(int $entropy): string;

    /**
     * Sets file properties (name, path...) prior to database write.
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload(): void;

    /**
     * Moves file to upload directory after database write.
     *
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload(): void;

    /**
     * Deletes file after entity has been removed from database.
     *
     * @ORM\PostRemove()
     */
    public function unlinkFile(): void;
}
