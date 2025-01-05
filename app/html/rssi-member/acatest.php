<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Associate Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-light">

    <!-- Sidebar -->
    <div class="d-flex" id="dashboardSidebar">
        <div class="bg-dark text-white p-4" style="width: 250px;">
            <h4 class="text-center">Dashboard</h4>
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">Dashboard Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">Tasks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">Meetings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">Notifications</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="#">Settings</a>
                </li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="container-fluid p-4">
            <!-- Top bar -->
            <div class="d-flex justify-content-between">
                <h2>Welcome, Associate</h2>
                <div>
                    <button class="btn btn-primary">Logout</button>
                </div>
            </div>

            <hr>

            <!-- Dashboard Stats Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Tasks Pending</h5>
                            <p class="card-text">You have <strong>5</strong> tasks pending.</p>
                            <a href="#" class="btn btn-link">View tasks</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Upcoming Meetings</h5>
                            <p class="card-text">You have <strong>2</strong> upcoming meetings this week.</p>
                            <a href="#" class="btn btn-link">View meetings</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Reports</h5>
                            <p class="card-text">Your latest report was submitted successfully.</p>
                            <a href="#" class="btn btn-link">View report</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wall of Fame, Poll, Quick Links, and Visitor Count -->
            <div class="row">
                <!-- Wall of Fame -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Wall of Fame</h5>
                            <h6>This Month</h6>
                            <ul>
                                <li>
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/50" class="rounded-circle" alt="John Doe">
                                        <span class="ms-3">John Doe - Employee of the Month</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/50" class="rounded-circle" alt="Jane Smith">
                                        <span class="ms-3">Jane Smith - Best Performance in Q1</span>
                                    </div>
                                </li>
                            </ul>
                            <h6>Last Month</h6>
                            <ul>
                                <li>
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/50" class="rounded-circle" alt="Michael Brown">
                                        <span class="ms-3">Michael Brown - Most Valuable Employee</span>
                                    </div>
                                </li>
                                <li>
                                    <div class="d-flex align-items-center">
                                        <img src="https://via.placeholder.com/50" class="rounded-circle" alt="Emily Clark">
                                        <span class="ms-3">Emily Clark - Top Contributor in Projects</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Poll Section -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Poll of the Week</h5>
                            <form id="pollForm">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pollOption" id="option1" value="Option 1">
                                    <label class="form-check-label" for="option1">Option 1</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pollOption" id="option2" value="Option 2">
                                    <label class="form-check-label" for="option2">Option 2</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="pollOption" id="option3" value="Option 3">
                                    <label class="form-check-label" for="option3">Option 3</label>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Vote</button>
                            </form>
                            <hr>
                            <div id="pollResults" style="display: none;">
                                <h6>Poll Results:</h6>
                                <ul>
                                    <li>Option 1: 25 votes</li>
                                    <li>Option 2: 18 votes</li>
                                    <li>Option 3: 12 votes</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links and Visitor Count -->
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Quick Links</h5>
                            <ul>
                                <li><a href="#">Company Intranet</a></li>
                                <li><a href="#">Employee Directory</a></li>
                                <li><a href="#">Leave Requests</a></li>
                                <li><a href="#">Help Desk</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Visitor Count</h5>
                            <p><strong>Today:</strong> 125 visitors</p>
                            <p><strong>Week:</strong> 890 visitors</p>
                            <p><strong>This Month:</strong> 3,250 visitors</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Achievements Section and Latest Updates -->
            <div class="row">
                <!-- Achievements -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Your Achievements</h5>
                            <ul>
                                <li>Completed the Project X ahead of schedule.</li>
                                <li>Successfully handled the client meeting on 5th July.</li>
                                <li>Received the "Employee of the Month" award for March.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Latest Updates (News Bulletin) -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Latest Updates</h5>
                            <ul>
                                <li>New HR policies have been introduced. Read the details in your inbox.</li>
                                <li>Company-wide training session on productivity tools next week.</li>
                                <li>Our new office location opens this Friday! Check out the photos on the intranet.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Poll and Chart.js -->
    <script>
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Performance',
                    data: [65, 59, 80, 81, 56, 55],
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Poll form submission handling
        document.getElementById('pollForm').addEventListener('submit', function(event) {
            event.preventDefault();
            alert('Thank you for voting! Your vote has been submitted.');
            // Update the poll results dynamically
            const pollResults = document.getElementById('pollResults');
            pollResults.style.display = 'block';
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
