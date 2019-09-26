import {body} from '../helpers/jquery/selectors';
import {getFileFingerprint} from './fingerprint-generator';

body.on('submit', '#ajax-form-large-file-upload', function (event) {
    event.preventDefault();

    const LARGE_FILE_UPLOAD_FORM = event.currentTarget;

    getFileFingerprint(
        LARGE_FILE_UPLOAD_FORM.elements.file.files[0],
        'SHA256',
        progress => console.log("Progress: " + progress)
    ).then(fingerprint => {
        console.log(fingerprint);
        handleFileUpload(LARGE_FILE_UPLOAD_FORM, fingerprint);
    })
});

async function handleFileUpload(form, fingerprint) {
    const CHUNK_SIZE = 2000000; // 2Mo
    const BLOB = form.elements.file.files[0];

    const METADATA = {
        name: BLOB.name,
        sha256: fingerprint,
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

        // await uploadChunk(form, CHUNK, isLastChunk);

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
