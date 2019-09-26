import CryptoJS from 'crypto-js';

/**
 * Generates a fingerprint from a file with the specified algorithm (if supported).
 * Fingerprint is created and updated sequentially to make possible the processing of a large file without loading
 * the whole file in memory, which is bad for performance.
 *
 * @param file
 * @param algorithm (name)
 * @param chunkSize
 * @param progressCallback
 * @returns {Promise<unknown>}
 */
export function getFileFingerprint(file, algorithm, chunkSize, progressCallback = null) {
    return new Promise((resolve, reject) => {
        const HASHER = getHasher(algorithm);

        processFile(
            file,
            chunkSize,
            (chunkContent, offset, fileSize) => {
                // Hash is updated with the content of this chunk.
                HASHER.update(CryptoJS.enc.Latin1.parse(chunkContent));

                // If it exists, the callback function to update the progress feedback is called.
                if (progressCallback) {
                    progressCallback(offset / fileSize);
                }
            },
            // Called when processFile() successfully reaches end of file or encouters an error.
            status => {
                if (status === 'success') {
                    const HASH = HASHER.finalize().toString();
                    resolve(HASH);
                } else {
                    reject();
                }
            });
    });
}

function getHasher(algorithm) {
    // Add algorithms here if you need to support more.
    const SUPPORTED_HASHERS = {
        sha256: CryptoJS.algo.SHA256.create()
    };

    const ALGORITHM_NAME_LOWER_CASE = algorithm.toLowerCase();

    if (SUPPORTED_HASHERS.hasOwnProperty(ALGORITHM_NAME_LOWER_CASE) === false) {
        throw new Error(
            `${algorithm} is not supported by the file fingerprint generator. Consider adding it to getSupportedHashers()`
        );
    }

    return SUPPORTED_HASHERS[ALGORITHM_NAME_LOWER_CASE];
}

/**
 *
 *
 * @param file
 * @param chunkSize (in Mo)
 * @param handleChunkContentCallback
 * @param endCallback
 */
function processFile(file, chunkSize, handleChunkContentCallback, endCallback) {
    const FILE_SIZE = file.size;
    chunkSize *= 1000000;
    let offset = 0;

    const FILE_READER = new FileReader();

    function sliceNewChunk(offset, chunkSize) {
        return file.slice(offset, offset + chunkSize);
    }

    /*
    FILE_READER begins to read the content of the chunk. This is asynchronous.
    When the read operation is done, it will fire the onload event.
     */
    function loadChunkContent(chunk) {
        FILE_READER.readAsBinaryString(chunk);
    }

    // We start loading the content of the first chunk (offset is still 0).
    loadChunkContent(sliceNewChunk(offset, chunkSize));

    /*
    FILE_READER read operation is done and onload event has been fired, we can now process the binary content of the
    chunk.
     */
    FILE_READER.onload = function () {
        if (FILE_READER.error) {
            endCallback(FILE_READER.error || {});
            return;
        }

        offset += FILE_READER.result.length;

        handleChunkContentCallback(FILE_READER.result, offset, FILE_SIZE);

        /*
         If we have processed the last chunk of the file, we are done and stop processFile() here.
         Else, we start loading the content of the next chunk (offset is no longer 0). Once it done, onload event
         will be fired and this function called again, until the whole file has been processed.
         */
        if (offset >= FILE_SIZE) {
            endCallback('success');
        } else {
            loadChunkContent(sliceNewChunk(offset, chunkSize));
        }
    };

    FILE_READER.onerror = function (error) {
        endCallback(error || {});
    };
}
