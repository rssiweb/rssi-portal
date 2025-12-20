// image-compressor.js - Simple standalone compression
// Usage: <input type="file" onchange="compressImageBeforeUpload(this)">

function compressImageBeforeUpload(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];
    const maxSizeMB = 2;
    const maxWidth = 1200;
    const maxHeight = 1200;
    const quality = 0.7;

    // Check if it's an image
    if (!file.type.match('image.*')) return;

    // If image is already small enough, don't compress
    if (file.size <= maxSizeMB * 1024 * 1024) {
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        const img = new Image();
        img.onload = function () {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            let width = img.width;
            let height = img.height;

            // Resize if larger than max dimensions
            if (width > maxWidth || height > maxHeight) {
                const ratio = Math.min(maxWidth / width, maxHeight / height);
                width = Math.floor(width * ratio);
                height = Math.floor(height * ratio);
            }

            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob(function (blob) {
                const compressedFile = new File([blob], file.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                input.files = dataTransfer.files;

                console.log('Image compressed from', formatBytes(file.size), 'to', formatBytes(blob.size));

            }, 'image/jpeg', quality);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Make function globally available
window.compressImageBeforeUpload = compressImageBeforeUpload;
window.formatBytes = formatBytes;