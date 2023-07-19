
<html>

<head>
    <title>Continuous QR Code Scanner</title>
    <script src="https://unpkg.com/html5-qrcode"></script>

</head>

<body>
    <div id="qr-reader" style="width:500px"></div>
    <div id="qr-reader-results"></div>
    <script>
        var resultContainer = document.getElementById('qr-reader-results');
        var lastResult, countResults = 0;

        function onScanSuccess(decodedText, decodedResult) {
            if (decodedText !== lastResult) {
                ++countResults;
                lastResult = decodedText;
                // Handle on success condition with the decoded message.
                console.log(`Scan result ${decodedText}`, decodedResult);
                var html = `<div class="result">[${countResults}] - ${decodedText}</div>`;
                resultContainer.innerHTML += html;
            }
        }

        var html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>

</html>