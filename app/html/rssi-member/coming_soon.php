<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon</title>
    <style>
        .tagline {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .animated-text span {
            display: inline-block;
            animation: bounce 1.5s infinite alternate;
        }

        .animated-text span:nth-child(1) {
            animation-delay: 0s;
        }

        .animated-text span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .animated-text span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce {
            from {
                transform: translateY(0);
            }

            to {
                transform: translateY(-10px);
            }
        }
    </style>
</head>

<body>
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <h1 class="mb-3">Something Amazing is Coming!</h1>
                <p class="tagline">We're working hard behind the scenes to bring you something special.</p>
                <div class="animated-text">
                    <span>🚀</span>
                    <span>🌟</span>
                    <span>💡</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>