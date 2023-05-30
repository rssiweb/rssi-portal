<!DOCTYPE html>
<html>

<head>
    <title>Student Admit Card</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .card {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            height: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            max-width: 80px;
        }

        .content {
            margin-bottom: 20px;
        }

        .student-info {
            margin-bottom: 10px;
        }

        .footer {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="header">
            <h4>Rina Shiksha Sahayak Foundation</h4>
            <h1>Admit Card</h1>
            <div class="d-flex justify-content-between align-items-center">
                <p class="me-auto">Serial Number: 123456789</p>
                <img src="qr_code.png" alt="QR Code" class="logo">
            </div>
        </div>
        <div class="content">
            <div class="student-info">
                <p><strong>Student Name:</strong> John Doe</p>
                <p><strong>Student ID:</strong> 123456</p>
                <p><strong>Class:</strong> 10th Grade</p>
                <p><strong>Category:</strong> General</p>
            </div>
            <div class="exam-info">
                <p><strong>Exam Name:</strong> Annual Exam 2023</p>
                <p><strong>Exam Schedule:</strong> 25th May 2023, 10:00 AM</p>
                <p><strong>Exam Centre:</strong> ABC School, New York</p>
            </div>
            <div class="instructions">
                <h4>Instructions</h4>
                <ul>
                    <li>Admit card is mandatory to bring on the day of the exam.</li>
                    <li>Reach the exam centre 30 minutes before the scheduled time.</li>
                    <li>Follow all the exam rules and regulations.</li>
                    <li>Any kind of cheating or malpractice will result in disqualification.</li>
                </ul>
            </div>
        </div>
        <div class="footer">
            <p>© 2023 Rina Shiksha Sahayak Foundation. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>