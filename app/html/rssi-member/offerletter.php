<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}


date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');


if ($role == 'Admin') {
    @$id = strtoupper($_GET['get_id']);
    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$id'"); //select query for viewing users.
    $resultt = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'");
}

if ($role != 'Admin') {

    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'"); //select query for viewing users.
}


$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_all($resultt);


if (!$result) {
    echo "An error occurred.\n";
    exit;
}

?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <?php if ($role != 'Admin') { ?>
        <title>Offer Letter_<?php echo $user_check ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id != null) { ?>
        <title>Offer Letter_<?php echo $id ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id == null) { ?>
        <title>Offer Letter</title>
    <?php } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/css/style.css">
    <!-- Main css -->
    <style>
        @media screen {
            .no-display {
                display: none;
            }
        }

        @media print {
            table {
                page-break-inside: auto;
            }

            .report-footer {
                position: fixed;
                bottom: 0px;
                height: 20px;
                display: block;
                width: 90%;
                border-top: solid 1px #ccc;
                overflow: visible;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }
        }

        li {
            margin-bottom: 10px;
        }
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

</head>

<body>
    <div class="col-md-12">

        <section class="box" style="padding: 2%;">

            <?php if ($role == 'Admin') { ?>
                <form action="" method="GET" class="no-print">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">

                            <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate Id" value="<?php echo $id ?>" required>
                        </div>
                    </div>

                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                    </div>
                </form>
            <?php } ?>

            <?php if ($role != 'Admin') { ?>
                <div class="col no-print" style="width:99%;margin-left:1.5%;text-align:right;">
                    <button type="button" onclick="window.print()" name="print" class="btn btn-danger btn-sm" style="outline: none;"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button><br><br>
                </div>
            <?php } ?>

            <?php if ($resultArr != null) { ?>

                <?php foreach ($resultArr as $array) { ?>

                    <table class="table" border="0">
                        <thead>
                            <tr>
                                <td>
                                    <div class="col" style="display: inline-block; width:65%;">

                                        <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                                        <p style="font-size: small;">1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p>
                                    </div>
                                    <div class="col" style="display: inline-block; width:32%; vertical-align: top; text-align:right;">
                                        <!-- Scan QR code to check authenticity -->
                                        <?php

                                        $a = 'https://login.rssi.in/rssi-member/verification.php?get_id=';
                                        $b = $array['associatenumber'];
                                        $c = $array['photo'];

                                        $url = $a . $b;
                                        $url = urlencode($url); ?>
                                        <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=<?php echo $url ?>" width="75px" />
                                        <!-- <img src=<?php echo $c ?> width=80px height=80px /> -->
                                    </div>
                                </td>
                            </tr>
                        </thead>


                        <tbody>
                            <tr>
                                <td>


                                    <?php echo @date("d/m/Y", strtotime($date)) . '<br>RSSI/' . $array['associatenumber'] . '/' . $array['depb'] . '<br><br>

                                        ' . $array['fullname'] . '<br>
                                        ' . $array['currentaddress'] . '<br><br>

                                        <b>Sub: Letter of Offer</b><br><br>

                                        Dear ' . strtok($array['fullname'], ' ') . ',<br><br>

                                        <p>Thank you for exploring career opportunities with Rina Shiksha Sahayak Foundation (RSSI). You have successfully completed our initial selection process and we are pleased to make you an offer.</p>

                                        <p>This offer is based on your profile and performance in the selection process. You have been selected for the position of <b>' . substr($array['position'], 0, strrpos($array['position'], "-")) . ' (' . $array['job_type'] . ').</b> Your gross salary including all benefits will be <b>₹' . $array['salary'] . '/- per annum</b>, as per the terms and conditions set out herein.</p>'
                                    ?>

                                    <p>Please sign the offer letter and email the scanned copy to us at info@rssi.in as a token of your acceptance. If not accepted within 3 calendar days, it will be construed that you are not interested in this employment and this offer will be automatically withdrawn.</p>

                                    <p>Along with the offer letter, you are also given a joining letter indicating the details of your joining date and initial place of posting. However, the joining letter will be considered valid only after fulfilling all the prerequisites of the onboarding process.</p>

                                    <p>Please find attached the terms and conditions of your employment.</p>
                                    <p><b><u>COMPENSATION and BENEFITS</u></b></p>
                                    <ol>
                                        <li>You will be eligible for a gross salary of <b>₹<?php echo $array['salary'] / 12 ?>/- per month</b>.</li>
                                        <li>You will receive reimbursement for the reasonable and properly documented pre-approved expenses and costs you incur in carrying out your service.
                                            You may receive non-cash benefits, e.g. Free tickets, and free access to services but if these types of benefits are accepted regularly and have substantial value, they may need to be taxed.</li>
                                    </ol>
                                    <p><b><u>TERMS AND CONDITIONS</u></b></p>
                                    <ol start="3">
                                        <li>We hope your association with us will be a very long one. However, your association with the Organization can be terminated by Thirty (30) days notice in writing from either side, or you can buy out the notice period set by the Organization. However, in the event of any discrepancy or false information being found in your application or resume, willful neglect of your duties, breach of trust, gross indiscipline, or any other serious breach of duty which may be prejudicial to the interests of the Organization, has the discretion to terminate your Services immediately or with such notice as it may deem fit.</li>

                                        <li>You are not eligible to take more than 1 leave without notice during your tenure, in case of more than 1 leave without notice, the organization may decide for dismissal.</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <ol start="5">
                                        <li>

                                            <?php if ($array['engagement'] == 'Intern') { ?>
                                                You will be liable to pay RSSI ₹5000/- in case you fail to serve RSSI for at least 1 month from the original joining date in accordance with the Service Agreement clause.
                                            <?php } else if ($array['engagement'] == 'Employee' && substr($array['position'], 0, strrpos($array['position'], "-")) == 'Employee-Faculty cum Centre Incharge') { ?>
                                                You will be liable to pay RSSI ₹5000/- in case you fail to serve RSSI for at least 4 month from the original joining date in accordance with the Service Agreement clause.
                                            <?php } else if ($array['engagement'] == 'Employee' && substr($array['position'], 0, strrpos($array['position'], "-")) == 'Employee-Faculty') { ?>
                                                You will be liable to pay RSSI ₹5000/- in case you fail to serve RSSI for at least 4 month from the original joining date in accordance with the Service Agreement clause.
                                            <?php } else if (str_contains($array['position'], "Volunteer")) { ?>
                                                You will be liable to pay RSSI ₹5000/- in case you fail to serve RSSI for at least 4 month from the original joining date in accordance with the Service Agreement clause.
                                            <?php } ?>

                                        <li>You are expected to be active and responsive throughout your service period.</li>
                                        <li>Working Hours —
                                            <?php if (str_contains($array['position'], "Intern")) { ?>
                                                4 days a week, 2 hours per day
                                            <?php } else if (str_contains($array['position'], "Employee")) { ?>
                                                6 days a week, 2 hours per day + Administrative activities as required.
                                            <?php } else if (str_contains($array['position'], "Volunteer")) { ?>
                                                3 days a week, 2 hours per day
                                            <?php } ?>

                                            <br>The working hours can be extended up to a maximum of 30 minutes depending on the real time situation for other activities including prayer, and management i.e. student admission, and other non-academic activities etc. You should be flexible in terms of working hours.
                                        </li>
                                        <li>Primary responsibility —
                                            Responsible for teaching students, conducting tests and meetings, solving problems, evaluating students, and helping them improve their skills.To learn more about your role and responsibilities, please refer to the documents listed here.
                                            <ol type="i">
                                                <?php if (str_contains($array['position'], "Intern")) { ?>
                                                    <li>
                                                        <a href="https://drive.google.com/file/d/1UV1Y9d0w1dFh4YYV2Cj4pPpLTEUoCT7_/view" target="_blank">Responsibilities of the Teaching Intern</a>
                                                    </li>
                                                <?php } else if (str_contains($array['position'], "Centre Incharge")) { ?>
                                                    <li>
                                                        <a href="https://drive.google.com/file/d/1dhzOnSjyI4CgmY5AnLprJRCcGvBUvRuj/view" target="_blank">Responsibilities of the Teaching staff</a>
                                                    </li>
                                                    <li>
                                                        <a href="https://drive.google.com/file/d/1VOuqKRhyy3hycuiIMi022qKAzvPVd4dw/view" target="_blank">Responsibilities of Centre In charge / Asst. centre in-charge</a>
                                                    </li>
                                                <?php } else if (str_contains($array['position'], "Employee")) { ?>
                                                    <li>
                                                        <a href="https://drive.google.com/file/d/1dhzOnSjyI4CgmY5AnLprJRCcGvBUvRuj/view" target="_blank">Responsibilities of the Teaching staff</a>
                                                    </li>
                                                <?php } else if (str_contains($array['position'], "Volunteer")) { ?>
                                                    <li>
                                                        <a href="https://drive.google.com/file/d/1dhzOnSjyI4CgmY5AnLprJRCcGvBUvRuj/view" target="_blank">Responsibilities of the Teaching staff</a>
                                                    </li>
                                                <?php } ?>
                                            </ol>
                                        </li>
                                        <li>It is strictly prohibited to discuss any confidential information i.e. salary, increment percentage, appraisal rating (IPF) etc. with any other colleague or on social networking platforms like Facebook, Instagram, LinkedIn etc. HR can take legal action in case of any non-compliance.</li>

                                        <li>Please note, with this post you will/may get access to the confidential data of our volunteers/interns/employees (Aadhar card number, PAN, Voter Card number, etc.) and students (Aadhar number of students and parents, Date of birth, contact number - especially for girls students), please handle those carefully. No part of the RSSI website, pictures, or documents labelled as RSSI Internal or RSSI confidential may be republished, displayed, or distributed on or through any means or media without explicit prior written permission. You would be liable for any kind of Data Breach if it takes place using your credentials.</li>

                                        <li>The Organization will retain ownership of all intellectual properties generated during your service period as part of your duties or associated responsibilities. All intellectual property rights on all ‘works’ (as per Copyright Act, 1957 and subsequent amendments) generated or modified by you individually or as part of a team during your service period and as part of your service period will be wholly vested in the Organization. By this contract, you have also signed any associated documents to confirm the above ownership further. Unless permitted by an explicit agreement you are also bound to keep such matters confidential and shall use such work for the sole benefit of the Organization required by your employer.
                                            RSSI is a smoke, drug & alcohol-free workplace. Alcohol or drugs (unless prescribed for you by your treating medical practitioner) are not permitted to be carried with you or used on any RSSI premises or vehicles.</li>

                                        <li>By accepting this offer letter you are also accepting all HR policies applicable to RSSI <?php echo $array['engagement'] ?> like PoSH, Leave Policy etc. HR can take action in case of any non-compliance.</li>

                                        <li>Your association will be governed by and constructed in accordance with the laws of India and the courts of Kharagpur, West Bengal alone will have the jurisdiction.</li>
                                    </ol>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php if (str_contains($array['position'], "Employee")) { ?>

                                        <p><b><u>Increments and Promotions</u></b></p>
                                        <p>Your performance and contribution to RSSI will be an important consideration for salary increments and promotions. Salary increments and promotions will be based on RSSI&#39;s Compensation and Promotion policy.</p>

                                        <p><b><u>Maternity Leave</u></b></p>

                                        <p>Full-time women employees are eligible to avail of maternity leave of 12 weeks. Out of these 12 weeks, six weeks of leave is post-natal leave. In case of miscarriage or medical termination of pregnancy, a worker is entitled to two weeks of paid maternity leave. Adopting or commissioning mothers, may avail of maternity leave for four weeks. For more details on the benefits and eligibility, please refer to RSSI Policy - Maternity Leave once you join.</p>
                                    <?php } ?>

                                    <p><b><u>Disclaimer</u></b></p>

                                    <p>Candidates who have applied to RSSI and who have not been successful in clearing the RSSI selection process are not eligible to re-apply to RSSI within six months from the date on which the candidate had attended such selection Test and/or Interview. In case you are found to have re-applied to RSSI within six months of the previous unsuccessful attempt, the management reserves the right to revoke/withdraw the offer/appointment, without prejudice to its other rights.</p>

                                    <p><b><u>Rules and Regulations of the Company</u></b></p>
                                    <p>Your appointment will be governed by the policies, rules, regulations, practices, processes, and procedures of RSSI as applicable to you and the changes therein from time to time. The changes in the Policies will automatically be binding on you and no separate individual communication or notice will be served to this effect. However, the same shall be communicated on the internal portal/Phoenix.</p>

                                    <p>I look forward to your continued commitment to RSSI in the years to come.</p>


                                    <p>Sincerely,</p>
                                    <p><b>For Rina Shiksha Sahayak Foundation</b></p>
                                    <img src="../img/<?php echo $associatenumber ?>.png" width="65px" style="margin-bottom:-5px"><br>
                                    <p style="line-height: 2;"><?php echo $fullname ?><br>
                                        <?php if (str_contains($position, "Talent")) { ?>
                                            <?php echo 'Talent Acquisition & Academic Interface Program (AIP)' ?>
                                        <?php } else { ?>
                                            <?php echo $engagement ?>
                                        <?php } ?>

                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <!-- <p>RSSI is an equal opportunity employer that aims to integrate global diversity and inclusion at each level within our
                                        organization. Hiring decisions are solely made on the capability of an individual to perform a role. Any personal details like
                                        gender, age and nationality that may be provided by you during the course of application or selection process will be used for
                                        administrative records and all qualified applicants will receive consideration for employment without regard to this
                                        information.</p> -->
                                    <p><b><u>Personal Data Processing</u></b></p>
                                    <p>
                                        Your personal data collected and developed during recruitment process will be processed in accordance with the
                                        RSSI Data Privacy Policy. The personal data referred therein are details related to contact, family, education,
                                        personal identifiers issued by government, social profile, background references, previous employment and
                                        experience, medical history, skillset, proficiency and certifications, job profile and your career aspirations.
                                    </p>
                                    <p>
                                        It will be processed for various organizational purposes such as recruitment, onboarding, background check,
                                        project assignment, performance management, job rotation, career development including at leadership level,
                                        diversity and inclusion initiatives, global mobility, wellness program, statutory and legal requirements and specific
                                        organizational initiatives in force during your tenure in RSSI.
                                    </p>
                                    <p>
                                        After you join RSSI, there would be more sets of Personal Information (PI) attributes processed for various
                                        legitimate purposes. All of it will be processed with compliance to applicable laws and the RSSI Data Privacy
                                        Policy. In some scenarios of your PI processing, you will be provided with appropriate notice and/or explicit
                                        consent might be obtained from time to time.
                                    </p>
                                    <p>
                                        For the purposes mentioned above, your required PI may be shared with specific vendor organizations who
                                        provide services to RSSI, e.g. background check, health insurance, counselling, travel, transport and visa, payroll
                                        services, associate engagement activities, and financial and taxation services.
                                    </p>
                                    <p><b><u>Acknowledgement:</u></b></p>

                                    <p>I, <?php echo $array['fullname'] . '&nbsp;(' . $array['associatenumber'] . ')' ?>, acknowledge that I have read the offer letter and agree to the Terms and Conditions.
                                    <p>I certify that the information furnished in the registration form as well as in all other forms filled-in by me in conjunction with my association is factually correct and subject to verification by RSSI including Reference Check and Background Verification. I accept that an appointment given to me on this basis can be revoked and/ or terminated without any notice at any time in future if any information has been found to be false, misleading, deliberately omitted/suppressed.</p>
                                    <p>Also, I give my consent to processing my data by the RSSI.</p>
                                    <p>I also declare that there is no criminal case filed against me or pending against me in any Court of law in India or
                                        abroad and no restrictions are placed on my travelling anywhere in India or abroad for the purpose of business of the
                                        Organization.
                                    </p>
                                    <p style="margin-top:5%;">Signature of the Associate</p>
                                    <p>Date&nbsp;(dd/mm/yyyy)</p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>
                                    <p class=" report-footer">Private and Confidential</p>
                                    <p style="margin-top:5%;">Signature of the Associate</p>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                <?php }
            } else { ?>
                <p class="no-print">Please enter Associate ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>