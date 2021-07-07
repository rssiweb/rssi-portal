<div class="page-topbar">
    <div class="logo-area"> </div>
    <div class="quick-area">

        <ul class="pull-left info-menu  user-notify" id="menu">
            <button id="menu_icon"><i class="fa fa-bars" aria-hidden="true"></i></button>
            <li><a href="#"> <i class="fa fa-envelope"></i>
                    <!--<span class="badge">1</span>-->
                </a></li>

            <?php if (@$filterstatus == 'Active') {
            ?>
                <li class="profile dropdown">
                    <a href="#" class="close1"> <i class="fa fa-bell"></i><span class="badge">2</span></a>
                    <ul id="noti" class="dropdown-menu profile fadeIn" style="right:unset;">
                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1Dr3SOmKUPe7gjaQg_V1Y7VAwufrOWJdj/view" target="_blank">Guidelines for Exam invigilator.
                                <span class="label label-info">Jul 6, 2021</span>
                            </a>
                        </li>

                        <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1RR5MaWwSpogiRWjRK-REh-hXKfAbvttm/view" target="_blank">Invigilation Duty List for QT1/2021.
                                <span class="label label-info">Jul 3, 2021</span>
                            </a>
                        </li>
                    </ul>
                </li>
            <?php
            } else { ?>
                <li><a href="#"> <i class="fa fa-bell"></i>
                </li>
                <?php  }
                ?>

                <!------------------------------- ADMIN ---------------------------------->
                <?php if (@$role == 'Admin') {
                ?>
                <li class="profile dropdown">
                    <a href="#"> <i class="fas fa-project-diagram"></i></a>
                    <ul class="dropdown-menu profile fadeIn" style="right:unset">
                        <li style="height: unset;">
                            <a style="font-size:13px;" href="faculty.php"><i class="fas fa-user-tie"></i>&nbsp;RSSI Volunteer</a>
                        </li>

                        <li style="height: unset;">
                            <a style="font-size:13px;" href="student.php"><i class="fas fa-user-graduate"></i>&nbsp;RSSI Student</a>
                        </li>

                        <li style="height: unset;">
                            <a style="font-size:13px;" href="userlog.php"><i class="fas fa-server"></i>&nbsp;User log</a>
                        </li>
                        <li style="height: unset;">
                            <a style="font-size:13px;" href="library_status_admin.php"><i class="fas fa-book-reader"></i>&nbsp;Library Status</a>
                        </li>
                        <li style="height: unset;">
                            <a style="font-size:13px;" href="medistatus.php"><i class="fas fa-hand-holding-medical"></i>&nbsp;Medistatus</a>
                        </li>
                    </ul>
                </li>
            <?php
                } else {
                }
            ?>

        </ul>
        <!--<script>
            $('.close1').click(function(e) {

                $('.badge').delay(10).fadeOut(700);
                e.stopPropagation();
            });
        </script>-->

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
                <p class="profile-title"><?php echo $associatenumber ?></p>

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
                    <span class="menu-title">Examination</span>
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