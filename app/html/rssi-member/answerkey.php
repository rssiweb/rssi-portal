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
<style>
<!--
 /* Font Definitions */
 @font-face
	{font-family:"Cambria Math";
	panose-1:2 4 5 3 5 4 6 3 2 4;}
@font-face
	{font-family:Calibri;
	panose-1:2 15 5 2 2 2 4 3 2 4;}
@font-face
	{font-family:"Nirmala UI";
	panose-1:2 11 5 2 4 2 4 2 2 3;}
 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{margin-top:0cm;
	margin-right:0cm;
	margin-bottom:8.0pt;
	margin-left:0cm;
	line-height:107%;
	font-size:11.0pt;
	font-family:"Calibri",sans-serif;}
p.MsoHeader, li.MsoHeader, div.MsoHeader
	{mso-style-link:"Header Char";
	margin:0cm;
	font-size:11.0pt;
	font-family:"Calibri",sans-serif;}
a:link, span.MsoHyperlink
	{color:#0563C1;
	text-decoration:underline;}
span.HeaderChar
	{mso-style-name:"Header Char";
	mso-style-link:Header;}
.MsoPapDefault
	{margin-bottom:8.0pt;
	line-height:107%;}
 /* Page Definitions */
 @page WordSection1
	{size:612.0pt 792.0pt;
	margin:72.0pt 72.0pt 72.0pt 72.0pt;}
div.WordSection1
	{page:WordSection1;}
 /* List Definitions */
 ol
	{margin-bottom:0cm;}
ul
	{margin-bottom:0cm;}
-->
</style>

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
