<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Steps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step-item {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            position: relative;
        }
        .step-item:not(:last-child)::after {
            content: '';
            width: 50px;
            height: 2px;
            background-color: #ccc;
            position: absolute;
            top: calc(50% - 1px);
            left: calc(100% + 10px);
            z-index: -1;
        }
        .step-item.active::after {
            background-color: #4CAF50;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .step-label {
            color: #999;
        }
        .step-item.active .step-circle {
            background-color: #4CAF50;
            color: white;
        }
        /* Arrow styles */
        .arrow-right {
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-left: 10px solid #ccc;
            position: absolute;
            top: calc(50% - 10px);
            left: calc(100% + 5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-auto">
                <div class="row g-0">
                    <div class="col">
                        <div class="step-item active">
                            <div class="step-circle">1</div>
                            <div class="step-label">Registration</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="step-item">
                            <div class="arrow-right"></div>
                            <div class="step-circle">2</div>
                            <div class="step-label">Identity Verification</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="step-item">
                            <div class="arrow-right"></div>
                            <div class="step-circle">3</div>
                            <div class="step-label">Schedule Interview</div>
                        </div>
                    </div>
                    <!-- Add more steps as needed -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
