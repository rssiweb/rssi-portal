<div class="page-topbar">
    <div class="logo-area"> </div>
    <!-- <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1654034999/PRIDE_RSSI_bxdnlu.gif" width="50" class="over" /> -->
    <div class="quick-area">

        <ul class="pull-left info-menu  user-notify" id="menu">
            <button id="menu_icon"><i class="fa fa-bars" aria-hidden="true"></i></button>
             <!--<li><a href="#"> <i class="fa fa-envelope"></i>
                   <span class="badge">1</span>
                </a></li>-->
                <?php if (@$filterstatus == 'Active') {
            ?>
                <li class="profile dropdown">
                    <a href="#" class="close1"> <i class="fas fa-folder-plus"></i><!--<span class="badge">1</span>--></a>
                    <ul id="noti" class="dropdown-menu profile fadeIn" style="right:unset;height:300px; /*overflow-y: auto;*/">
                    
                    <li style="height: unset;">
                            <a class="notification" href="https://docs.google.com/document/d/1CpnFSbSjn7wB2ey9d-ZtikgC0WlhT86fO7k3gOSLTQE/edit" target="_blank">National Module Syllabus
                               <span class="label label-info">Apr 5, 2022</span> <!--&nbsp;&nbsp;<span class="label label-danger blink_me">new</span>-->
                            </a>
                        </li>

                    <li style="height: unset;">
                            <a class="notification" href="https://drive.google.com/file/d/1BPcxYM3X9PgQZDoI_9aK5lpEwuvdXVby/view" target="_blank">How to join Google space
                                <span class="label label-info">Dec 3, 2021</span>
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
                            <a class="notification" href="https://drive.google.com/file/d/1-vF45CbqRnWX1IzvbHTC9d5iPBVN4jix/view" target="_blank">Instructions to students during examination
                                <span class="label label-info">Jul 7, 2021</span>
                            </a>
                        </li>
                        <li></li>
                    </ul>
                </li><!------------------------------- ACTIVE STUDENT ---------------------------------->
                <li class="profile dropdown">
                    <a href="#"> <i class="fas fa-sitemap"></i></a>
                    <ul class="dropdown-menu profile fadeIn" style="right:unset">

                        <?php if (@$filterstatus == 'Active') {
                        ?>

                            <li style="height: unset;">
                                <a style="font-size:13px;" href="facultyexp.php"><i class="fas fa-user-tie"></i>&nbsp;My Faculty</a>
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
                    <a href="#"> <i class="fas fa-folder-plus"></i>
                    <a href="#"> <i class="fas fa-project-diagram"></i>
                </li>
            <?php  }
            ?>
                        <li>
                <a href="https://g.page/r/CQkWqmErGMS7EAg/review" target="_blank"> <i class="fas fa-star-half-alt" title="Rate us"></i><!--<span class="badge" style="right:unset !important; left: 25px;">Rate us</span>-->
                </a>
            </li>
            <!--<li>
                <a href="https://www.youtube.com/channel/UC1gI7Hvsq90_QndfttoS6wA" target="_blank"> <i class="fab fa-youtube" title="Subscribe now"></i>
                </a>
            </li>-->
        </ul>

        <ul class="pull-right info-menu user-info">
            <li class="profile dropdown">
                <a href="#" data-toggle="dropdown" class="dropdown-toggle" aria-expanded="false">
                    <img src="<?php echo $photourl ?>" class="img-circle img-inline">
                    <span class="hidden-xs"><?php echo $studentname ?> <i class="fa fa-angle-down"></i></span>
                </a>
                <ul class="dropdown-menu profile fadeIn">
                <li>
                        <a>
                            Last synced: <?php echo $lastupdatedon ?>
                        </a>
                    </li>
                    <li>
                    <a href="resetpassword.php">
                            <i class="fa fa-wrench"></i>
                            Reset Password
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
            <div class="profile-image icon-container">
                <a href="#">
                    <img alt="" src="<?php echo $photourl ?>" class="img-circle img-inline" class="img-responsive img-circle">
                    <div class="status-circle" style="background-color: #92C353;" title="Available"></div>
                </a>
            </div>
            <div class="profile-details">
                <h3>
                    <a href="#"><?php echo $studentname ?></a>
                </h3>
                <p class="profile-title"><?php echo $student_id ?></p>

            </div>
        </div>

        <ul class="wraplist" style="height: auto;">
            <!--          <li class="menusection">Main</li>-->
            <li>
                <a href="home.php" class="<?php echo $home_active ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-chalkboard-user"></i>
                    </span>
                    <span class="menu-title">Classroom</span>
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

            <li>
                <a href="result.php" target="_blank" class="<?php echo $result_active ?>">
                    <span class="sidebar-icon"><i class="fa-solid fa-chart-column"></i>
                    </span>
                    <span class="menu-title">Results</span>
                </a>
            </li>

            <li><a href="leave.php" class="<?php echo $leave_active ?>"><span class="sidebar-icon"><i class="fas fa-plane-departure"></i></span> <span class="menu-title">Leave</span></a></li>

            <li><a href="allocation.php" class="<?php echo $allocation_active ?>"><span class="sidebar-icon"><i class="fa-solid fa-calendar-check"></i></span> <span class="menu-title">My Allocation</span></a></li>
            <li><a href="document.php" class="<?php echo $document_active ?>"><span class="sidebar-icon"><i class="fas fa-folder-open"></i></i></span> <span class="menu-title">My Document</span></a></li>

            <li><a href="profile.php" class="<?php echo $profile_active ?>"><span class="sidebar-icon"><i class="fa fa-user-circle"></i></span> <span class="menu-title">My Profile</span></a></li>

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
            }else{}
        }
    });
    </script>
<script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>