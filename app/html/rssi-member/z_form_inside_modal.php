<?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>

<p><strong>Fee</strong></p>
<!-- <span style="display: inline !important;" class="badge bg-secondary">PAID&nbsp;-&nbsp;<span class="maxmonth"></span></span> -->

<form name="payment" action="#" method="POST">
  <input type="hidden" name="form-type" type="text" value="payment">
  <input type="hidden" class="form-control" name="studentid" id="studentid" type="text" value="">
  <input type="hidden" class="form-control" name="collectedby" id="collectedby" type="text" value="">

  <select name="year" id="year" class="form-select" style="display: -webkit-inline-box; width:20vh;" required>
    <!-- <option disabled selected hidden>Select Year</option> -->
  </select>

  <select name="ptype" id="ptype" class="form-select" style="display: -webkit-inline-box; width:20vh;" required>
    <option disabled selected hidden>Select Type</option>
    <option value="Fees" selected>Fees</option>
    <option value="Admission Fee">Admission Fee</option>
    <option value="Fine">Fine</option>
    <option value="Uniform">Uniform</option>
    <option value="ID Card">ID Card</option>
  </select>

  <select name="month" id="month" class="form-select" style="display: -webkit-inline-box; width:20vh;" required>
    <option disabled selected hidden>Select Month</option>
    <option value="1">January</option>
    <option value="2">February</option>
    <option value="3">March</option>
    <option value="4">April</option>
    <option value="5">May</option>
    <option value="6">June</option>
    <option value="7">July</option>
    <option value="8">August</option>
    <option value="9">September</option>
    <option value="10">October</option>
    <option value="11">November</option>
    <option value="12">December</option>
  </select>

  <input type="number" name="fees" id="fees" class="form-control" style="display: -webkit-inline-box; width:15vh;" placeholder="Amount" required>
  <button type="submit" id="yes" class="btn btn-danger btn-sm " style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none">Update</button>
</form>
<hr>
<p><strong>Distributed items and supplies</strong></p>
<form name="distribution" action="#" method="POST">
  <input type="hidden" name="form-type" value="distribution">
  <input type="hidden" class="form-control" name="distributedto" id="distributedto" value="">
  <input type="hidden" class="form-control" name="distributedby" id="distributedby" value="">
  <div style="display: flex; flex-direction: row; align-items: center;">
    <select name="items" id="items" class="form-select" style="display: -webkit-inline-box; width:20vh;  margin-right: 10px;" required>
      <option disabled selected hidden>Select Item</option>
      <option value="Uniform">Uniform</option>
      <option value="ID Card">ID Card</option>
      <option value="Notebook">Notebook</option>
      <option value="Pen">Pen</option>
      <option value="Pencil">Pencil</option>
      <option value="Sanitary Pads">Sanitary Pads</option>
    </select>
    <input type="number" name="quantity" id="quantity" class="form-control" style="width: 15vh; margin-right: 10px;" placeholder="Quantity" required>
    <input type="date" name="issuance_date" id="issuance_date" class="form-control" style="width: 15vh; margin-right: 10px;" placeholder="Issuance Date" required>
    <button type="submit" id="submit_distribution" class="btn btn-danger btn-sm" style="outline: none;">Update</button>
  </div>
</form>
<br>
<script>
  var currentYear = new Date().getFullYear();
  for (var i = 0; i < 5; i++) {
    var year = currentYear;
    //next.toString().slice(-2)
    $('#year').append(new Option(year));
    currentYear--;
  }
</script>
<div class="modal-footer">
  <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
</div>
<?php } ?>
<script>
const scriptURL = 'payment-api.php';
const paymentForm = document.forms['payment'];
const distributionForm = document.forms['distribution'];

// Automatically show the modal when the form is submitted
const showModal = () => {
$('#myModal_p').modal({
backdrop: 'static',
keyboard: false
});
$('#myModal_p').modal('show');
};

// Automatically hide the modal when the submission is complete
const hideModal = () => {
$('#myModal_p').modal('hide');
};

paymentForm.addEventListener('submit', e => {
e.preventDefault();

showModal(); // Show the modal when the form is submitted

fetch(scriptURL, {
method: 'POST',
body: new FormData(paymentForm)
})
.then(response => response.text())
.then(result => {
hideModal(); // Hide the modal when the submission is complete

if (result === 'success') {
alert("Fee has been deposited successfully.");
location.reload();
} else {
alert("Failed to deposit fee. Please try again later or contact our support team for assistance.");
}
})
.catch(error => {
hideModal(); // Hide the modal in case of an error
console.error('Error!', error.message);
});
});

distributionForm.addEventListener('submit', e => {
e.preventDefault();

showModal(); // Show the modal when the form is submitted

fetch(scriptURL, {
method: 'POST',
body: new FormData(distributionForm)
})
.then(response => response.text())
.then(result => {
hideModal(); // Hide the modal when the submission is complete

if (result === 'success') {
alert("Record has been updated.");
location.reload();
} else {
alert("Error updating record. Please try again later or contact support.");
}
})
.catch(error => {
hideModal(); // Hide the modal in case of an error
console.error('Error!', error.message);
});
});
</script>



    <!-- <div class="modal fade show" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0"
                                class="active"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="2"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="3"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="4"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="5"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="6"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="7"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img
                                    src="../img/image.jpg"
                                    class="d-block w-100" alt="Slide 1">
                            </div>
                            <div class="carousel-item">
                                <img
                                    src="../img/81-of-Data-Breaches.jpg"
                                    class="d-block w-100" alt="Slide 2">
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/drawing-competition-poshan-nutrition/?target=inapp&type=task&nid=353539"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725862673110258821_1.png"
                                        class="d-block w-100" alt="Slide 3"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/selfie-colorful-thali-depicting-diet-diversity/?target=inapp&type=task&nid=353517"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725768844123183681.png"
                                        class="d-block w-100" alt="Slide 4"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/slogan-writing-competition-nutrition-poshan/?target=inapp&type=task&nid=353506"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725731570123183681.png"
                                        class="d-block w-100" alt="Slide 5"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/poem-writing-competition-poshan/?target=inapp&type=task&nid=353495"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725697827123183681_1.png"
                                        class="d-block w-100" alt="Slide 6"></a>
                            </div>

                            <div class="carousel-item">
                                <a href="https://quiz.mygov.in/quiz/quiz-competition-on-healthy-diet-complementary-feeding/"
                                    target="_blank"><img
                                        src="https://static.mygov.in/media/quiz/2024/09/mygov_66deba543b0af.jpg"
                                        class="d-block w-100" alt="Slide 7"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://quiz.mygov.in/quiz/quiz-competition-on-anemia-first-1000-days/"
                                    target="_blank"><img
                                        src="https://static.mygov.in/media/quiz/2024/09/mygov_66ded9542454c.jpg"
                                        class="d-block w-100" alt="Slide 8"></a>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex flex-grow-1">
                        <p class="mb-0" style="text-align: left;">
                            Click on the image above to learn more about this topic or to take further action.
                        </p>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div> -->
    <!-- <div class="modal fade show" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <iframe class="d-block w-100" src="https://www.youtube.com/embed/VKNs5QOx634?autoplay=1&mute=1&loop=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="height: 500px;"></iframe>
                            </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex flex-grow-1">
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>-->
    <!-- <script>
        var myModal = new bootstrap.Modal(document.getElementById('myModal'));
        myModal.show();
    </script> -->