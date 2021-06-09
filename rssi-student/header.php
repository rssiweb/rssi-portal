<div class="page-topbar">
    <div class="logo-area"> </div>
    <div class="quick-area">

        <ul class="pull-left info-menu  user-notify">
            <button id="menu_icon"><i class="fa fa-bars" aria-hidden="true"></i></button>
            <li><a href="#"> <i class="fa fa-envelope"></i>
                    <!--<span class="badge">1</span>-->
                </a></li>
            <li><a href="#"> <i class="fa fa-bell"></i>
                    <!--<span class="badge">1</span> title="1 new notification"-->
                </a></li>
        </ul>

        <ul class="pull-right info-menu user-info">
            <li class="profile dropdown">
                <a href="#" data-toggle="dropdown" class="dropdown-toggle" aria-expanded="false">
                    <img src="<?php echo $photourl ?>" class="img-circle img-inline">
                    <span class="hidden-xs"><?php echo $studentname ?> <i class="fa fa-angle-down"></i></span>
                </a>
                <ul class="dropdown-menu profile fadeIn">
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
                    <img alt="" src="<?php echo $photourl ?>" class="img-circle img-inline class=" img-responsive img-circle">
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
                    <span class="sidebar-icon"><i class="fas fa-chalkboard-teacher"></i>
                    </span>
                    <span class="menu-title">Classroom</span>
                </a>
            </li>

            <li>
                <a href="application.php" class="<?php echo $application_active ?>">
                    <span class="sidebar-icon"><i class="fa fa-desktop"></i>
                    </span>
                    <span class="menu-title">Applications</span>
                </a>
            </li>

            <li>
                <a href="exam.php" class="<?php echo $exam_active ?>">
                    <span class="sidebar-icon"><i class="fas fa-spell-check"></i>
                    </span>
                    <span class="menu-title">Examination</span>
                </a>
            </li>

            <li>
                <a href="result.php" class="<?php echo $result_active ?>">
                    <span class="sidebar-icon"><i class="fas fa-poll-h"></i>
                    </span>
                    <span class="menu-title">Results</span>
                </a>
            </li>

            <li><a href="leave.php" class="<?php echo $leave_active ?>"><span class="sidebar-icon"><i class="fas fa-plane-departure"></i></span> <span class="menu-title">Leave</span></a></li>

            <li><a href="allocation.php" class="<?php echo $allocation_active ?>"><span class="sidebar-icon"><i class="fa fa-calendar"></i></span> <span class="menu-title">My Allocation</span></a></li>

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
            }else{}
        }
    });
    </script>
<script src="https://use.fontawesome.com/releases/v5.15.3/js/all.js" data-auto-replace-svg="nest"></script>