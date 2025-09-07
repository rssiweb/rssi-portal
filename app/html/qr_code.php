<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator with Logo</title>
    <link href="../img/favicon_1.ico" rel="icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #4bb543;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 25px;
            text-align: center;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="url"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e5eb;
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: all 0.3s;
        }

        input[type="url"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px;
            background: #f8f9fa;
            border: 2px dashed #e1e5eb;
            border-radius: var(--border-radius);
            color: #6c757d;
            cursor: pointer;
        }

        .file-input-button:hover {
            background: #e9ecef;
        }

        .file-name {
            font-size: 14px;
            margin-top: 5px;
            color: #6c757d;
        }

        button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .result {
            display: none;
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            border-radius: var(--border-radius);
            background: #f8f9fa;
            border: 2px dashed #e1e5eb;
        }

        .result.active {
            display: block;
        }

        #qrcode {
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            display: inline-block;
            position: relative;
        }

        #qrcode canvas {
            border-radius: 8px;
        }

        .logo-preview {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin: 15px auto;
            display: block;
            border: 2px solid #e1e5eb;
            border-radius: 8px;
            padding: 5px;
            background: white;
        }

        .logo-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20%;
            height: 20%;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            pointer-events: none;
        }

        .download-btn {
            background: var(--success);
            margin-top: 15px;
            max-width: 250px;
            margin-left: auto;
            margin-right: auto;
        }

        .download-btn:hover {
            background: #3a9c34;
        }

        .loader {
            display: none;
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .error {
            color: #dc3545;
            margin-top: 10px;
            font-size: 14px;
            display: none;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e1e5eb;
        }

        @media (max-width: 600px) {
            .container {
                border-radius: 0;
            }

            h1 {
                font-size: 24px;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>QR Code Generator by Sourav Saha</h1>
            <p class="subtitle">Create custom QR codes with your logo</p>
        </header>

        <div class="content">
            <form id="qr-form">
                <div class="form-group">
                    <label for="url">Website URL</label>
                    <input type="url" id="url" name="url" placeholder="https://example.com" required>
                </div>

                <div class="form-group">
                    <label for="logo">Logo (Optional)</label>
                    <div class="file-input-container">
                        <div class="file-input-button">
                            <span>Choose logo image</span>
                            <i class="fas fa-upload"></i>
                        </div>
                        <input type="file" id="logo" name="logo" accept="image/*" style="position: absolute; left: 0; top: 0; opacity: 0; width: 100%; height: 100%; cursor: pointer;">
                    </div>
                    <div class="file-name" id="file-name">No file chosen</div>
                    <img id="logo-preview" class="logo-preview" style="display: none;" alt="Logo preview">
                </div>

                <button type="submit" name="generate" id="generate-btn">
                    <span class="loader" id="loader"></span>
                    <span>Generate QR Code</span>
                </button>

                <div class="error" id="error-message"></div>
            </form>

            <div class="result" id="result">
                <h3>Your QR Code</h3>
                <div id="qrcode"></div>
                <br>
                <button class="download-btn" id="download-btn">
                    <i class="fas fa-download"></i>
                    Download QR Code
                </button>
            </div>
        </div>

        <footer>
            <p>© 2025 QR Code Generator | Create custom QR codes with your logo | Designed and maintained by Sourav Saha</p>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('qr-form');
            const result = document.getElementById('result');
            const errorMessage = document.getElementById('error-message');
            const loader = document.getElementById('loader');
            const fileInput = document.getElementById('logo');
            const fileName = document.getElementById('file-name');
            const logoPreview = document.getElementById('logo-preview');
            const qrcodeElement = document.getElementById('qrcode');
            const downloadBtn = document.getElementById('download-btn');
            const generateBtn = document.getElementById('generate-btn');

            let qrcode = null;
            let logoFile = null;
            let logoDataUrl = null;

            // Show selected file name and preview
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    logoFile = this.files[0];
                    fileName.textContent = logoFile.name;

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        logoDataUrl = e.target.result;
                        logoPreview.src = logoDataUrl;
                        logoPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(logoFile);
                } else {
                    fileName.textContent = 'No file chosen';
                    logoPreview.style.display = 'none';
                    logoDataUrl = null;
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Basic client-side validation
                const urlInput = document.getElementById('url');
                if (!urlInput.value) {
                    showError('Please enter a URL');
                    return;
                }

                try {
                    new URL(urlInput.value);
                } catch (_) {
                    showError('Please enter a valid URL');
                    return;
                }

                // Show loader
                loader.style.display = 'block';
                generateBtn.disabled = true;
                result.classList.remove('active');
                errorMessage.style.display = 'none';

                // Simulate processing time
                setTimeout(generateQRCode, 800);
            });

            function generateQRCode() {
                const url = document.getElementById('url').value;

                // Clear previous QR code
                qrcodeElement.innerHTML = '';

                // Generate QR code
                qrcode = new QRCode(qrcodeElement, {
                    text: url,
                    width: 250,
                    height: 250,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });

                // Wait for QR code to render
                setTimeout(addLogoToQRCode, 300);
            }

            function addLogoToQRCode() {
                if (!logoDataUrl) {
                    finishGeneration();
                    return;
                }

                const canvas = qrcodeElement.querySelector('canvas');
                if (!canvas) {
                    finishGeneration();
                    return;
                }

                const ctx = canvas.getContext('2d');
                const logoImg = new Image();

                logoImg.onload = function() {
                    // Draw logo in the center of QR code
                    const logoSize = canvas.width * 0.25; // Logo size is 25% of QR code
                    const x = (canvas.width - logoSize) / 2;
                    const y = (canvas.height - logoSize) / 2;

                    // Draw white background for logo
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(x - 2, y - 2, logoSize + 4, logoSize + 4);

                    // Draw logo
                    ctx.drawImage(logoImg, x, y, logoSize, logoSize);

                    finishGeneration();
                };

                logoImg.onerror = function() {
                    finishGeneration();
                };

                logoImg.src = logoDataUrl;
            }

            function finishGeneration() {
                // Hide loader
                loader.style.display = 'none';
                generateBtn.disabled = false;

                // Show result
                result.classList.add('active');

                // Scroll to result
                result.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }

            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }

            // Download functionality
            downloadBtn.addEventListener('click', function() {
                const canvas = qrcodeElement.querySelector('canvas');
                if (!canvas) return;

                const link = document.createElement('a');
                link.download = 'qrcode-with-logo.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        });
    </script>
</body>

</html>