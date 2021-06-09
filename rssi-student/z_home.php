<?php  
session_start();
// Storing Session
$user_check=$_SESSION['sid'];  
  
if(!$_SESSION['sid'])  
{  
  
    header("Location: index.php");//redirect to the login page to secure the welcome page without login access.  
}  
?> 

<?php  
        include("student_data.php");  
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Index</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

<script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>

</head>

<body>

<script src="https://use.fontawesome.com/07b0ce5d10.js"></script>


    <div class="page-topbar">
        <div class="logo-area"> </div>
        <div class="quick-area">

            <ul class="pull-left info-menu  user-notify">
                <button id="menu_icon"><i class="fa fa-bars" aria-hidden="true"></i></button>
                <li><a href="#"> <i class="fa fa-envelope"></i>
                        <!--<span class="badge">1</span>-->
                    </a></li>
                <li><a href="#"> <i class="fa fa-bell"></i>
                        <span class="badge">1</span>
                    </a></li>
            </ul>

            <ul class="pull-right info-menu user-info">
                <li class="profile dropdown">
                    <a href="#" data-toggle="dropdown" class="dropdown-toggle" aria-expanded="false">
                        <img src="<?php echo $PhotoURL?>" class="img-circle img-inline">
                        <span><?php echo $StudentName ?> <i class="fa fa-angle-down"></i></span>
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
                    <a href="ui-profile.html">
                        <img alt="" src="<?php echo $PhotoURL ?>" class="img-circle img-inline class=" img-responsive img-circle">
                    </a>
                </div>
                <div class="profile-details">
                    <h3>
                        <a href="ui-profile.html"><?php echo $StudentName ?></a>
                    </h3>
                    <p class="profile-title"><?php echo $Student_ID ?></p>

                </div>
            </div>

            <ul class="wraplist" style="height: auto;">
                <!--          <li class="menusection">Main</li>-->
                <li><a href="class.php"><span class="sidebar-icon"><i class="fa fa-video-camera"></i></i></i></span> <span class="menu-title">Live Classes</span></a></li>
                <li><a href="allocation.php"><span class="sidebar-icon"><i class="fa fa-calendar"></i></span> <span class="menu-title">Allocation Details</span></a></li>
                <li><a href="application.php"><span class="sidebar-icon"><i class="fa fa-desktop"></i></span> <span class="menu-title">Applications</span></a></li>
                <li><a href="profile.php"><span class="sidebar-icon"><i class="fa fa-user-circle"></i></span> <span class="menu-title">My Profile</span></a></li>
            </ul>
        </div>
    </div>


    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-6">
                <section class="box"></section>
            </div>
            <div class="col-md-6">
                <section class="box"></section>
            </div>

            <div class="clearfix"></div>
            <!--**************clearfix**************-->

            <div class="col-md-12">
                <section class="box"></section>
            </div>

        </section>
    </section>
    <style>




/*///////////////////////////DASH BOARD NAVBAR///////////////////////////*/
.page-topbar {
    min-height: 60px;
    position: fixed;
    width: 100%;
    background-color: #35395c;
    top: 0px;
    left: 0px;
    z-index: 1999;
    box-shadow: 0 2px 4px rgba(33,33,33,.2);
    -webkit-box-shadow: 0 2px 4px rgba(33,33,33,.2);
    -moz-box-shadow: 0 2px 4px rgba(33,33,33,.2);
    -o-box-shadow: 0 2px 4px rgba(33,33,33,.2);
    -ms-box-shadow: 0 2px 4px rgba(33,33,33,.2);
    max-height: 60px;
}

.page-topbar .logo-area {
    background-image: url(../img/WebsiteHeaderLogo.png);
    background-repeat: no-repeat;
    background-size: 140px;
    margin-top: 0.75%;
    margin-left: 1%;
}
.page-topbar .logo-area {
    width: 240px;
    background-color: #35395c;
    display: block;
    min-height: 45px;
    float: left;
}

.page-topbar .logo-icon{
    background-image: url(../img/icon.png) !important;
    background-repeat: no-repeat !important;
    background-size: 37px;
    margin-top: 0.79%;
    margin-left: 1.2%;
}

.page-topbar .logo-icon {
    width: 100px !important;
    background-color: #35395c;
    display: block;
    min-height: 45px;
    float: left;
}
.page-topbar ul {
    margin: 0px;
    padding: 0px;
}
.page-topbar ul li {
    list-style-type: none;    
}
.page-topbar .user-info li a {
    display: inline-block;
    font-size: 13px;
    padding: 5px 8px;
    color: #fff;
    font-family: 'Comfortaa';
    font-weight: 400;
     position:relative;
}
.page-topbar .user-notify li a {
    display: inline-block;
    font-size: 16px;
    padding: 5px 15px;
    color: #fff;
    font-family: 'Comfortaa';
    font-weight: 400;
     position:relative;
}
.page-topbar .quick-area .info-menu {
    height: 60px;
    margin: 0;
    line-height: 50px;
    padding: 0 15px;
    display: flex;
}

.info-menu li{
   
}

.page-topbar .badge {
    display: inline-block;
    min-width: 10px;
    position: absolute;
    padding: 3px 6px;
    font-size: 10px;
    font-weight: bold;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    background-color: #F44336;
    border-radius: 10px;
    top: 8px;
    right: 0;
}
li.profile img {
    height: 30px;
    width: 30px;
    top: 0px;
    right: 10px;
    position: relative;
    background-color: #999999;
}

/* Ripple magic */
.page-topbar button:focus {
     outline: none;
}
 .page-topbar button {
    border: none;
    width: 50px;
    cursor: pointer;
    height: 50px;
    color: white;
    /*padding: 11px 15px;*/
    font-size: 21px;
    border-radius: 100%;
    background: transparent;
    margin-top: 5px;
    position: relative;
    float: left;
}
 .page-topbar button{
     position: relative;
     overflow: hidden;
}
 .page-topbar button:after {
     content: '';
     position: absolute;
     top: 50%;
     left: 50%;
     width: 5px;
     height: 5px;
     background: rgba(255, 255, 255, .5);
     opacity: 0;
     border-radius: 100%;
     transform: scale(1, 1) translate(-50%);
     transform-origin: 50% 50%;
}
 @keyframes ripple {
     0% {
         transform: scale(0, 0);
         opacity: 1;
    }
     20% {
         transform: scale(25, 25);
         opacity: 1;
    }
     100% {
         opacity: 0;
         transform: scale(40, 40);
    }
}
 .page-topbar button:focus:not(:active)::after {
     animation: ripple 1s ease-out;
}
.page-topbar li .dropdown-menu.profile {
    max-width: 180px;
    height: auto;
    padding: 0px;
}
.dropdown-menu {
    width: 360px;
    border-radius: 0px;
    -o-border-radius: 0px;
    -ms-border-radius: 0px;
    -moz-border-radius: 0px;
    -webkit-border-radius: 0px;
    line-height: 23px;
    font-size: 14px;
    padding-bottom: 0px;
    border: 0px solid transparent;
    box-shadow: 0 0 20px 0 rgba(33,33,33,.2);
    -webkit-box-shadow: 0 0 20px 0 rgba(33,33,33,.2);
    -moz-box-shadow: 0 0 20px 0 rgba(33,33,33,.2);
    -o-box-shadow: 0 0 20px 0 rgba(33,33,33,.2);
    -ms-box-shadow: 0 0 20px 0 rgba(33,33,33,.2);
    margin-top: -1px;
    padding-top: 0px;
}
.page-topbar .dropdown-menu {
    background: #ffffff;
    border: 1px solid #f5f5f5;
}
.page-topbar li .dropdown-menu.profile li {
    line-height: 40px;
    display: block;
    height: 40px;
    margin: 0px;
}
.page-topbar li .dropdown-menu.profile li a {
    display: block;
    border-bottom: 1px solid #eeeeee;
    padding: 12px 8px;
}

.page-topbar .dropdown-menu:after, .page-topbar .dropdown-menu:before {
    bottom: 100%;
    left: 22px;
    border: solid transparent;
    content: " ";
    height: 0;
    width: 0;
    position: absolute;
    pointer-events: none;
}
.page-topbar .dropdown-menu:before {
    border-color: rgba(194, 225, 245, 0);
    border-bottom-color: rgba(80, 80, 80, .1);
    border-width: 10px;
    margin-left: -10px;
}
.page-topbar li .dropdown-menu li a {
    text-decoration: none;
    color: #717171;
}


/*//////////////////////////SIDE BAR NAVE//////////////////////////*/

.page-sidebar {
    height: auto;
    width: 260px;
    position: fixed;
    padding: 0px;
    background-color: #ffffff;
    margin-top: 60px;
    overflow: hidden;
    box-shadow: 0 0 10px 0 rgba(33,33,33,.2);
    -webkit-box-shadow: 0 0 10px 0 rgba(33,33,33,.2);
    -moz-box-shadow: 0 0 10px 0 rgba(33,33,33,.2);
    -o-box-shadow: 0 0 10px 0 rgba(33,33,33,.2);
    -ms-box-shadow: 0 0 10px 0 rgba(33,33,33,.2);
    min-height: 100%;
    height: auto;
}
.page-sidebar.fixedscroll {
    position: fixed;
}
.page-sidebar.collapseit {
    width: 60px;
    margin-left: 0px !important;
    z-index: 99;
}
.page-sidebar .profile-info {
    background: transparent;
    padding-top: 15px;
    padding-bottom: 10px;
    padding-left: 20px;
    padding-right: 15px;
    display: flex;
}
.page-sidebar .profile-image {
    padding-right: 5px;
    display: inline-block;
}
.profile-info .profile-image img {
    height: 65px;
    width: 65px;
    float: right;
    top: 5px;
    left: 5px;
    position: relative;    
}
.short-profile img {
    height: 45px !important;
    width: 45px !important; 
    left: 2px !important;
}
.short-profile .profile-details{
    display: none;
}
.profile-info .profile-details h3 {
    margin: 19px 0px 6px 14px;
}
.profile-info .profile-details .profile-title {
    color: rgba(174, 178, 183, 1.0);
    font-size: 13px;
    margin: 0px 0px 6px 14px;
}
#main-menu-wrapper ul {
    padding: 0px;
    margin: 0px;
    padding-left: 0px;
    list-style-type: none;
    font-family: 'Comfortaa';
    font-weight: 400;
    font-size: 14px;
}
#main-menu-wrapper .menusection {    
    font-weight: 400;
    font-size: 13px;
    line-height: 40px;
    padding-left: 24px;
    margin: 10px 0 0px 0;
    color: #9e9e9e;
    text-transform: uppercase;
}
.profile-info .profile-details h3 a {    
    font-weight: 400;
    color: #757575;
    font-size: 19px;
    line-height: 23px;
    text-decoration: none;
}
#main-menu-wrapper li a {
    display: block;
    line-height: 42px;
    min-height: 42px;
    height: auto;
    color: #424242;
    text-decoration: none;
/*    padding: 14px 0px;*/
    padding-left: 5px;
    clear: both;
    border-left: 4px solid transparent;
    white-space: nowrap;
/*    font-size: 18px;*/
}
#main-menu-wrapper .fa {
    font-size: 14px;
}
#main-menu-wrapper li a i {
    display: inline-block;
    width: 40px;
    height: 34px;
    line-height: 34px;
    text-align: center;
    top: 0px;
    position: relative;
}
#main-menu-wrapper li a:hover {
    background-color: rgba(33, 33, 33, 0.1);
    border-left: 4px solid #35395c;    
}



/*MAIN CONTENT/////////////////////////////////////////////////////////////*/

#main-content {
    margin-left: 260px;
    display: block;
}
#main-content.sidebar_shift {
    margin-left: 60px;
    margin-right: 0px;
}
.main-wrapper {
    margin-top: 60px;
    display: inline-block;
    width: 100%;
    padding: 15px 15px 0 15px;
    margin-left: 0px;
    margin-right: 0px;
}
section.box {
    background-color: #ffffff;
    margin: 15px 0;
    min-height: 350px;
    box-shadow: 0 1px 3px rgba(33,33,33,.2);
    -webkit-box-shadow: 0 1px 3px rgba(33,33,33,.2);
    -moz-box-shadow: 0 1px 3px rgba(33,33,33,.2);
    -o-box-shadow: 0 1px 3px rgba(33,33,33,.2);
    -ms-box-shadow: 0 1px 3px rgba(33,33,33,.2);
}</style>

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
</body>
</html>