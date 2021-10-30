<div class="page-topbar">
    <div class="logo-area"> </div>
    <div class="quick-area">

        <ul class="pull-left info-menu  user-notify" id="menu">
            <button id="menu_icon"><i class="fa fa-bars" aria-hidden="true"></i></button>
            <li>
                <a href="#"> <i class="fa fa-envelope"></i>
                    <!--<span class="badge">1</span>-->
                </a>
            </li>
            <?php if (@$filterstatus == 'Active') {
            ?>
                <li class="profile dropdown">
                    <a href="#" class="close1"> <i class="fas fa-folder-plus"></i><span class="badge">1</span></a>
                    <ul id="noti" class="dropdown-menu profile fadeIn" style="right:unset;/*height:300px; overflow-y: auto;*/">

                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1UV1Y9d0w1dFh4YYV2Cj4pPpLTEUoCT7_/view" target="_blank">Responsibilities of the Teaching Intern
                                <span class="label label-info">Aug 2, 2021</span>
                            </a>
                        </li>
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1yDOMpaOaHatAaDBjRyuoaOpuDGCf9h9S/view" target="_blank">How to Accept a Google Meeting Invite
                                <span class="label label-info">Aug 2, 2021</span>
                            </a>
                        </li>
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1GnFHc-WzcmKmLvXo3I1FOWZvio6ETDbX/view" target="_blank">RSSI Library User Guide
                                <span class="label label-info">Aug 1, 2021</span>
                            </a>
                        </li>
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1YOhIKnOe1Ygt7wFS-Jh3Fe8Fm4qUKiXG/view?usp=sharing" target="_blank">Examiner & Reviewer User Guide
                                <span class="label label-info">Jul 20, 2021</span>
                            </a>
                        </li>

                        <!--<li style="height: unset;">
                            <a class="notification" href="https://docs.google.com/spreadsheets/d/12y-AWluI4FyefSLvh-PXvSxTZXMOZQzpYVMHeLR8ivc/edit?usp=sharing" target="_blank">Exam Attendance Tracker_QT1/2021
                                <span class="label label-info">Jul 9, 2021</span>
                            </a>
                        </li>-->
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/18BQujDNktIXJLgRIzstlJuO9MBlHoWUk/view" target="_blank">Google Chat notification setting
                                <span class="label label-info">Jul 7, 2021</span>
                            </a>
                        </li>

                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1no7KiM1Kqt9_elGZgNCzF2HAW8um85Un/view" target="_blank">Instructions to invigilators during examination
                                <span class="label label-info">Jul 6, 2021</span>
                            </a>
                        </li>
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1u8iQR68_E0buYgkjvs3bmM_nONobKF-1/view" target="_blank">Document submission and Exit interview for Interns
                                <span class="label label-info">Jun 30, 2021</span>
                            </a>
                        </li>

                        <!--<li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1RR5MaWwSpogiRWjRK-REh-hXKfAbvttm/view" target="_blank">Invigilation Duty List for QT1/2021.
                                <span class="label label-info">Jul 3, 2021</span>
                            </a>
                        </li>-->
                        <li></li>
                    </ul>
                </li>

                <!------------------------------- ADMIN ---------------------------------->
                <li class="profile dropdown">
                    <a href="#"> <i class="fas fa-project-diagram"></i></a>
                    <ul class="dropdown-menu profile fadeIn" style="right:unset">

                        <li style="height: unset;">
                            <a style="font-size:13px;" href="student.php"><i class="fas fa-user-graduate"></i>&nbsp;RSSI Student</a>
                        </li>

                        <?php if (@$role == 'Admin') {
                        ?>

                            <li style="height: unset;">
                                <a style="font-size:13px;" href="faculty.php"><i class="fas fa-user-tie"></i>&nbsp;RSSI Volunteer</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="facultyexp.php"><i class="fas fa-user-tag"></i>&nbsp;Faculty Experience Dashboard</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="leave_admin.php"><i class="fas fa-plane-departure"></i>&nbsp;Leave Tracker</a>
                            </li>

                            <li style="height: unset;">
                                <a style="font-size:13px;" href="userlog.php"><i class="fas fa-server"></i>&nbsp;User log</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="library_status_admin.php"><i class="fas fa-book-reader"></i>&nbsp;Library Status</a>
                            </li>
                            <li style="height: unset;">
                                <a style="font-size:13px;" href="medistatus_admin.php"><i class="fas fa-hand-holding-medical"></i>&nbsp;Medistatus</a>
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
                    <a href="#"> <i class="fa fa-bell"></i>
                        <a href="#"> <i class="fas fa-project-diagram"></i>
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
                        <a href="#settings">
                            <i class="fa fa-wrench"></i>
                            Settings
                        </a>
                    </li>
                    <li>
                        <a href="profile.php">
                            <i class="fa fa-user"></i>
                            Profile
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
            <div class="profile-image ">
                <a href="#">
                    <img alt="" src="<?php echo $photo ?>" class="img-circle img-inline class=" img-responsive img-circle">
                </a>
            </div>
            <div class="profile-details">
                <h3>
                    <a href="#"><?php echo $fullname ?></a>
                </h3>
                <p class="profile-title"><?php echo $associatenumber ?>

                    <?php if (@$on_leave == 'on leave') {
                    ?>
                        <span class="label label-danger" style="display:-webkit-inline-box">on leave</span>
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
                    <span class="sidebar-icon"><i class="fas fa-chalkboard-teacher"></i>
                    </span>
                    <span class="menu-title">Class Details</span>
                </a>
            </li>

            <li>
                <a href="application.php" class="<?php echo $application_active ?>">
                    <span class="sidebar-icon"><i class="fa fa-desktop"></i>
                    </span>
                    <span class="menu-title">Applications</span>
                </a>
            </li>

            <li><a href="library.php" class="<?php echo $library_active ?>"><span class="sidebar-icon"><i class="fas fa-book-reader"></i></span> <span class="menu-title">Library</span></a></li>

            <li>
                <a href="exam.php" class="<?php echo $exam_active ?>">
                    <span class="sidebar-icon"><i class="fas fa-spell-check"></i>
                    </span>
                    <span class="menu-title">Examination</span>&nbsp;<span style="font-family:Arial, Helvetica, sans-serif" class="label label-warning">Update</span>
                </a>
            </li>

            <li><a href="leave.php" class="<?php echo $leave_active ?>"><span class="sidebar-icon"><i class="fas fa-plane-departure"></i></span> <span class="menu-title">Leave</span></a></li>
            <li><a href="allocation.php" class="<?php echo $allocation_active ?>"><span class="sidebar-icon"><i class="fa fa-calendar"></i></span> <span class="menu-title">My Allocation</span></a></li>

            <li><a href="my_appraisal.php" class="<?php echo $appraisal_active ?>"><span class="sidebar-icon"><i class="fas fa-chart-line"></i></span> <span class="menu-title">My Appraisal</span></a></li>

            <li><a href="document.php" class="<?php echo $document_active ?>"><span class="sidebar-icon"><i class="fas fa-folder-open"></i></i></span> <span class="menu-title">My Document</span></a></li>

            <li><a href="medimate.php" class="<?php echo $medimate_active ?>"><span class="sidebar-icon"><i class="fas fa-hand-holding-medical"></i></span> <span class="menu-title">Medimate</span></a></li>

            <li><a href="profile.php" class="<?php echo $profile_active ?>"><span class="sidebar-icon"><i class="fa fa-user-circle"></i></span> <span class="menu-title">My Profile</span></a></li>
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
<script src="https://use.fontawesome.com/releases/v5.15.3/js/all.js" data-auto-replace-svg="nest"></script>
<!-- Messenger Chat Plugin Code -->
<div id="fb-root"></div>

<!-- Your Chat Plugin code -->
<div id="fb-customer-chat" class="fb-customerchat">
</div>

<script>
    var chatbox = document.getElementById('fb-customer-chat');
    chatbox.setAttribute("page_id", "215632685291793");
    chatbox.setAttribute("attribution", "biz_inbox");

    window.fbAsyncInit = function() {
        FB.init({
            xfbml: true,
            version: 'v12.0'
        });
    };

    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s);
        js.id = id;
        js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>