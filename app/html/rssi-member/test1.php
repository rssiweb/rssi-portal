<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Book Modal with Dynamic Category</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    #new_category,
    #add_category_btn,
    #category_feedback {
      display: none;
    }
  </style>
</head>
<body>

<!-- Trigger Button -->
<div class="container mt-5">
  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
    Add Book
  </button>
</div>

<!-- Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addBookForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addBookModalLabel">Add Book</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <!-- Book Name -->
          <div class="mb-3">
            <label for="book_name" class="form-label">Book Name</label>
            <input type="text" class="form-control" id="book_name" name="book_name" required>
          </div>

          <!-- Category Selection -->
          <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select class="form-select" id="category" name="category" required>
              <option value="">Select Category</option>
              <option value="Science">Science</option>
              <option value="Math">Math</option>
              <option value="__new__">+ Add New Category</option>
            </select>
          </div>

          <!-- New Category Input -->
          <div class="mb-3">
            <input type="text" id="new_category" class="form-control" placeholder="Enter new category">
            <div id="category_feedback" class="invalid-feedback"></div>
          </div>

          <!-- Add Category Button -->
          <div class="mb-3">
            <button type="button" id="add_category_btn" class="btn btn-outline-secondary">Add Category</button>
          </div>

        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
$(document).ready(function () {
  $('#category').on('change', function () {
    if ($(this).val() === '__new__') {
      $(this).hide();
      $('#new_category').val('').show().focus();
      $('#add_category_btn').show();
    }
  });

  $('#add_category_btn').on('click', function () {
    var newCat = $('#new_category').val().trim();
    if (!newCat) {
      $('#new_category').addClass('is-invalid');
      $('#category_feedback').text('Please enter a category name').show();
      return;
    }

    // Add new category
    $('#category')
      .append($('<option>', {
        value: newCat,
        text: newCat
      }))
      .val(newCat)
      .show();

    // Reset new category input
    $('#new_category').val('').hide().removeClass('is-invalid');
    $('#add_category_btn').hide();
    $('#category_feedback').hide();
  });

  $('#addBookForm').on('submit', function (e) {
    if ($('#category').val() === '__new__') {
      var inputVal = $('#new_category').val().trim();
      if (!inputVal) {
        e.preventDefault();
        $('#new_category').addClass('is-invalid').show().focus();
        $('#category_feedback').text('Please enter a new category name').show();
        $('#add_category_btn').show();
        $('#category').hide();
      }
    }
  });
});
</script>

</body>
</html>
