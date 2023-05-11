<!DOCTYPE html>
<html>

<head>
  <title>Employee Payslip</title>
  <!-- Load Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/css/bootstrap.min.css">
</head>

<body>
  <div class="container">
    <h1 class="text-center">Employee Payslip</h1>
    <hr>
    <div class="row">
      <div class="col-md-6">
        <h2>Employee Information</h2>
        <ul class="list-unstyled">
          <li><strong>Name:</strong> John Smith</li>
          <li><strong>Employee ID:</strong> 12345</li>
          <li><strong>Department:</strong> Sales</li>
        </ul>
      </div>
      <div class="col-md-6">
        <h2>Pay Information</h2>
        <ul class="list-unstyled">
          <li><strong>Pay Date:</strong> May 1, 2023</li>
          <li><strong>Pay Period:</strong> April 16-30, 2023</li>
        </ul>
      </div>
    </div>
    <hr>
    <div class="row">
      <div class="col-md-6">
        <h2>Earnings</h2>
        <table class="table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Basic Salary</td>
              <td>$3,500</td>
            </tr>
            <tr>
              <td>Overtime Pay</td>
              <td>$500</td>
            </tr>
            <tr>
              <td>Bonus</td>
              <td>$1,000</td>
            </tr>
            <tr>
              <td><strong>Total Earnings</strong></td>
              <td><strong>$5,000</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="col-md-6">
        <h2>Deductions</h2>
        <table class="table">
          <thead>
            <tr>
              <th>Category</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Income Tax</td>
              <td>$1,000</td>
            </tr>
            <tr>
              <td>Medical Insurance</td>
              <td>$100</td>
            </tr>
            <tr>
              <td><strong>Total Deductions</strong></td>
              <td><strong>$1,100</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <hr>
    <div class="row">
      <div class="col-md-6">
        <h2>Net Pay</h2>
        <h3>$3,400</h3>
      </div>
    </div>
  </div>
  <!-- Load Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-UrPO+I2TJf76bXaXk/W1sE/7DWoAIf8OFvckPHZxrPzjK7mWm1U6v9UyLHfDdL3g" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/js/bootstrap.min.js"></script>
</body>

</html>