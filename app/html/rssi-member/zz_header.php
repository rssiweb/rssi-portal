<div class="page-topbar">
    <div class="logo-area"> </div>
    <!-- <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1659756398/indian-flag-20_v5idmk.gif" width="50" class="over" /> -->
    <div class="quick-area">

        <ul class="pull-left info-menu  user-notify" id="menu">
            <button id="menu_icon"><span class="material-symbols-outlined" aria-hidden="true">
                    menu
                </span></button>
            <li>
                <!--<a href="#"> <i class="fa fa-envelope"></i>
                    <span class="badge">1</span>
                </a>-->
            </li>
            <?php if (@$filterstatus == 'Active') {
            ?>
                <li class="profile dropdown">
                    <a href="#" class="close1"> <span class="material-symbols-outlined">
                            folder_open
                        </span>
                        <!--<span class="badge">1</span>-->
                    </a>
                    <ul id="noti" class="dropdown-menu profile fadeIn" style="right:unset;/*height:300px; overflow-y: auto;*/">

                        <li style="height: unset;">
                            <a class="notification" href="https://youtube.com/shorts/ftA0TokZ28g" target="_blank">How to join Google space
                                <span class="badge label-info">Dec 18, 2022</span>
                                <!-- &nbsp;&nbsp;<span class="badge label-danger blink_me">new</span> -->
                            </a>
                        </li>

                        <li style="height: unset;">
                            <a class="notification" href="https://youtu.be/W3sWtsuRlTM" target="_blank">User Guide My Account
                                <span class="badge label-info">Dec 8, 2021</span>
                            </a>
                        </li>
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1yDOMpaOaHatAaDBjRyuoaOpuDGCf9h9S/view" target="_blank">How to Accept a Google Meeting Invite
                                <span class="badge label-info">Aug 2, 2021</span>
                            </a>
                        </li>
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1GnFHc-WzcmKmLvXo3I1FOWZvio6ETDbX/view" target="_blank">RSSI Library User Guide
                                <span class="badge label-info">Aug 1, 2021</span>
                            </a>
                        </li>

                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/18BQujDNktIXJLgRIzstlJuO9MBlHoWUk/view" target="_blank">Google Chat notification setting
                                <span class="badge label-info">Jul 7, 2021</span>
                            </a>
                        </li>
                        <li></li>
                    </ul>
                </li>

                <!------------------------------- ADMIN ---------------------------------->
                <li class="profile dropdown">
                    <a href="#" class="menu-link"><span class="material-symbols-outlined">
                            widgets
                        </span></a>
                    <ul class="dropdown-menu profile fadeIn" style="right:unset">
                        <?php if (@$role != 'Member') {
                        ?>

                            <li style="height: unset;">
                                <a style="font-size:13px;" href="student.php"><i class="fa-solid fa-user-graduate"></i>&nbsp;RSSI Student</a>
                            </li>
                        <?php }
                        ?>
                        <li style="height: unset;">
                            <a style="font-size:13px;" href="iexplore.php"><i class="fa-solid fa-arrow-up-a-z"></i>&nbsp;iExplore</a>
                        </li>
                        <li style="height: unset;">
                            <a style="font-size:13px;" href="policy.php"><i class="fa-regular fa-file-lines"></i>&nbsp;HR Policy</a>
                        </li>
                        <?php if (@$role == 'Offline Manager' || @$role == 'Admin') {
                        ?>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="dashboard.php"><span class="material-symbols-outlined">
                                        co_present
                                    </span>&nbsp;ProcessHub</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="visitor.php"><i class="fa-solid fa-building-user"></i>&nbsp;Visitor pass</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="gps.php"><i class="fa-solid fa-truck-fast"></i>&nbsp;GPS</a>
                            </li>
                        <?php }
                        ?>

                        <?php if (@$role == 'Admin') {
                        ?>

                            <li style="height: unset;">
                                <a style="font-size:13px;" href="faculty.php"><i class="fas fa-user-tie"></i>&nbsp;RSSI Faculty</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="leave_admin.php"><i class="fas fa-plane-departure"></i>&nbsp;Leave Management</a>
                            </li>

                            <li style="height: unset;">
                                <a style="font-size:13px;" href="my_book.php"><i class="fas fa-book-reader"></i>&nbsp;Library Status</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="medistatus.php"><i class="fas fa-hand-holding-medical"></i>&nbsp;Medistatus</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="reimbursementstatus.php"><i class="fa-solid fa-indian-rupee-sign"></i>&nbsp;Reimbursement</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="donationinfo_admin.php"><i class="fas fa-hand-holding-heart"></i>&nbsp;Donation</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="pms.php"><i class="fas fa-unlock-alt"></i>&nbsp;PMS</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="ams.php"><i class="fa-solid fa-bullhorn"></i>&nbsp;Announcement</a>
                            </li>
                        <?php
                        } else {
                        }
                        ?>
                        <li></li>
                    </ul>
                </li>
            <?php } else { ?>

                <li>
                    <a href="#"> <span class="material-symbols-outlined">
                            folder_open
                        </span>
                        <a href="#"> <span class="material-symbols-outlined">
                                page_info
                            </span>
                </li>
            <?php  }
            ?>
        </ul>

        <ul class="pull-right info-menu user-info">
            <li class="profile dropdown">
                <a href="#" data-toggle="dropdown" class="dropdown-toggle" aria-expanded="false">
                    <img src="<?php echo $photo ?>" class="img-circle img-inline">
                    <span class="hidden-xs"><?php echo $fullname ?> <i class="fa fa-angle-down"></i></span>
                </a>
                <ul class="dropdown-menu profile fadeIn">
                    <li>
                        <a>
                            Last synced: <?php echo $lastupdatedon ?>
                        </a>
                    </li>
                    <li>
                        <a>
                            Role assigned: <span class="badge label-danger"><?php echo $role ?></span>
                        </a>
                    </li>

                    <li>
                        <a href="resetpassword.php">
                            <i class="fa fa-wrench"></i>
                            Reset Password
                        </a>
                    </li>

                    <li class="last">
                        <a href="logout.php">
                            <i class="fa fa-lock"></i>
                            Sign Out
                        </a>
                    </li>
                </ul>


            </li>
        </ul>

    </div>
</div>
<script>
    $(function() {
        $(".dropdown").hover(
            function() {
                $('.dropdown-menu', this).stop(true, true).fadeIn("fast");
                $(this).toggleClass('open');
                $('b', this).toggleClass("caret caret-up");
            },
            function() {
                $('.dropdown-menu', this).stop(true, true).fadeOut("fast");
                $(this).toggleClass('open');
                $('b', this).toggleClass("caret caret-up");
            });
    });
</script>

<div class="page-sidebar expandit">
    <div class="sidebar-inner" id="main-menu-wrapper">
        <div class="profile-info row ">
            <div class="profile-image icon-container">
                <a href="#">
                    <img alt="" src="<?php echo $photo ?>" class="img-circle img-inline" class="img-responsive img-circle">
                    <div class="status-circle" style="background-color: #92C353;" title="Available"></div>
                </a>
            </div>
            <div class="profile-details">
                <h3>
                    <a href="#"><?php echo $fullname ?></a>
                </h3>
                <p class="profile-title"><?php echo $associatenumber ?>

                    <?php if (@$onleave != null) {
                    ?>
                        <span class="badge label-danger" style="display:-webkit-inline-box">on leave</span>
                    <?php
                    } else {
                    }
                    ?>
                </p>

            </div>
        </div>

        <ul class="wraplist" style="height: auto;">
            <!--          <li class="menusection">Main</li>-->
            <li>
                <a href="home.php" class="<?php echo $home_active ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-chalkboard-user"></i>
                    </span>
                    <span class="menu-title">Class Details</span>
                </a>
            </li>
            <li><a href="library.php" class="<?php echo $library_active ?>"><span class="sidebar-icon"><i class="fa-solid fa-book-open-reader"></i></i></span> <span class="menu-title">Library</span></a></li>
            <li>
                <a href="exam.php" class="<?php echo $exam_active ?>">
                    <span class="sidebar-icon"><i class="fas fa-spell-check"></i>
                    </span>
                    <span class="menu-title">Examination</span>
                    <!--&nbsp;<span style="font-family:Arial, Helvetica, sans-serif" class="badge label-warning">Update</span>-->
                </a>
            </li>
            <?php if (@$role != 'Member') {
            ?>
                <li><a href="leave.php" class="<?php echo $leave_active ?>"><span class="sidebar-icon"><i class="fas fa-plane-departure"></i></span> <span class="menu-title">Leave</span></a></li>
            <?php }
            ?>
            <li><a href="allocation.php" class="<?php echo $allocation_active ?>"><span class="sidebar-icon"><i class="fa-solid fa-calendar-check"></i></span> <span class="menu-title">My Allocation</span></a></li>
            <?php if (@$role != 'Member') {
            ?>
                <li><a href="my_appraisal.php" class="<?php echo $appraisal_active ?>"><span class="sidebar-icon"><i class="fa-solid fa-circle-nodes"></i></span> <span class="menu-title">My Appraisal</span></a></li>
            <?php }
            ?>
            <li><a href="document.php" class="<?php echo $document_active ?>"><span class="sidebar-icon"><i class="fas fa-folder-open"></i></i></span> <span class="menu-title">My Document</span></a></li>
            <?php if (@$role != 'Member') {
            ?>
                <li><a href="medimate.php" class="<?php echo $medimate_active ?>"><span class="sidebar-icon"><i class="fas fa-hand-holding-medical"></i></span> <span class="menu-title">Medimate</span></a></li>
            <?php }
            ?>
            <li><a href="reimbursement.php" class="<?php echo $reimbursement_active ?>"><span class="sidebar-icon"><i class="fa-solid fa-indian-rupee-sign"></i></span> <span class="menu-title">Reimbursement</span></a></li>

            <li><a href="myprofile.php" target="_blank" class="<?php echo $profile_active ?>"><span class="sidebar-icon"><i class="fa-solid fa-user"></i></span> <span class="menu-title">My Profile</span></a></li>

            <li>
                <a href="application.php" class="<?php echo $application_active ?>">
                    <span class="sidebar-icon"><i class="fa-brands fa-android"></i>
                    </span>
                    <span class="menu-title">Applications</span>
                </a>
            </li>
        </ul>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#menu_icon').click(function() {
            if ($('.page-sidebar').hasClass('expandit')) {
                $('.page-sidebar').addClass('collapseit');
                $('.page-sidebar').removeClass('expandit');
                $('.profile-info').addClass('short-profile');
                $('.logo-area').addClass('logo-icon');
                $('#main-content').addClass('sidebar_shift');
                $('.menu-title').css("display", "none");
            } else {
                $('.page-sidebar').addClass('expandit');
                $('.page-sidebar').removeClass('collapseit');
                $('.profile-info').removeClass('short-profile');
                $('.logo-area').removeClass('logo-icon');
                $('#main-content').removeClass('sidebar_shift');
                $('.menu-title').css("display", "inline-block");
            }
        });

    });
</script>
<script>
    $(document).ready(function() {
        if ($(window).width() < 760) {
            if ($('.page-sidebar').hasClass('expandit')) {
                $('.page-sidebar').addClass('collapseit');
                $('.page-sidebar').removeClass('expandit');
                $('.profile-info').addClass('short-profile');
                $('.logo-area').addClass('logo-icon');
                $('#main-content').addClass('sidebar_shift');
                $('.menu-title').css("display", "none");
            } else {}
        }
    });
</script>
<script>
    var inactivityTime = function() {
        var time;
        document.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onmousedown = resetTimer; // touchscreen presses
        document.ontouchstart = resetTimer;
        document.onclick = resetTimer; // touchpad clicks
        document.onkeydown = resetTimer; // onkeypress is deprectaed
        document.addEventListener('scroll', resetTimer, true); // improved; see comments

        function logout() {
            alert("Your session has expired, please login again.")
            location.href = 'logout.php'
            window.close()
        }

        function resetTimer() {
            clearTimeout(time);
            time = setTimeout(logout, 1800000)
            // 1000 milliseconds = 1 second
        }
    };
    window.addEventListener("load", function() {
        inactivityTime();
    }, false);
</script>
<script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>