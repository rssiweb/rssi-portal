<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduling Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container py-5">
        <h1 class="mb-4">Interview Scheduling Form</h1>
        <form>
            <div class="mb-3">
                <label for="application-number" class="form-label">Application Number:</label>
                <input type="text" class="form-control" id="application-number" name="application-number" required>
            </div>

            <div class="mb-3">
                <label for="candidate-name" class="form-label">Candidate Name:</label>
                <input type="text" class="form-control" id="candidate-name" name="candidate-name" required>
            </div>

            <div class="mb-3">
                <label for="position-applied-for" class="form-label">Position Applied For:</label>
                <input type="text" class="form-control" id="position-applied-for" name="position-applied-for" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email ID:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="phone-number" class="form-label">Phone Number:</label>
                <input type="tel" class="form-control" id="phone-number" name="phone-number" required>
            </div>

            <div class="mb-3">
                <label for="interview-location" class="form-label">Interview Location:</label>
                <input type="text" class="form-control" id="interview-location" name="interview-location" required>
            </div>

            <div class="mb-3">
                <label for="interview-date-time" class="form-label">Interview Date and Time:</label>
                <input type="datetime-local" class="form-control" id="interview-date-time" name="interview-date-time" required>
            </div>

            <div class="mb-3">
                <label for="resume" class="form-label">Resume:</label>
                <input type="url" class="form-control" id="resume" name="resume" required>
            </div>

            <div class="mb-3">
                <label for="identity-verification" class="form-label">Identity Verification:</label>
                <input type="url" class="form-control" id="identity-verification" name="identity-verification" required>
            </div>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>