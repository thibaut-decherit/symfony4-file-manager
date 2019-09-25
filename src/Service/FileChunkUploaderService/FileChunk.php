<?php

namespace App\Service\FileChunkUploaderService;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class FileChunk
 * @package App\Service\ChunkUploaderService
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
     * Used to determine if server should consider the file is complete
     *
     * @var bool
     */
    private $isLastChunk;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * FileChunk constructor.
     * @param int $id
     * @param array $metadata
     * @param UploadedFile $file
     * @param bool $isLastChunk
     * @param string $entityClass
     * @param UserInterface $user
     */
    public function __construct(
        int $id,
        array $metadata,
        UploadedFile $file,
        bool $isLastChunk,
        string $entityClass,
        UserInterface $user
    )
    {
        $this->name = (string)$metadata['name'];
        $this->fingerprint = (string)$metadata['sha256'];
        $this->size = (int)$metadata['size'];
        $this->type = (string)$metadata['type'];
        $this->file = $file;
        $this->id = $id;
        $this->isLastChunk = $isLastChunk;
        $this->entityClass = $entityClass;
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return UploadedFile
     */
    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isLastChunk(): bool
    {
        return $this->isLastChunk;
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }
}
