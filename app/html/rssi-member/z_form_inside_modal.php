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