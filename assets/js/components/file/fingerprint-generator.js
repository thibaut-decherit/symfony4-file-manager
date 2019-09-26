import CryptoJS from 'crypto-js';

export function getFileFingerprint(file, algorithm, cbProgress) {
    return new Promise((resolve, reject) => {
        const HASHER = getSupportedHashers()[algorithm.toLowerCase()];

        processChunk(file, (chunk, offs, total) => {
            HASHER.update(CryptoJS.enc.Latin1.parse(chunk));
            if (cbProgress) {
                cbProgress(offs / total);
            }
        }, err => {
            if (err) {
                reject(err);
            } else {
                // TODO: Handle errors
                const HASH = HASHER.finalize().toString();
                resolve(HASH);
            }
        });
    });
}

function getSupportedHashers() {
    return {
        md5: CryptoJS.algo.MD5.create(),
        sha1: CryptoJS.algo.SHA1.create(),
        sha256: CryptoJS.algo.SHA256.create(),
        sha512: CryptoJS.algo.SHA512.create()
    };
}

function processChunk(file, chunkCallback, endCallback) {
    const FILE_SIZE = file.size;
    const CHUNK_SIZE = 4000000; // 4Mo
    let offset = 0;

    const FILE_READER = new FileReader();
    FILE_READER.onload = function () {
        if (FILE_READER.error) {
            endCallback(FILE_READER.error || {});
            return;
        }
        offset += FILE_READER.result.length;
        // callback for handling read chunk
        // TODO: handle errors
        chunkCallback(FILE_READER.result, offset, FILE_SIZE);
        if (offset >= FILE_SIZE) {
            endCallback(null);
            return;
        }
        readNext();
    };

    FILE_READER.onerror = function (err) {
        endCallback(err || {});
    };

    function readNext() {
        const CHUNK = file.slice(offset, offset + CHUNK_SIZE);
        FILE_READER.readAsBinaryString(CHUNK);
    }

    readNext();
}
