<?php

namespace App\Service\FileChunkUploaderService;

use App\Helper\StringHelper;
use Exception;
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
    public function getLastUploadedChunkId(Request $request, string $entityClass, UserInterface $user): ?int
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
    public function buildChunk(Request $request, string $entityClass, UserInterface $user): FileChunk
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
     * Returns file path if upload is complete (chunk was the last one required to complete the file) without error.
     * Returns 'chunk upload done' string if the chunk has been uploaded and processed successfully.
     * Returns 'file corrupted' string if upload is complete but file fingerprint does not match fingerprint generated
     * client-side.
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
     * Handles write to disk operations and fingerprint validation of complete files.
     * Returns file path if upload is complete (chunk was the last one required to complete the file) without error.
     * Returns 'chunk upload done' string if the chunk has been uploaded and processed successfully.
     * Returns 'file corrupted' string if upload is complete but file fingerprint does not match fingerprint generated
     * client-side.
     *
     * @param FileChunk $fileChunk
     * @return string
     */
    private function upload(FileChunk $fileChunk): string
    {
        $this->writeChunkToDisk($fileChunk);

        if ($fileChunk->isLastChunk() === false) {
            return 'chunk upload done';
        }

        if ($this->validateFileFingerprint($fileChunk) === false) {
            $this->handleCorruptedFile($fileChunk);

            return 'file corrupted';
        }

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

        if ($fileChunk->getId() === 0) {
            // First chunk so it is directly written to disk.
            $fileChunk->getFile()->move($chunkUploadDirectoryPath, $this->getChunkName($fileChunk));
        } else {
            // Current chunk is appended to the chunk already on disk
            $previousChunkPath = $this->getPreviousChunkPath($fileChunk);
            $currentChunkPath = $this->getChunkPath($fileChunk);
            file_put_contents($previousChunkPath, file_get_contents($fileChunk->getFile()), FILE_APPEND);

            rename($previousChunkPath, $currentChunkPath);
        }
    }

    /**
     * @param FileChunk $lastFileChunk
     * @return string
     * The path of the complete file
     * @throws Exception
     */
    private function moveCompleteFileToUploadDirectory(FileChunk $lastFileChunk): string
    {
        if (!file_exists($this->getCompleteFileUploadDirectory($lastFileChunk))) {
            mkdir($this->getCompleteFileUploadDirectory($lastFileChunk), 0775, true);
        }

        $completeFileChunkPath = $this->getChunkPath($lastFileChunk);

        // Renames the file and makes sure another file with the same name does not already exists in the same directory.
        $loop = true;
        $completeFilePath = '';
        while ($loop) {
            $randomName = StringHelper::generateRandomString(256) . '.' . $lastFileChunk->getExtension();
            $completeFilePath = $this->getCompleteFileUploadDirectory($lastFileChunk) . '/' . $randomName;

            if (file_exists($completeFilePath) === false) {
                $loop = false;
            }
        }

        rename($completeFileChunkPath, $completeFilePath);

        rmdir($this->getChunkUploadDirectory($lastFileChunk)); // TODO: delete file and folder instead just to be sure, create a filesystem helper if required (see https://stackoverflow.com/a/3349792/9847511)

        return $completeFilePath;
    }

    /**
     * Warning: This will render the server unresponsive for multiple seconds if you hash a very large file (> 100Mo).
     *
     * @param FileChunk $lastFileChunk
     * @return bool
     */
    private function validateFileFingerprint(FileChunk $lastFileChunk): bool
    {
        $completeFileChunkPath = $this->getChunkPath($lastFileChunk);

        return hash_file('SHA256', $completeFileChunkPath) === $lastFileChunk->getFingerprint();
    }

    /**
     * @param FileChunk $lastFileChunk
     * @return void
     */
    private function handleCorruptedFile(FileChunk $lastFileChunk): void
    {
        $completeFileChunkPath = $this->getChunkPath($lastFileChunk);

        unlink($completeFileChunkPath); // TODO: delete file and folder instead, create a filesystem helper if required (see https://stackoverflow.com/a/3349792/9847511)
    }

    /**
     * @return string
     */
    private function getPrivateUploadDirectory(): string
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

        return "$privateUploadDirectory/user-$userId/$entityClass/partial/$fingerprint";
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

        return "$privateUploadDirectory/user-$userId/$entityClass/complete";
    }
}
