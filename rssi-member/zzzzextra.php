                <!--**************QUESTION PAPER SUBMISSION TIMER**************-->
                <!--<?php
                    if ((@$questionflag == 'Y') && $filterstatus == 'Active') {
                        ?>
                        <div class="alert alert-success" role="alert" style="text-align: -webkit-center;">Being on time is a wonderful thing. You have successfully submitted the QT1/2021 question paper.
                        </div>
                    <?php
                        } else if ((@$questionflag == 'NA' || @$questionflag == 'YL') && $filterstatus == 'Active') {
                    ?>
                    <?php
                        } else if ((@$questionflag == null || @$questionflag != 'Y') && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-danger" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; left for question paper submission.
                        </div>
                        <script>
                            // Set the date we're counting down to
                            var countDownDate = new Date("<?php echo $qpaper ?>").getTime();
    
                            // Update the count down every 1 second
                            var x = setInterval(function() {
    
                                // Get today's date and time
                                var now = new Date().getTime();
    
                                // Find the distance between now and the count down date
                                var distance = countDownDate - now;
    
                                // Time calculations for days, hours, minutes and seconds
                                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
                                // Output the result in an element with id="demo"
                                document.getElementById("demo").innerHTML = days + "d " + hours + "h " +
                                    minutes + "m " + seconds + "s ";
    
                                // If the count down is over, write some text 
                                if (distance < 0) {
                                    clearInterval(x);
                                    document.getElementById("demo").innerHTML = "EXPIRED";
                                }
                            }, 1000);
                        </script>
                    <?php
                        } else {
                        }
                    ?>-->
                    <!--**************QUESTION PAPER SUBMISSION END**************-->


                    <!--**************VACCINATION CONFIRMATION************** || strpos(@$vaccination, $word) !== false)-->
    <!--<?php
        $word = "Not vaccinated";
        if (@$vaccination == null && $filterstatus == 'Active') {
        ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid" type="text" value="<?php echo $associatenumber ?>" readonly>-->
    <!--<input type="hidden" type="text" name="status" id="count" value="" readonly required>-->
    <!--<p>Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Please select your COVID-19 vaccination status from the list below?</p>
                <div class="center">
                <select name="status" class="form-control cmb" style="width:max-content;" placeholder="" required>
                <option value="" disabled selected hidden>Select Status</option>
                  <option>I have taken both doses of the vaccine</option>
                  <option>I have taken my first dose of vaccine</option>
                  <option>I haven't taken any dose of vaccine yet, but will take it soon</option>
                  <option>I haven't taken any dose of vaccine yet, also will not take it in future</option>
                </select>
                <div>
                <br>
                <button type="submit" id="vaccinated" class="close-button btn btn-success">Save
                </button>-->
    <!--&nbsp;
                <button type="submit" id="notvaccinated" class="close-button btn btn-danger">
                    <i class="fas fa-thumbs-down" aria-hidden="true"></i>&nbsp;Not vaccinated
                </button>-->
    <br><br>
    </form>
    </div>
    <!--<script>
            $('#vaccinated').click(function() {
                $('#count').val('Vaccinated');
            });

            $('#notvaccinated').click(function() {
                $('#count').val('Not vaccinated');
            });
        </script>-->
    <!--<script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbzRMd98T75iCUIe9ZwMYatPiJcmzzmgleL3epY7WwquEyyfRwg/exec'
            const form = document.forms['submit-to-google-sheet']

            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })
        </script>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        <script>
            $(document).ready(function() {

                if (Boolean(readCookie('vacc'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("vacc", "2 days", 2);
                    //return false;
                });

                function createCookie(name, value, days) {
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        var expires = "; expires=" + date.toGMTString();
                    } else var expires = "";
                    document.cookie = name + "=" + value + expires + "; path=/";
                }



                function readCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    createCookie(name, "", -1);
                }

            });
        </script>
    <?php
        } else {
    ?>
    <?php } ?>-->
    <!--**************QUESTION PAPER SUBMISSION CONFIRMATION**************-->
    <!--<?php
        if ((@$questionflag == null || @$questionflag != 'Y') && $filterstatus == 'Active') {
        ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Do you know how to submit QT1/2021 question paper? For more details please visit the <span class="noticet"><a href="exam.php" target="_blank">Examination Portal.</a></span></p><br>
                <button type="submit" id="yes" class="close-button2 btn btn-success" style="width: 90%; white-space:normal !important;word-wrap:break-word;">
                    <i class="fas fa-smile" style="font-size:17px" aria-hidden="true"></i>&nbsp;Yes, I know the process. I will share the question paper as per the stipulated time.</button><br><br>
                <button type="submit" id="no" class="close-button2 btn btn-default" style="width: 90%; white-space:normal !important;word-wrap:break-word;">
                    <i class="far fa-meh" style="font-size:17px" aria-hidden="true"></i>&nbsp;I have not been assigned any question paper for this quarter.
                </button>
                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('Agree');
            });

            $('#no').click(function() {
                $('#count2').val('NA');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbyiOP3O__HFeipBtF5EFnv1fID-VTMbnM8yt64P7qBtHmHgvi1R/exec'
            const form = document.forms['submit-to-google-sheet2']

            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })
        </script>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        <script>
            $(document).ready(function() {

                if (Boolean(readCookie('name1'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("name1", "4 days", 4);
                    //return false;
                });

                function createCookie(name, value, days) {
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        var expires = "; expires=" + date.toGMTString();
                    } else var expires = "";
                    document.cookie = name + "=" + value + expires + "; path=/";
                }



                function readCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    createCookie(name, "", -1);
                }

            });
        </script>
    <?php
        } else if (@$questionflag != 'NA' && $filterstatus == 'Active') {
    ?>
    <?php } else {
        } ?>-->
    <!--**************JOIN GOOGLE CHAT CONFIRMATION**************-->
    <!--<?php
        if (@$googlechat == null && $filterstatus == 'Active') {
        ?>

        <div id="thoverX" class="thover pop-up3"></div>
        <div id="tpopupX" class="tpopup pop-up3">
            <form name="submit-to-google-sheet3" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername3" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid3" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status3" id="count3" value="" readonly required>
                <div style="padding-left:5%;padding-right:5%"><p>Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Did you know that from August 1st all official communication will be in Google Chat? If you haven't joined the Google Chatroom yet, please join the <span class="noticet"><a href="https://mail.google.com/chat/u/0/#chat/space/AAAA3h1BiX4" target="_blank">RSSI Faculty</a></span> group now.</p></div>

                <button onclick='window.location.href="https://mail.google.com/chat/u/0/#chat/space/AAAA3h1BiX4"' type="submit" id="join" class="close-button3 btn btn-success" style="white-space:normal !important;word-wrap:break-word;">
                    <i class="fas fa-smile" style="font-size:17px" aria-hidden="true"></i>&nbsp;Yes, I have joined.</button>
                <br><br>
            </form>
        </div>
        <script>
            $('#join').click(function() {
                $('#count3').val('Joined');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbzFxxBLaI4b_gQFpS7IPLZLSgmaQjQWSa7o-qGDRF8y_xIpLrde/exec'
            const form = document.forms['submit-to-google-sheet3']

            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })
        </script>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        <script>
            $(document).ready(function() {

                if (Boolean(readCookie('googlechat'))) {
                    $('.pop-up3').hide();
                    $('.pop-up3').fadeOut(1000);
                }
                $('.close-button3').click(function(e) {

                    $('.pop-up3').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("googlechat", "2 days", 2);
                    //return false;
                });

                function createCookie(name, value, days) {
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        var expires = "; expires=" + date.toGMTString();
                    } else var expires = "";
                    document.cookie = name + "=" + value + expires + "; path=/";
                }



                function readCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    createCookie(name, "", -1);
                }

            });
        </script>
    <?php } else {
        } ?>-->

    <!--**************Experience details************** || strpos(@$vaccination, $word) !== false)
    <?php
    $word = "Not vaccinated";
    if (@$googlechat == null && $filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" class="form-control" name="flag" type="text" value="Y" readonly>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>),
                    Please confirm if the below details are up to date.</p>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Educational Qualification:</p>
                <select name="edu" class="form-control cmb" style="width:max-content;margin-left: 5%; display:inline" placeholder="" required>
                    <option selected><?php echo $eduq ?></option>
                    <option>Bachelor Degree Regular</option>
                    <option>Bachelor Degree Correspondence</option>
                    <option>Master Degree</option>
                    <option>PhD (Doctorate Degree)</option>
                    <option>Post Doctorate or 5 years experience</option>
                    <option>Culture, Art & Sports etc.</option>
                    <option>Class 12th Pass</option>
                    <option hidden>I have taken both doses of the vaccine</option>
                </select>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Major subject or area of ​​specialization:</p>
                <textarea name="sub" id="sub" class="form-control cmb" style="width:max-content; margin-left: 5%; display:inline" rows="2" cols="35" required></textarea>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Work experience:</p>
                <textarea name="work" id="work" class="form-control cmb" style="width:max-content; margin-left: 5%; display:inline" rows="4" cols="35" required><?php echo $workexperience ?></textarea>
                <br>
                <button type="submit" id="sendButton" class="close-button btn btn-success">Save
                </button><br>
               //************/<marquee style="margin-left: 5%; line-height:4" direction="left" height="100%" width="70%" onmouseover="this.stop();" onmouseout="this.start();">To enable the Save button, please update the major subject or area of ​​specialization.</marquee>
                <br><p align="right" style="color:red; margin-right: 5%;">*&nbsp; <i>All fields are mandatory<i></p>
                <br>
        </div>
        </div>
        </form>
        </div>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbzFxxBLaI4b_gQFpS7IPLZLSgmaQjQWSa7o-qGDRF8y_xIpLrde/exec'
            const form = document.forms['submit-to-google-sheet']

            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })
        </script>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
        <script>
            $(document).ready(function() {

                if (Boolean(readCookie('profile'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("profile", "2 days", 2);
                    //return false;
                });

                function createCookie(name, value, days) {
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        var expires = "; expires=" + date.toGMTString();
                    } else var expires = "";
                    document.cookie = name + "=" + value + expires + "; path=/";
                }



                function readCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    createCookie(name, "", -1);
                }

            });
        </script>
        //************/ disable submit button if any required field is blank 
        <script>
            $(document).ready(function() {
                $('#sendButton').attr('disabled', true);

                $('#sub').keyup(function() {
                    if ($(this).val().length != 0) {
                        $('#sendButton').attr('disabled', false);
                    } else {
                        $('#sendButton').attr('disabled', true);
                    }
                })
            });
        </script>
    <?php
    } else {
    ?>
    <?php } ?>-->