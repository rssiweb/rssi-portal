<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Tracker</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/css/bootstrap.min.css">
</head>

<body>
  <div class="container mt-5">
    <h1 class="text-center mb-4">Attendance Tracker</h1>
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <form>
          <div class="mb-3">
            <label for="fingerprintInput" class="form-label">Fingerprint</label>
            <input type="text" class="form-control" id="fingerprintInput" placeholder="Scan your fingerprint" disabled>
          </div>
          <div class="text-center">
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>
