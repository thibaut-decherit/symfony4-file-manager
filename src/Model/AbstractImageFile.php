<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AbstractImageFile
 * @package App\Model
 *
 * Abstract class for entities requiring image upload and image metadata support.
 */
abstract class AbstractImageFile extends AbstractFile
{
    /**
     * @Assert\Image(
     *     maxSize="2M",
     *     mimeTypes = {"image/jpeg", "image/png"}
     * )
     */
    protected $file;

    /**
     * @return string
     */
    public function getUploadDir(): string
    {
        // Path in public/ directory where files will be saved (upload() will create the directory if it doesn't exist).
        return parent::getUploadDir() . '/images';
    }
}
