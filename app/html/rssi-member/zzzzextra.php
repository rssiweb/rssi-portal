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

    <!--**************ADDRESS CONFIRMATION**************
    <?php
    if (@$googlechat == null && $filterstatus == 'Active') {
        ?>
    
            <div id="thoverX" class="thover pop-up"></div>
            <div id="tpopupX" class="tpopup pop-up">
                <form name="submit-to-google-sheet" action="" method="POST">
                    <br>
                    <input type="hidden" class="form-control" name="membername1" type="text" value="<?php echo $fullname ?>" readonly>
                    <input type="hidden" class="form-control" name="memberid1" type="text" value="<?php echo $associatenumber ?>" readonly>
                    <input type="hidden" type="text" name="status1" id="count1" value="" readonly required>
                    <input type="hidden" class="form-control" name="flag" type="text" value="Y" readonly>
                    <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Please confirm whether the current address (You are currently residing here and you can receive any parcel sent from RSSI here) given below is correct.</p>
                    <b><?php echo $currentaddress ?></b><br><br>
                    <button type="submit" id="yes" class="close-button btn btn-success" style="white-space:normal !important;word-wrap:break-word;">
                        <i class="fas fa-smile" style="font-size:17px" aria-hidden="true"></i>&nbsp;Yes, Correct</button><br><br>
                    <button onclick='window.location.href="form.php"' type="submit" id="no" class="close-button btn btn-default" style="white-space:normal !important;word-wrap:break-word;">
                        <i class="far fa-meh" style="font-size:17px" aria-hidden="true"></i>&nbsp;No, I want to change my address.
                    </button>
                    <br><br>
                </form>
            </div>
            <script>
                $('#yes').click(function() {
                    $('#count1').val('Yes, Correct');
                });
    
                $('#no').click(function() {
                    $('#count1').val('No, I want to change my address.');
                });
            </script>
            <script>
                const scriptURL = 'https://script.google.com/macros/s/AKfycbzExVHj1fLiSiERCCF5IVI73-Q7qJDaBDGNzdHJvOUuvyUX5Ig/exec'
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
    
                    if (Boolean(readCookie('address'))) {
                        $('.pop-up').hide();
                        $('.pop-up').fadeOut(1000);
                    }
                    $('.close-button').click(function(e) {
    
                        $('.pop-up').delay(10).fadeOut(700);
                        e.stopPropagation();
    
                        createCookie("address", "30 days", 30);
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

        <!--**************QUESTION PAPER SUBMISSION CONFIRMATION**************
    <?php
        if ((@$googlechat == null) && $filterstatus == 'Active') {
            ?>
    
            <div id="thoverX" class="thover pop-up2"></div>
            <div id="tpopupX" class="tpopup pop-up2">
                <form name="submit-to-google-sheet2" action="" method="POST">
                    <br>
                    <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                    <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                    <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                    <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Do you know when and how to submit QT2/2021 question paper? For more details please visit the <span class="noticet"><a href="exam.php" target="_blank">Examination Portal.</a></span></p><br>
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
                    $('#count2').val('Yes, I know the process. I will share the question paper as per the stipulated time.');
                });
    
                $('#no').click(function() {
                    $('#count2').val('I have not been assigned any question paper for this quarter.');
                });
            </script>
            <script>
                const scriptURL = 'https://script.google.com/macros/s/AKfycbycsvlCllfvKdy257W77NyB05X5hbMpGilznY8n6x5VqL9xsTij/exec'
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
    
                    if (Boolean(readCookie('qt2q'))) {
                        $('.pop-up2').hide();
                        $('.pop-up2').fadeOut(1000);
                    }
                    $('.close-button2').click(function(e) {
    
                        $('.pop-up2').delay(10).fadeOut(700);
                        e.stopPropagation();
    
                        createCookie("qt2q", "4 days", 4);
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

            <!--**************User confirmation2**************-->
    <!--<?php
    if ($filterstatus == 'Active') {
        ?>
    
            <div id="thoverX" class="thover pop-up3"></div>
            <div id="tpopupX" class="tpopup pop-up3">
                <form name="submit-to-google-sheet3" action="" method="POST">
                    <br>
                    <input type="hidden" class="form-control" name="membername3" type="text" value="<?php echo $studentname ?>" readonly>
                    <input type="hidden" class="form-control" name="memberid3" type="text" value="<?php echo $student_id ?>" readonly>
                    <input type="hidden" type="text" name="status3" id="count3" value="" readonly required>
                    <div style="padding-left:5%;padding-right:5%"><p>Hi&nbsp;<?php echo $studentname ?>&nbsp;(<?php echo $student_id ?>), Did you know that from August 1st all official communication will be in Google Chat? If you haven't joined the Google Chatroom yet, please join the <span class="noticet"><a href="https://mail.google.com/chat/u/0/#chat/space/AAAAgNqt55Q" target="_blank">RSSI Student</a></span> group now.</p></div>
    
                    <button onclick='window.location.href="https://mail.google.com/chat/u/0/#chat/space/AAAAgNqt55Q"' type="submit" id="join" class="close-button3 btn btn-success" style="white-space:normal !important;word-wrap:break-word;">
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
    
            <!--**************FEEDBACK**************-->
            <?php
        if ($filterstatus == 'Active') {
        ?>
    
            <div id="thoverX" class="thover pop-up2"></div>
            <div id="tpopupX" class="tpopup pop-up2" style="overflow-y: scroll; -webkit-overflow-scrolling: touch; height:570px; overflow-x: hidden;">
                <form name="submit-to-google-sheet2" action="" method="POST">
                    <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $studentname ?>" readonly>
                    <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $student_id ?>" readonly>
                    <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
    
                    <script src="https://apps.elfsight.com/p/platform.js" defer></script>
                    <div class="elfsight-app-a29c0d34-63fe-4fd3-80d5-7205df6250b0"></div>
    
                    <button type="submit" id="no" class="close-button2 btn btn-danger" style="width: 20%; white-space:normal !important;word-wrap:break-word;">Close</button><br>
                </form>
            </div>
            <script>
                $('#no').click(function() {
                    $('#count2').val('checked feedback');
                });
            </script>
            <script>
                const scriptURL = 'https://script.google.com/macros/s/AKfycby_0R2p9cBKr5ZQlpSJWKlyNVEdK25EWXaOevzT4lhVk7uqysM/exec'
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
    
                    if (Boolean(readCookie('feedback'))) {
                        $('.pop-up2').hide();
                        $('.pop-up2').fadeOut(1000);
                    }
                    $('.close-button2').click(function(e) {
    
                        $('.pop-up2').delay(10).fadeOut(700);
                        e.stopPropagation();
    
                        createCookie("feedback", "30 days", 30);
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
        } else if ($filterstatus == 'Inactive') {
        ?>
        <?php } else {
        } ?>
        <!--**************User confirmation2-SUBJECT CHANGE**************-->
    <?php
    if ($filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername1" type="text" value="<?php echo $studentname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid1" type="text" value="<?php echo $student_id ?>" readonly>
                <input type="hidden" type="text" name="status1" id="count1" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo $studentname ?>&nbsp;(<?php echo $student_id ?>), Please confirm whether the RSSI subject combination given below is correct or not. You have to write the below subject test in QT2-2021.</p>
                <b><?php echo $nameofthesubjects ?></b><br><br>
                <button type="submit" id="yes" class="close-button btn btn-success" style="white-space:normal !important;word-wrap:break-word;">
                    <i class="fas fa-smile" style="font-size:17px" aria-hidden="true"></i>&nbsp;Yes, Correct</button><br><br>
                <button onclick='window.location.href="form.php"' type="submit" id="no" class="close-button btn btn-default" style="white-space:normal !important;word-wrap:break-word;">
                    <i class="far fa-meh" style="font-size:17px" aria-hidden="true"></i>&nbsp;No, I want to change my subject combination.
                </button>
                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count1').val('Yes, Correct');
            });

            $('#no').click(function() {
                $('#count1').val('No, I want to change my subject combination.');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbyiOP3O__HFeipBtF5EFnv1fID-VTMbnM8yt64P7qBtHmHgvi1R/exec'
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

                if (Boolean(readCookie('subqt2'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("subqt2", "30 days", 30);
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
    <?php } ?>

    <!--**************QUESTION PAPER SUBMISSION CONFIRMATION**************-->
    <?php
    if ((@$googlechat == null) && $filterstatus == 'Active' && @$remarks != 'Reg') {
    ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo strtok($fullname, ' ') ?>, Your offer letter has been revised and the offer has been extended for the academic year 2022-2023. You can access your offer letter anytime from the My Document section.
                    <!--<span class="noticet"><a href="my_appraisal.php" target="_blank">My Appraisal</a> portal.</span>-->
                </p>
                <embed class="hidden-xs" src="https://drive.google.com/file/d/<?php echo $questionflag ?>/preview" width="700px" height="400px" /></embed>
                <span class="noticet hidden-md hidden-sm hidden-lg"><a href="<?php echo $profile ?>" target="_blank"><?php echo $filename ?></a></span>
                <br><br>

                <button type="submit" id="yes" class="btn btn-success btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;" onclick="location.href='https://docs.google.com/forms/d/e/1FAIpQLSdBTkdA-jqrPqZBLebQV3vlBiVk2iklcbZkn1Z1pbZIQISb3g/formResponse?usp=pp_url&entry.1000022=<?php echo $associatenumber ?>&entry.1000020=<?php echo $fullname ?>&entry.1000025=<?php echo $email ?>&entry.1701149099=Accepted';">I accept the offer</button>
                <button type="submit" id="no" class="btn btn-danger btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;" onclick="location.href='https://docs.google.com/forms/d/e/1FAIpQLSdBTkdA-jqrPqZBLebQV3vlBiVk2iklcbZkn1Z1pbZIQISb3g/formResponse?usp=pp_url&entry.1000022=<?php echo $associatenumber ?>&entry.1000020=<?php echo $fullname ?>&entry.1000025=<?php echo $email ?>&entry.1701149099=Rejected';">I reject the offer</button>

                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('I accept the offer');
            });

            $('#no').click(function() {
                $('#count2').val('I reject the offer');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbycsvlCllfvKdy257W77NyB05X5hbMpGilznY8n6x5VqL9xsTij/exec'
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

                if (Boolean(readCookie('Offer'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("Offer", "15 days", 15);
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
    } else if (@$googlechat != null && $filterstatus == 'Active') {
    ?>
    <?php } else {
    } ?>
<!--**************QUESTION PAPER SUBMISSION TIMER**************
<?php
                if ((@$questionflag == 'Y') && $filterstatus == 'Active') {
                ?>
                    <div class="alert alert-success" role="alert" style="text-align: -webkit-center;">Being on time is a wonderful thing. You have successfully submitted the QT3/2022 question paper.
                    </div>
                <?php
                } else if ((@$questionflag == 'NA' || @$questionflag == 'YL') && $filterstatus == 'Active') {
                ?>
                <?php
                } else if ((@$questionflag == null || @$questionflag != 'Y') && $filterstatus == 'Active') {
                ?>
                    <div class="alert alert-danger" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                        <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; left for question paper submission.
                        //left for the completion of answer sheet evaluation.
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
                <!--<div class="alert alert-info alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        Invigilation duty list has been published. Please check&nbsp;<span class="noticet">
                            <a href="https://drive.google.com/file/d/1wrTxXQLzPPuJr0T8BnyfkNjkM00JpzLY/view" target="_blank">here..</a></span>
                        &nbsp;&nbsp;<span class="label label-danger blink_me">new</span>
                    </div>-->


                    <!--**************Experience details************** || strpos(@$vaccination, $word) !== false)
     <?php
    if (@$mjorsub == null && $filterstatus == 'Active' && $vaccination == null) {
    ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" class="form-control" name="flag" type="text" value="Y" readonly>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Hi&nbsp;<?php echo strtok($fullname, ' ') ?>&nbsp;(<?php echo $associatenumber ?>),
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
               <marquee style="margin-left: 5%; line-height:4" direction="left" height="100%" width="70%" onmouseover="this.stop();" onmouseout="this.start();">To enable the Save button, please update the major subject or area of ​​specialization.</marquee>
                <br><p align="right" style="color:red; margin-right: 5%;">*&nbsp; <i>All fields are mandatory<i></p>
                <br>
        </div>
        </div>
        </form>
        </div>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbyl_OmmyKhdyfAYW4O-pLQZs6ZmFAfkJ_yP3wYe4-Ry9UkiFiQ/exec'
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

                if (Boolean(readCookie('majorsub'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("majorsub", "14 days", 14);
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
        </script>-->
        <!--disable submit button if any required field is blank
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

    <!--**************QUESTION PAPER SUBMISSION CONFIRMATION**************
    <?php
        if ((@$googlechat == null) && $filterstatus == 'Active') {
            ?>
    
            <div id="thoverX" class="thover pop-up2"></div>
            <div id="tpopupX" class="tpopup pop-up2">
                <form name="submit-to-google-sheet2" action="" method="POST">
                    <br>
                    <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                    <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                    <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                    <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Do you know when and how to submit QT3/2022 question paper? For more details please visit the <span class="noticet"><a href="exam.php" target="_blank">Examination Portal.</a></span></p><br>
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
                    $('#count2').val('Yes, I know the process. I will share the question paper as per the stipulated time.');
                });
    
                $('#no').click(function() {
                    $('#count2').val('I have not been assigned any question paper for this quarter.');
                });
            </script>
            <script>
                const scriptURL = 'https://script.google.com/macros/s/AKfycbycsvlCllfvKdy257W77NyB05X5hbMpGilznY8n6x5VqL9xsTij/exec'
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
    
                    if (Boolean(readCookie('qt3q'))) {
                        $('.pop-up2').hide();
                        $('.pop-up2').fadeOut(1000);
                    }
                    $('.close-button2').click(function(e) {
    
                        $('.pop-up2').delay(10).fadeOut(700);
                        e.stopPropagation();
    
                        createCookie("qt3q", "4 days", 4);
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

            <!--**************User confirmation2**************-->

              <!--onclick="location.href='https://docs.google.com/forms/d/e/1FAIpQLSdBTkdA-jqrPqZBLebQV3vlBiVk2iklcbZkn1Z1pbZIQISb3g/formResponse?usp=pp_url&entry.1000022=<?php echo $associatenumber ?>&entry.1000020=<?php echo $fullname ?>&entry.1000025=<?php echo $email ?>&entry.1701149099=Rejected';"-->

              <!--<embed class="hidden-xs" src="https://drive.google.com/file/d/<?php echo $questionflag ?>/preview" width="700px" height="400px" /></embed>
                <span class="noticet hidden-md hidden-sm hidden-lg"><a href="<?php echo $profile ?>" target="_blank"><?php echo $filename ?></a></span>-->
                 
                
                <!--Your offer letter has been revised and the offer has been extended for the academic year 2022-2023. You can access your offer letter anytime from the My Document section.-->

                <!-- Toasts Notification -->
    <?php if (@$feedback == null) { ?>

<form name="submit-to-google-sheet-noti" action="" method="POST">
<input type="hidden" class="form-control" name="memberidnoti" type="text" value="<?php echo $associatenumber ?>" readonly>
<input type="hidden" type="text" name="statusnoti" id="countnoti" value="" readonly required>
<div aria-live="polite" aria-atomic="true" style="position: relative; min-height: 200px;">

    <div class="toastmobile" style="position: fixed; top: 10%; right: 3%;z-index: 100;">

        <div role="alert" aria-live="assertive" aria-atomic="true" class="toast" data-autohide="false">
            <div class="toast-header">

                <svg max-width="20" height="20" class="mr-2" viewBox="0 0 30 24">
                    <path d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z" fill="#ccc"></path>
                </svg>

                <strong class="mr-auto" style="font-size: 12px;">Notification</strong>
                <span class="label label-danger blink_me" style="font-size: 10px;">new</span>
                <button id="yesnoti" type="submit" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body" style="font-size: 12px;">
                Now you can check the tagged asset or agreement details from your profile > My Document > My Asset
            </div>
        </div>
    </div>
</div>
</form>
<script>
        $('#yesnoti').click(function() {
            $('#countnoti').val('Seen');
        });
    </script>
    <script>
        const scriptURL = 'https://script.google.com/macros/s/AKfycbzJP9-BI4bzwKNzXoyqymPgh2m0dFTreg-bHFELM_lyKW2XCoir/exec'
        const form = document.forms['submit-to-google-sheet-noti']

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
    // $('.toast').toast({
    //   autohide: false
    // })
    $('.toast').toast("show")
</script> <?php }?>

<!-- Toasts Notification End -->

@media only screen and (max-device-width: 480px) {
            .toastmobile {
                width: 60%;
            }
        }


        <!--**************NOTICE Display**************
    <?php
    if ((@$questionflag == null) && $filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <embed class="hidden-xs" src="https://drive.google.com/file/d/1DlBalR4kvQ6g3V5QYTDyWeoclTRbagZi/preview" width="700px" height="400px" /></embed>
                <span class="noticet hidden-md hidden-sm hidden-lg"><a href="https://drive.google.com/file/d/1DlBalR4kvQ6g3V5QYTDyWeoclTRbagZi/preview" target="_blank">Notice No. RS/2022-04/02</a></span>
                <br><br>

                <button type="submit" id="yes" class="btn btn-success btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;">Agree</button>
                <button type="submit" id="no" class="btn btn-danger btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;">Disagree</button>

                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('Agree');
            });

            $('#no').click(function() {
                $('#count2').val('Disagree');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbycsvlCllfvKdy257W77NyB05X5hbMpGilznY8n6x5VqL9xsTij/exec'
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

                if (Boolean(readCookie('notice02'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("notice02", "1 days", 1);
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
    } else if (@$questionflag != null && $filterstatus == 'Active') {
    ?>
    <?php } else {
    } ?>-->



    <!--**************Experience details************** || strpos(@$vaccination, $word) !== false)
    <?php
    if ($filterstatus == 'Active' && $vaccination == null) {
    ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" class="form-control" name="flag" type="text" value="Y" readonly>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Hi&nbsp;<?php echo strtok($fullname, ' ') ?>&nbsp;(<?php echo $associatenumber ?>),
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
                <textarea name="sub" id="sub" class="form-control cmb" style="width:max-content; margin-left: 5%; display:inline" rows="2" cols="35" required><?php echo $mjorsub ?></textarea>
                <p align="left" style="margin-left: 5%; margin-right: 5%;">Work experience:</p>
                <textarea name="work" id="work" class="form-control cmb" style="width:max-content; margin-left: 5%; display:inline" rows="4" cols="35" required><?php echo $workexperience ?></textarea>
                <br>
                <?php if ($workexperience == null) { ?>

                    <button type="submit" id="sendButton" class="close-button btn btn-success">Save
                    </button><?php } else { ?>
                    <button type="submit" class="close-button btn btn-success">Save
                    </button>
                <?php } ?>
                &nbsp;<button type="submit" class="close-button btn btn-info">No Change
                </button><br>
                <marquee style="margin-left: 5%; line-height:4" direction="left" height="100%" width="70%" onmouseover="this.stop();" onmouseout="this.start();">For multiple entries in Major subject or area of ​​specialization or work experience, please use comma (,) as a delimiter.</marquee>
                <br>
                <p align="right" style="color:red; margin-right: 5%;">*&nbsp; <i>All fields are mandatory<i></p>
                <br>
        </div>
        </div>
        </form>
        </div>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbyl_OmmyKhdyfAYW4O-pLQZs6ZmFAfkJ_yP3wYe4-Ry9UkiFiQ/exec'
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

                if (Boolean(readCookie('majorsub'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("majorsub", "1 day", 1);
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
        </script>-->
        <!--disable submit button if any required field is blank
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
        </script>-->
    <?php
    } else {
    ?>
    <?php } ?>