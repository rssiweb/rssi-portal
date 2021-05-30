<?php
session_start();
// Change this to your connection info.
$DATABASE_HOST = 'localhost';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';
$DATABASE_NAME = 'studentlogin';
// Try and connect using the info above.
$con = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);
if (mysqli_connect_errno()) {
    // If there is an error with the connection, stop the script and display the error.
    exit('Failed to connect to MySQL: ' . mysqli_connect_error());
}
// Now we check if the data from the login form was submitted, isset() will check if the data exists.
if (!isset($_POST['username'], $_POST['password'])) {
    // Could not get the data that should have been sent.
    exit('Please fill both the username and password fields!');
}
// Prepare our SQL, preparing the SQL statement will prevent SQL injection.
if ($stmt = $con->prepare('SELECT Category,Student_ID,Roll_Number,StudentName,Gender,Age,Class,Profile,Contact,GuardiansName,RelationwithStudent,StudentAAdhar,GuardianAAdhar,DateofBirth,PostalAddress,NameOfTheSubjects,PreferredBranch,NameOfTheSchool,NameOfTheBoard,StateofDomicile,EmailAddress,SchoolAdmissionRequired,Selectdateofformsubmission,Status,EffectiveFrom,Remarks,NameofVendorFoundation,PhotoURL,Familymonthlyincome,Totalnumberoffamilymembers,Medium,Fees,BookStstus,MyDocument,ProfileStatus,lastupdatedon,colors,ClassURL,Remarks1,Notice,Badge,Filterstatus,AllocationDate,Maxclass,Attd,Leaveapply,CLTaken,SLTaken,OTHTaken,FileName,Lastlogin FROM studentdata WHERE Student_ID = ?')) {
    // Bind parameters (s = string, i = int, b = blob, etc), in our case the username is a string so we use "s"
    $stmt->bind_param('s', $_POST['username']);
    $stmt->execute();
    // Store the result so we can check if the account exists in the database.
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result(
            $Category,
            $Student_ID,
            $Roll_Number,
            $StudentName,
            $Gender,
            $Age,
            $Class,
            $Profile,
            $Contact,
            $GuardiansName,
            $RelationwithStudent,
            $StudentAAdhar,
            $GuardianAAdhar,
            $DateofBirth,
            $PostalAddress,
            $NameOfTheSubjects,
            $PreferredBranch,
            $NameOfTheSchool,
            $NameOfTheBoard,
            $StateofDomicile,
            $EmailAddress,
            $SchoolAdmissionRequired,
            $Selectdateofformsubmission,
            $Status,
            $EffectiveFrom,
            $Remarks,
            $NameofVendorFoundation,
            $PhotoURL,
            $Familymonthlyincome,
            $Totalnumberoffamilymembers,
            $Medium,
            $Fees,
            $BookStstus,
            $MyDocument,
            $ProfileStatus,
            $lastupdatedon,
            $colors,
            $ClassURL,
            $Remarks1,
            $Notice,
            $Badge,
            $Filterstatus,
            $AllocationDate,
            $Maxclass,
            $Attd,
            $Leaveapply,
            $CLTaken,
            $SLTaken,
            $OTHTaken,
            $FileName,
            $Lastlogin
        );
        $stmt->fetch();
        // Account exists, now we verify the password.
        // Note: remember to use password_hash in your registration file to store the hashed passwords.
        if ($_POST['password'] === $colors) {
            // Verification success! User has logged-in!
            // Create sessions, so we know the user is logged in, they basically act like cookies but remember the data on the server.
            session_regenerate_id();
            $_SESSION['loggedin'] = TRUE;
            $_SESSION['Student_ID'] = $_POST['username'];
            $_SESSION['Category'] = $Category;
            $_SESSION['Student_ID'] = $Student_ID;
            $_SESSION['Roll_Number'] = $Roll_Number;
            $_SESSION['StudentName'] = $StudentName;
            $_SESSION['Gender'] = $Gender;
            $_SESSION['Age'] = $Age;
            $_SESSION['Class'] = $Class;
            $_SESSION['Profile'] = $Profile;
            $_SESSION['Contact'] = $Contact;
            $_SESSION['GuardiansName'] = $GuardiansName;
            $_SESSION['RelationwithStudent'] = $RelationwithStudent;
            $_SESSION['StudentAAdhar'] = $StudentAAdhar;
            $_SESSION['GuardianAAdhar'] = $GuardianAAdhar;
            $_SESSION['DateofBirth'] = $DateofBirth;
            $_SESSION['PostalAddress'] = $PostalAddress;
            $_SESSION['NameOfTheSubjects'] = $NameOfTheSubjects;
            $_SESSION['PreferredBranch'] = $PreferredBranch;
            $_SESSION['NameOfTheSchool'] = $NameOfTheSchool;
            $_SESSION['NameOfTheBoard'] = $NameOfTheBoard;
            $_SESSION['StateofDomicile'] = $StateofDomicile;
            $_SESSION['EmailAddress'] = $EmailAddress;
            $_SESSION['SchoolAdmissionRequired'] = $SchoolAdmissionRequired;
            $_SESSION['Selectdateofformsubmission'] = $Selectdateofformsubmission;
            $_SESSION['Status'] = $Status;
            $_SESSION['EffectiveFrom'] = $EffectiveFrom;
            $_SESSION['Remarks'] = $Remarks;
            $_SESSION['NameofVendorFoundation'] = $NameofVendorFoundation;
            $_SESSION['PhotoURL'] = $PhotoURL;
            $_SESSION['Familymonthlyincome'] = $Familymonthlyincome;
            $_SESSION['Totalnumberoffamilymembers'] = $Totalnumberoffamilymembers;
            $_SESSION['Medium'] = $Medium;
            $_SESSION['Fees'] = $Fees;
            $_SESSION['BookStstus'] = $BookStstus;
            $_SESSION['MyDocument'] = $MyDocument;
            $_SESSION['ProfileStatus'] = $ProfileStatus;
            $_SESSION['lastupdatedon'] = $lastupdatedon;
            $_SESSION['colors'] = $colors;
            $_SESSION['ClassURL'] = $ClassURL;
            $_SESSION['Remarks1'] = $Remarks1;
            $_SESSION['Notice'] = $Notice;
            $_SESSION['Badge'] = $Badge;
            $_SESSION['Filterstatus'] = $Filterstatus;
            $_SESSION['AllocationDate'] = $AllocationDate;
            $_SESSION['Maxclass'] = $Maxclass;
            $_SESSION['Attd'] = $Attd;
            $_SESSION['Leaveapply'] = $Leaveapply;
            $_SESSION['CLTaken'] = $CLTaken;
            $_SESSION['SLTaken'] = $SLTaken;
            $_SESSION['OTHTaken'] = $OTHTaken;
            $_SESSION['FileName'] = $FileName;
            $_SESSION['Lastlogin'] = $Lastlogin;

} else {
                // Incorrect password
                echo 'Incorrect username and/or password!';
            }
        } else {
            // Incorrect username
            echo 'Incorrect username and/or password!';
        }
    
        $stmt->close();
    }
    ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
</head>

<body>
    <div class="login">
        <h1>Login</h1>
        <form action="home.php" method="post">
            <label for="username">
					<i class="fas fa-user"></i>
				</label>
            <input type="text" name="username" placeholder="Username" id="username" required>
            <label for="password">
					<i class="fas fa-lock"></i>
				</label>
            <input type="password" name="password" placeholder="Password" id="password" required>
            <input type="submit" value="Login">
        </form>
    </div>
    <style>
        * {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "segoe ui", roboto, oxygen, ubuntu, cantarell, "fira sans", "droid sans", "helvetica neue", Arial, sans-serif;
            font-size: 16px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            background-color: #435165;
        }
        
        .login {
            width: 400px;
            background-color: #ffffff;
            box-shadow: 0 0 9px 0 rgba(0, 0, 0, 0.3);
            margin: 100px auto;
        }
        
        .login h1 {
            text-align: center;
            color: #5b6574;
            font-size: 24px;
            padding: 20px 0 20px 0;
            border-bottom: 1px solid #dee0e4;
        }
        
        .login form {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding-top: 20px;
        }
        
        .login form label {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 50px;
            height: 50px;
            background-color: #3274d6;
            color: #ffffff;
        }
        
        .login form input[type="password"],
        .login form input[type="text"] {
            width: 310px;
            height: 50px;
            border: 1px solid #dee0e4;
            margin-bottom: 20px;
            padding: 0 15px;
        }
        
        .login form input[type="submit"] {
            width: 100%;
            padding: 15px;
            margin-top: 20px;
            background-color: #3274d6;
            border: 0;
            cursor: pointer;
            font-weight: bold;
            color: #ffffff;
            transition: background-color 0.2s;
        }
        
        .login form input[type="submit"]:hover {
            background-color: #2868c7;
            transition: background-color 0.2s;
        }
    </style>
</body>

</html>