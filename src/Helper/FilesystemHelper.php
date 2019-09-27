<?php

namespace App\Helper;

use Exception;
use InvalidArgumentException;

/**
 * Class FilesystemHelper
 * Utility class for operations related to files and directories.
 *
 * @package App\Helper
 */
class FilesystemHelper
{
    /**
     * Returns file extension.
     * Returns empty string if files does not have an extension.
     *
     * @param string $fileNameOrFilePath
     * @return string
     */
    public static function getFileExtension(string $fileNameOrFilePath): string
    {
        return pathinfo($fileNameOrFilePath, PATHINFO_EXTENSION);
    }

    /**
     * @param string $fileNameOrFilePath
     * @return string
     */
    public static function getFileNameWithExtension(string $fileNameOrFilePath): string
    {
        return pathinfo($fileNameOrFilePath, PATHINFO_BASENAME);
    }

    /**
     * @param string $fileNameOrFilePath
     * @return string
     */
    public static function getFileNameWithoutExtension(string $fileNameOrFilePath): string
    {
        return pathinfo($fileNameOrFilePath, PATHINFO_FILENAME);
    }

    /**
     * Deletes directory and it's content recursively, including sub-directories and their own content.
     *
     * @param string $directoryPath
     * @return bool
     */
    public static function removeDirectoryAndContent(string $directoryPath): bool
    {
        if (is_dir($directoryPath) === false) {
            throw new InvalidArgumentException("$directoryPath is not a directory");
        }

        /*
         * array_diff() here removes current (.) and parent (..) directories which may be included in the array returned
         * by scandir().
         */
        $items = array_diff(scandir($directoryPath), ['.', '..']);

        foreach ($items as $item) {
            if (is_dir("$directoryPath/$item")) {
                FilesystemHelper::removeDirectoryAndContent("$directoryPath/$item");
            } else {
                unlink("$directoryPath/$item");
            }
        }

        return rmdir($directoryPath);
    }

    /**
     * Returns a random file name with the same extension than $fileNameOrFilePath.
     *
     * @param string $fileNameOrFilePath
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public static function getRandomFileName(string $fileNameOrFilePath, int $entropy = 256): string
    {
        return StringHelper::generateRandomString($entropy) . '.' . FilesystemHelper::getFileExtension($fileNameOrFilePath);
    }
}
