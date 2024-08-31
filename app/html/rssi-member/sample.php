<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filled Admission Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #000;
            box-sizing: border-box;
            position: relative;
        }

        h1, h2 {
            text-align: center;
            margin: 0;
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .photo {
            position: absolute;
            top: 40px;
            right: 20px;
            width: 100px;
            height: 120px;
            border: 1px solid #000;
            text-align: center;
            line-height: 120px;
            font-size: 14px;
            color: #666;
        }

        .form-section {
            margin-bottom: 20px;
        }

        .label {
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block;
        }

        .value {
            display: inline-block;
            border-bottom: 1px dashed #000;
            padding: 2px 4px;
            margin-bottom: 10px;
            min-width: 100px;
            text-align: center;
        }

        .box {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 4px;
            margin-bottom: 10px;
            min-width: 100px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }

        .signature-section div {
            width: 48%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
        }

        @page {
            size: A4;
            margin: 20mm;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                margin: 0;
                padding: 20px;
                border: 1px solid #000;
                box-sizing: border-box;
                width: 100%;
                max-width: 100%;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="logo.png" alt="University Logo" class="logo">
            <h1>SunRise University</h1>
            <h2>Admission Form</h2>
            <p>Session: 2024-2025</p>
        </div>

        <div class="form-section">
            <!-- Student Photo -->
            <div class="photo">Student Photo</div>

            <div class="form-group">
                <span class="label">Full Name:</span>
                <span class="value">John Doe</span>
            </div>
            <div class="form-group">
                <span class="label">Date of Birth:</span>
                <span class="value">01/01/2000</span>
            </div>
            <div class="form-group">
                <span class="label">Father's Name:</span>
                <span class="value">Richard Roe</span>
            </div>
            <div class="form-group">
                <span class="label">Mother's Name:</span>
                <span class="value">Jane Roe</span>
            </div>
            <div class="form-group">
                <span class="label">Gender:</span>
                <span class="box">Male</span>
            </div>
            <div class="form-group">
                <span class="label">Category:</span>
                <span class="box">General</span>
            </div>
        </div>

        <div class="form-section">
            <h3>Contact Details</h3>
            <div class="form-group">
                <span class="label">Address:</span>
                <span class="value">123 Main St, Springfield</span>
            </div>
            <div class="form-group">
                <span class="label">City:</span>
                <span class="value">Springfield</span>
            </div>
            <div class="form-group">
                <span class="label">State:</span>
                <span class="value">IL</span>
            </div>
            <div class="form-group">
                <span class="label">Phone:</span>
                <span class="value">+1 234 567 890</span>
            </div>
            <div class="form-group">
                <span class="label">Email:</span>
                <span class="value">johndoe@example.com</span>
            </div>
        </div>

        <div class="form-section">
            <h3>Education Details</h3>
            <div class="form-group">
                <span class="label">High School:</span>
                <span class="value">Springfield High School</span>
            </div>
            <div class="form-group">
                <span class="label">Year of Passing:</span>
                <span class="value">2018</span>
            </div>
            <div class="form-group">
                <span class="label">Percentage:</span>
                <span class="value">85%</span>
            </div>
            <div class="form-group">
                <span class="label">Undergraduate:</span>
                <span class="value">Springfield University</span>
            </div>
            <div class="form-group">
                <span class="label">Year of Passing:</span>
                <span class="value">2022</span>
            </div>
            <div class="form-group">
                <span class="label">Percentage:</span>
                <span class="value">88%</span>
            </div>
        </div>

        <div class="signature-section">
            <div>Signature of Applicant</div>
            <div>Signature of Parent/Guardian</div>
        </div>
    </div>
</body>
</html>
