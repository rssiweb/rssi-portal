<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}
?>

<html>

<head>
<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
<meta name=Generator content="Microsoft Word 15 (filtered)">

</head>

<body lang=EN-US link="#0563C1" vlink="#954F72" style='word-wrap:break-word'>

<div class=WordSection1>

<p class=MsoNormal><b><span style='font-size:16.0pt;line-height:107%'>LG3(Class
6) –</span></b></p>

<p class=MsoNormal>UP Board Class 6 Maths Model Paper (<span style='font-family:
"Nirmala UI",sans-serif'>&#2327;&#2339;&#2367;&#2340;</span>) <a
href="https://www.upboardsolutions.com/up-board-class-6-maths-model-paper/">https://www.upboardsolutions.com/up-board-class-6-maths-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 6 Science Model Paper (<span
style='font-family:"Nirmala UI",sans-serif'>&#2357;&#2367;&#2332;&#2381;&#2334;&#2366;&#2344;</span>
: <span style='font-family:"Nirmala UI",sans-serif'>&#2310;&#2323;</span> <span
style='font-family:"Nirmala UI",sans-serif'>&#2360;&#2350;&#2333;&#2375;&#2306;</span>
<span style='font-family:"Nirmala UI",sans-serif'>&#2357;&#2367;&#2332;&#2381;&#2334;&#2366;&#2344;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-6-science-model-paper/">https://www.upboardsolutions.com/up-board-class-6-science-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 6 Environment Model Paper (<span
style='font-family:"Nirmala UI",sans-serif'>&#2346;&#2352;&#2381;&#2351;&#2366;&#2357;&#2352;&#2339;</span>
: <span style='font-family:"Nirmala UI",sans-serif'>&#2361;&#2350;&#2366;&#2352;&#2366;</span>
<span style='font-family:"Nirmala UI",sans-serif'>&#2346;&#2352;&#2381;&#2351;&#2366;&#2357;&#2352;&#2339;</span>)
<a
href="https://www.upboardsolutions.com/up-board-class-6-environment-model-paper/">https://www.upboardsolutions.com/up-board-class-6-environment-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 6 English Model Paper <a
href="https://www.upboardsolutions.com/up-board-class-6-english-model-paper/">https://www.upboardsolutions.com/up-board-class-6-english-model-paper/</a></p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal><b><span style='font-size:16.0pt;line-height:107%'>LG2B(Class
4) –</span></b></p>

<p class=MsoNormal>UP Board Class 4 Maths Model Paper (<span style='font-family:
"Nirmala UI",sans-serif'>&#2327;&#2339;&#2367;&#2340;</span> : <span
style='font-family:"Nirmala UI",sans-serif'>&#2327;&#2367;&#2344;&#2340;&#2366;&#2352;&#2366;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-4-maths-model-paper/">https://www.upboardsolutions.com/up-board-class-4-maths-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 4 EVS Model Paper (<span style='font-family:
"Nirmala UI",sans-serif'>&#2361;&#2350;&#2366;&#2352;&#2366;</span> <span
style='font-family:"Nirmala UI",sans-serif'>&#2346;&#2352;&#2367;&#2357;&#2375;&#2358;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-4-evs-model-paper/">https://www.upboardsolutions.com/up-board-class-4-evs-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 4 Science Model Paper (<span
style='font-family:"Nirmala UI",sans-serif'>&#2357;&#2367;&#2332;&#2381;&#2334;&#2366;&#2344;</span>
: <span style='font-family:"Nirmala UI",sans-serif'>&#2346;&#2352;&#2326;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-4-science-model-paper/">https://www.upboardsolutions.com/up-board-class-4-science-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 4 English Model Paper Rainbow <a
href="https://www.upboardsolutions.com/up-board-class-4-english-model-paper/">https://www.upboardsolutions.com/up-board-class-4-english-model-paper/</a></p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal><b><span style='font-size:16.0pt;line-height:107%'>LG2A
(Class 2 and 3) –</span></b></p>

<p class=MsoNormal>UP Board Class 2 Maths Model Paper (<span style='font-family:
"Nirmala UI",sans-serif'>&#2327;&#2339;&#2367;&#2340;</span> : <span
style='font-family:"Nirmala UI",sans-serif'>&#2327;&#2367;&#2344;&#2340;&#2366;&#2352;&#2366;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-2-maths-model-paper/">https://www.upboardsolutions.com/up-board-class-2-maths-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 2 English Model Paper <a
href="https://www.upboardsolutions.com/up-board-class-2-english-model-paper/">https://www.upboardsolutions.com/up-board-class-2-english-model-paper/</a></p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal>UP Board Class 3 Maths Model Paper (<span style='font-family:
"Nirmala UI",sans-serif'>&#2327;&#2339;&#2367;&#2340;</span> : <span
style='font-family:"Nirmala UI",sans-serif'>&#2327;&#2367;&#2344;&#2340;&#2366;&#2352;&#2366;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-3-maths-model-paper/">https://www.upboardsolutions.com/up-board-class-3-maths-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 3 EVS Model Paper (<span style='font-family:
"Nirmala UI",sans-serif'>&#2361;&#2350;&#2366;&#2352;&#2366;</span> <span
style='font-family:"Nirmala UI",sans-serif'>&#2346;&#2352;&#2367;&#2357;&#2375;&#2358;</span>)
<a href="https://www.upboardsolutions.com/up-board-class-3-evs-model-paper/">https://www.upboardsolutions.com/up-board-class-3-evs-model-paper/</a></p>

<p class=MsoNormal>UP Board Class 3 English Model Paper Rainbow <a
href="https://www.upboardsolutions.com/up-board-class-3-english-model-paper/">https://www.upboardsolutions.com/up-board-class-3-english-model-paper/</a></p>

</div>

</body>

</html>
