<?php

namespace App\Entity;

use App\Model\AbstractImageFile;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class LargeImage
 * @package App\Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="large_image")
 * @ORM\HasLifecycleCallbacks
 * @UniqueEntity(
 *     fields={"name", "path"}
 * )
 */
class LargeImage extends AbstractImageFile
{
    /**
     * @return string
     */
    public function getUploadDir(): string
    {
        // Path in public/ directory where files will be saved (upload() will create the directory if it doesn't exist).
        return parent::getUploadDir() . '/largeImages';
    }

    /**
     * @param UploadedFile|null $file
     * @return LargeImage
     */
    public function setFile(UploadedFile $file = null): self
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @param string $name
     * @return LargeImage
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $path
     * @return LargeImage
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }
}
