// camera-compressor-100kb.js
// Camera-specific compression for onboarding photos

function compressCameraPhoto(base64Image) {
    return new Promise((resolve, reject) => {
        const maxSizeKB = 100; // Target: 100KB maximum
        const maxWidth = 700;
        const maxHeight = 700;

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

            // Try multiple quality levels to ensure under 100KB
            tryQualityLevel(0.6); // Start with 60% quality

            function tryQualityLevel(quality) {
                canvas.toBlob(function (blob) {
                    console.log(`Trying quality ${quality}: ${formatBytes(blob.size)}`);

                    if (blob.size <= maxSizeKB * 1024) {
                        // Success - under 100KB
                        createCompressedResult(blob, quality);
                    } else if (quality > 0.3) {
                        // Try with lower quality (minimum 30%)
                        tryQualityLevel(quality - 0.1);
                    } else {
                        // If still too large at 30% quality, resize more aggressively
                        console.log('Still too large, resizing more...');
                        width = Math.floor(width * 0.8);
                        height = Math.floor(height * 0.8);

                        if (width < 300 || height < 300) {
                            // Don't go too small
                            width = Math.max(width, 300);
                            height = Math.max(height, 300);
                        }

                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        // Try again with 30% quality after resizing
                        canvas.toBlob(function (finalBlob) {
                            createCompressedResult(finalBlob, 0.3);
                        }, 'image/jpeg', 0.3);
                    }
                }, 'image/jpeg', quality);
            }

            function createCompressedResult(blob, quality) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    console.log('Camera photo compressed to', formatBytes(blob.size), `(${Math.round(quality * 100)}% quality)`);
                    resolve({
                        base64: e.target.result,
                        blob: blob,
                        quality: quality
                    });
                };
                reader.readAsDataURL(blob);
            }
        };
        img.onerror = reject;
        img.src = base64Image;
    });
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Make functions globally available
window.compressCameraPhoto = compressCameraPhoto;
window.formatBytes = formatBytes;