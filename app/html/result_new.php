<!DOCTYPE html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php echo $student_id ?>_<?php echo $id ?>_<?php echo $year ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <style>
        body {
            background: #f8f9fa;
            font-family: "Roboto", sans-serif;
            font-size: 14px;
            color: #444;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: #007bff;
            color: #fff;
            padding: 10px;
            text-align: center;
        }

        .footer {
            background: #f8f9fa;
            padding: 10px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <header class="header">
        <h1>Government Result Portal</h1>
    </header>

    <div class="container mt-5">
        <form action="" method="GET">
            <div class="mb-3">
                <label for="studentId" class="form-label">Student ID</label>
                <input name="get_stid" id="studentId" class="form-control" type="text" required placeholder="Enter Student ID" value="<?php echo @$stid ?>">
            </div>
            <div class="mb-3">
                <label for="examName" class="form-label">Exam Name</label>
                <select name="get_id" id="examName" class="form-select" required>
                    <?php if ($id == null) { ?>
                        <option value="" disabled selected hidden>Select Exam Name</option>
                    <?php } else { ?>
                        <option hidden selected><?php echo $id ?></option>
                    <?php } ?>
                    <option>First Term Exam</option>
                    <option>Half Yearly Exam</option>
                    <option>Annual Exam</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="year" class="form-label">Year</label>
                <select name="get_year" id="year" class="form-select" required>
                    <?php if ($year == null) { ?>
                        <option value="" disabled selected hidden>Select Year</option>
                    <?php } else { ?>
                        <option hidden selected><?php echo $year ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="text-center">
                <button type="submit" name="search_by_id" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> Government Result Portal. All rights reserved.</p>
    </footer>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
