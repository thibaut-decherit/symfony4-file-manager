import Sha256 from 'crypto-js/sha256';
import {body} from '../helpers/jquery/selectors';

body.on('submit', '#ajax-form-large-file-upload', function (event) {
    event.preventDefault();

    const LARGE_FILE_UPLOAD_FORM = event.currentTarget;

    handleFileUpload(LARGE_FILE_UPLOAD_FORM);
});

// Warning: For tests only, converting a very large file could result in large browser RAM consumption (TODO: Verify)
async function handleFileUpload(form) {
    const CHUNK_SIZE = 100000; // 100ko
    const BLOB = form.elements.file.files[0];

    const METADATA = {
        name: BLOB.name,
        sha256: Sha256(BLOB).toString(), //TODO: Debug, doesn't handle checksum
        size: BLOB.size,
        type: BLOB.type,
    };

    let chunkCount = 0;
    for (let offset = 0; offset < METADATA.size; offset += CHUNK_SIZE) {
        const CHUNK = constructChunk(BLOB, METADATA, offset, CHUNK_SIZE, chunkCount);
        let isLastChunk = false;

        if (offset + CHUNK_SIZE >= METADATA.size) {
            isLastChunk = true;
        }

        await uploadChunk(form, CHUNK, isLastChunk);

        chunkCount++;
    }
}

async function uploadChunk(form, chunk, isLastChunk) {
    let formData = new FormData();

    formData.append('id', chunk.id);
    formData.append('metadata', chunk.metadata);
    formData.append('file', chunk.file);
    formData.append('isLastChunk', isLastChunk);

    await $.ajax({
        type: $(form).attr('method'),
        url: $(form).attr('action'),
        data: formData,
        processData: false,
        contentType: false
    })
        .done(function (response) {
            console.log(response);
        })
        .fail(function (error) {
            console.log(error);
        });
}

// Constructs a chunk with attached metadata
function constructChunk(blob, metadata, offset, chunkSize, chunkCount) {
    return {
        file: blob.slice(offset, offset + chunkSize, metadata.type),
        id: chunkCount,
        metadata: JSON.stringify(metadata)
    };
}
