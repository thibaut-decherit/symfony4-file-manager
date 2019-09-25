<?php

namespace App\Service\FileChunkUploaderService;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class FileChunkUploaderService
 * @package App\Service\FileChunkUploaderService
 */
class FileChunkUploaderService
{
    /**
     * @var string
     */
    private $privateUploadDirectory;

    /**
     * FileChunkUploaderService constructor.
     * @param string $privateUploadDirectory
     */
    public function __construct(string $privateUploadDirectory)
    {
        $this->privateUploadDirectory = $privateUploadDirectory;
    }

    /**
     * Returns true if upload is resumable.
     * Otherwise returns false.
     * Upload is considered resumable if a directory with the same fingerprint already exists.
     *
     * @param Request $request
     * @param string $entityClass
     * @param UserInterface $user
     * @return bool
     */
    public function isResumable(Request $request, string $entityClass, UserInterface $user): bool
    {
        return file_exists($this->getChunkUploadDirectory($this->buildChunk($request, $entityClass, $user)));
    }

    /**
     * Returns last uploaded chunk id if upload is resumable.
     * Otherwise returns null.
     *
     * @param Request $request
     * @param string $entityClass
     * @param UserInterface $user
     * @return int|null
     */
    public function getLastUploadedChunk(Request $request, string $entityClass, UserInterface $user): ?int
    {
        if ($this->isResumable($request, $entityClass, $user)) {
            $fileChunk = $this->buildChunk($request, $entityClass, $user);

            // TODO: Get name of file in $this->getChunkUploadDirectory($fileChunk)
            // TODO: Take string after the last '-' of filename then cast it to int, return the result
        }

        return null;
    }

    /**
     * @param Request $request
     * @param string $entityClass
     * @param UserInterface $user
     * @return FileChunk
     */
    private function buildChunk(Request $request, string $entityClass, UserInterface $user): FileChunk
    {
        $chunkNumber = $request->get('id');
        $metadata = (array)json_decode($request->get('metadata'));
        $isLastChunk = filter_var($request->get('isLastChunk'), FILTER_VALIDATE_BOOLEAN);

        return new FileChunk(
            $chunkNumber,
            $metadata,
            $request->files->get('file'),
            $isLastChunk,
            $entityClass,
            $user
        );
    }

    /**
     * Handles upload of a file chunk.
     * Returns file path if upload is complete (chunk was the last one required to complete the file).
     * Otherwise returns null.
     *
     * @param Request $request
     * @param string $entityClass
     * @param UserInterface $user
     * @return string|null
     */
    public function handleUpload(Request $request, string $entityClass, UserInterface $user): ?string
    {
        $fileChunk = $this->buildChunk($request, $entityClass, $user);

        return $this->upload($fileChunk);
    }

    /**
     * Handles write to disk operation then returns path of complete file if upload is complete.
     * Otherwise returns null.
     * Upload is considered complete if $fileChunk->isLastChunk() is true.
     *
     * @param FileChunk $fileChunk
     * @return string|null
     */
    private function upload(FileChunk $fileChunk): ?string
    {
        $this->writeChunkToDisk($fileChunk);

        if ($fileChunk->isLastChunk() === false) {
            return null;
        }

        return null; // TODO: temp
        return $this->moveCompleteFileToUploadDirectory($fileChunk);
    }

    /**
     * @param FileChunk $fileChunk
     */
    private function writeChunkToDisk(FileChunk $fileChunk): void
    {
        $chunkUploadDirectoryPath = $this->getChunkUploadDirectory($fileChunk);

        // Creates directory and subdirectories if they don't exist yet.
        if (!file_exists($chunkUploadDirectoryPath)) {
            mkdir($chunkUploadDirectoryPath, 0775, true);
        }

        $currentChunkName = $this->getChunkName($fileChunk);

        if ($fileChunk->getId() === 0) {
            // First chunk so it is directly written to disk.
            $fileChunk->getFile()->move($chunkUploadDirectoryPath, $currentChunkName);
        } else {
            // Current chunk is appended to the chunk already on disk
            $previousChunkPath = $this->getPreviousChunkPath($fileChunk);
            $currentChunkPath = $this->getChunkPath($fileChunk);
            file_put_contents($previousChunkPath, $fileChunk->getFile(), FILE_APPEND); // TODO: debug, doesn't seem to write any data

            rename($previousChunkPath, $currentChunkPath);
        }
    }

    /**
     * @param FileChunk $lastFileChunk
     * @return string
     */
    private function moveCompleteFileToUploadDirectory(FileChunk $lastFileChunk): string
    {
        if (!file_exists($this->getCompleteFileUploadDirectory($lastFileChunk))) {
            mkdir($this->getCompleteFileUploadDirectory($lastFileChunk), 0775, true);
        }

        // TODO: Check if sha256 fingerprint of file matches name of directory and fingerprint of $lastFileChunk->getFingerprint(), throw exception otherwise

        // TODO: Move completed file to complete directory with

        // TODO: Remove chunks 'hash' folder of completed file (first TODO may be able to do that too thanks to file->move)

        // TODO: Return path of moved complete file

        return 'pathToCompleteFile';
    }

    /**
     * @return string
     */
    public function getPrivateUploadDirectory(): string
    {
        return $this->privateUploadDirectory;
    }

    /**
     * @param FileChunk $fileChunk
     * @return string
     */
    private function getChunkName(FileChunk $fileChunk): string
    {
        $chunkId = $fileChunk->getId();

        return "merged-chunks-0-to-$chunkId";
    }

    /**
     * @param FileChunk $fileChunk
     * @return string
     */
    private function getChunkPath(FileChunk $fileChunk): string
    {
        $chunkUploadDirectory = $this->getChunkUploadDirectory($fileChunk);
        $chunkName = $this->getChunkName($fileChunk);

        return "$chunkUploadDirectory/$chunkName";
    }

    /**
     * @param FileChunk $fileChunk
     * @return string
     */
    private function getPreviousChunkPath(FileChunk $fileChunk): string
    {
        $chunkUploadDirectory = $this->getChunkUploadDirectory($fileChunk);
        $chunkId = $fileChunk->getId() - 1;

        return "$chunkUploadDirectory/merged-chunks-0-to-$chunkId";
    }

    /**
     * @param FileChunk $fileChunk
     * @return string
     */
    private function getChunkUploadDirectory(FileChunk $fileChunk): string
    {
        $privateUploadDirectory = $this->getPrivateUploadDirectory();
        $userId = $fileChunk->getUser()->getId();
        $entityClass = strtr($fileChunk->getEntityClass(), '\\', '-');
        $fingerprint = $fileChunk->getFingerprint();

        return "$privateUploadDirectory/user-$userId/$entityClass/chunks/$fingerprint";
    }

    /**
     * @param FileChunk $lastFileChunk
     * @return string
     */
    private function getCompleteFileUploadDirectory(FileChunk $lastFileChunk): string
    {
        $privateUploadDirectory = $this->getPrivateUploadDirectory();
        $userId = $lastFileChunk->getUser()->getId();
        $entityClass = strtr($lastFileChunk->getEntityClass(), '\\', '-');

        return "$privateUploadDirectory/user-$userId/$entityClass";
    }
}
