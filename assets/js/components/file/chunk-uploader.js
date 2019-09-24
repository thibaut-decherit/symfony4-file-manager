import Sha256 from 'crypto-js/sha256';

$('#file').on('change', function (event) {
    const chunkSize = 1000000;
    const blob = event.target.files[0];

    convertToChunks(blob, chunkSize);
});

// Warning: For tests only, converting a very large file could result in large browser RAM consumption (TODO: Verify)
function convertToChunks(blob, chunkSize) {
    let file = [];
    const metadata = {
        name: blob.name,
        sha256: Sha256(blob).toString(),
        size: blob.size,
        type: blob.type,
    };

    let offset;
    let chunkCount = 0;
    for (offset = 0; offset < metadata.size; offset += chunkSize) {
        file.push(constructChunk(blob, metadata, offset, chunkSize, chunkCount));

        // console.log('Sliced chunk ' + chunkCount + '(offset ' + offset + ')');
        chunkCount++;
    }

    console.log(file);
}

// Constructs a chunk with attached metadata
function constructChunk(blob, metadata, offset, chunkSize, chunkCount) {
    return {
        chunkNumber: chunkCount,
        data: blob.slice(offset, offset + chunkSize, metadata.type),
        metadata: metadata
    };
}
