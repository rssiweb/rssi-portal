<!DOCTYPE html>
<html>
<head>
	<title>Record Video</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" />
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-sm-12">
				<h1 class="text-center mb-5">Record Video</h1>
				<video id="video" width="640" height="480" controls></video>
				<button id="start" class="btn btn-primary mt-3">Start Recording</button>
				<button id="stop" class="btn btn-danger mt-3" disabled>Stop Recording</button>
				<form method="POST" action="upload.php">
					<input type="hidden" id="video-data" name="video-data" />
					<button type="submit" class="btn btn-success mt-3" disabled>Upload Video</button>
				</form>
			</div>
		</div>
	</div>
	<script>
		// Get references to HTML elements
		var video = document.getElementById("video");
		var startButton = document.getElementById("start");
		var stopButton = document.getElementById("stop");
		var uploadButton = document.querySelector("button[type=submit]");

		// Set up the media stream
		navigator.mediaDevices.getUserMedia({ video: true })
			.then(function(stream) {
				video.srcObject = stream;
				video.play();
			});

		// Set up the MediaRecorder
		var mediaRecorder = null;
		var chunks = [];
		startButton.addEventListener("click", function() {
			mediaRecorder = new MediaRecorder(video.srcObject, { mimeType: "video/webm" });
			mediaRecorder.start();
			startButton.disabled = true;
			stopButton.disabled = false;
			uploadButton.disabled = true;
			chunks = [];
			setTimeout(stopRecording, 10000); // Stop recording after 10 seconds
		});

		// Stop the MediaRecorder and save the recorded video
		stopButton.addEventListener("click", stopRecording);
		function stopRecording() {
			mediaRecorder.stop();
			startButton.disabled = false;
			stopButton.disabled = true;
			uploadButton.disabled = false;
		}
		mediaRecorder.addEventListener("dataavailable", function(event) {
			chunks.push(event.data);
		});
		mediaRecorder.addEventListener("stop", function() {
			var blob = new Blob(chunks, { type: "video/webm" });
			var url = URL.createObjectURL(blob);
			video.src = url;
			document.getElementById("video-data").value = url;
		});
	</script>
</body>
</html>
