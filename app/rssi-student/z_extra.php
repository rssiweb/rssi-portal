<!-- ***** Confirmation ******-->
<?php
    if (($module == "National") && $filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $studentname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $student_id ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo strtok($studentname, ' ') ?>, Did you know that participating in the Annual Sports requires you to submit the mandatory health declaration form? If you haven't submitted it yet, let's do it now.
                    <br><br>
                    <span class="noticet"><a href="https://docs.google.com/forms/d/e/1FAIpQLSdvvoVln-vUAyZeAGkusvoAl4S4TSE4lldzVZTMdqFcZiw3Fg/viewform?usp=pp_url&entry.1609864868=<?php echo $student_id ?>/<?php echo $studentname ?>" target="_blank">Health declaration form</a></span>
                </p>
                <br>
                <button type="submit" id="yes" class="close-button2 btn btn-success cw3" style="white-space:normal !important;word-wrap:break-word;">I have submitted Health declaration form</button>

                <button type="submit" id="no" class="close-button2 btn btn-danger cw3" style="white-space:normal !important;word-wrap:break-word;">I can't participate in the program</button>
                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('I have submitted');
            });

            $('#no').click(function() {
                $('#count2').val('I cant participate in the program');
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

                if (Boolean(readCookie('Khel Utsav'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("Khel Utsav", "15 days", 15);
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
    } else if ($module == 'State' && $filterstatus == 'Active') {
    ?>
    <?php } else {
    } ?>







    <!--**************QUESTION PAPER SUBMISSION CONFIRMATION**************
<?php
if (($module == "National") && $filterstatus == 'Active') {
?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $studentname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $student_id ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo strtok($studentname, ' ') ?>, Your QT2/2021 report card has been published. For more details, please visit
                    <span class="noticet"><a href="result.php" target="_blank">Results</a> portal.</span>
                </p>
               <br>
                <button type="submit" id="yes" class="close-button2 btn btn-success" style="width: 20%; white-space:normal !important;word-wrap:break-word;">I have checked my report card</button>
                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('I have checked my report card');
            });

            //$('#no').click(function() {
              //  $('#count2').val('I have checked my IPF and I reject it.');
            //});
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

                if (Boolean(readCookie('Result'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("Result", "30 days", 30);
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
} else if ($module == 'State' && $filterstatus == 'Active') {
    ?>
    <?php } else {
} ?>-->

