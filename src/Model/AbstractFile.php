<?php

namespace App\Model;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class AbstractFile
 * @package App\Model
 *
 * Abstract class for classes and entities requiring file upload and file metadata support.
 */
abstract class AbstractFile implements FileInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $path;

    /**
     * @return string
     */
    public function getUploadDir(): string
    {
        // Path in web/ directory where files will be saved (upload() will create the directory if it doesn't exist).
        return 'uploads';
    }

    /**
     * Generates an URI safe base64 encoded string that does not contain "+", "/" or "=" which need to be URL
     * encoded and make URLs unnecessarily longer.
     * String length is ceil($entropy / 6)
     *
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public function generateRandomName(int $entropy = 256): string
    {
        $bytes = random_bytes($entropy / 8);
        $randomName = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return $randomName . '.' . $this->file->getClientOriginalExtension();
    }

    /**
     * Sets file properties (name, path...) prior to database write.
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload(): void
    {
        // "file" property can be empty if field is required = false.
        if (null === $this->file) {
            return;
        }

        $fileName = $this->generateRandomName();

        $this->name = $fileName;
        $this->path = $this->getUploadDir() . '/' . $fileName;
    }

    /**
     * Moves file to upload directory after database write.
     *
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload(): void
    {
        // "file" property can be empty if field is required = false.
        if (null === $this->file) {
            return;
        }

        if (!file_exists($this->getUploadDir())) {
            mkdir($this->getUploadDir(), 0775, true);
        }

        $this->file->move(
            $this->getUploadDir(),
            $this->getName()
        );

        // Clears the file from the entity now that it has been moved to disk.
        $this->file = null;
    }

    /**
     * Deletes file after entity has been removed from database.
     *
     * @ORM\PostRemove()
     */
    public function unlinkFile(): void
    {
        unlink($this->getPath());
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Overwrite in final child and type hint the return as :self
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Overwrite in final child and type hint the return as :self
     *
     * @param UploadedFile|null $file
     * @return $this
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return UploadedFile|null
     */
    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * Overwrite in final child and type hint the return as :self
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
