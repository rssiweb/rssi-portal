<!DOCTYPE html>
<html>

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <title>Stock Management Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
    <style>
        .container {
            display: flex;
            justify-content: space-between;
        }

        .left-section {
            flex-basis: 50%;
            /* Adjust the width as needed */
        }

        .right-section {
            flex-basis: 50%;
            margin-left: 50px;
            /* Adjust the width as needed */
        }
    </style>
    <script>
        $(document).ready(function() {
            // Fetch item codes via AJAX and populate the dropdown
            $.ajax({
                url: "get_item_codes.php", // Replace with your server-side script to retrieve item codes
                type: "GET",
                dataType: "json",
                success: function(data) {
                    // Populate the dropdown with the retrieved item codes
                    var dropdown = $("#item-code");
                    $.each(data, function(index, itemCode) {
                        dropdown.append($("<option></option>").val(itemCode).text(itemCode));
                    });
                }
            });

            // When the "Lookup Item" button is clicked
            $("#lookupButton").click(function() {
                // Get the selected item code
                var itemCode = $("#item-code").val();

                // Make an AJAX request to retrieve the item details from the server
                $.ajax({
                    url: "get_item_details.php", // Replace with the URL to your server-side script
                    type: "POST",
                    data: {
                        itemCode: itemCode
                    },
                    dataType: "json",
                    success: function(data) {
                        // Handle the retrieved item details as desired
                        console.log(data);
                    }
                });
            });
        });
    </script>
</head>

<body>
    <div class="container mt-5">
        <div class="left-section">
            <h2>Stock In</h2>
            <form>
                <div class="mb-3">
                    <label for="item-code" class="form-label">Item Code</label>
                    <select class="form-select" id="item-code" required></select>
                    <button type="button" class="btn btn-primary mt-2" id="lookupButton">Lookup Item</button>
                </div>
            </form>
            <form>
                <div id="item-details">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item-name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="item-name" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="current-stock" class="form-label">Current Stock</label>
                            <input type="number" class="form-control" id="current-stock" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock-update" class="form-label">Stock Update</label>
                            <input type="number" class="form-control" id="stock-update" required>
                            <small id="stock-update-help" class="form-text text-muted">Enter the quantity of items used since the last stock update.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity-type" class="form-label">Quantity Type</label>
                            <select class="form-select" id="quantity-type">
                                <option value="pieces">Pieces</option>
                                <option value="packets">Packets</option>
                                <option value="boxes">Boxes</option>
                                <!-- Add more options if needed -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>

            <div class="mt-5">
                <h3>Stock Status</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>001</td>
                            <td>Chalks</td>
                            <td>50</td>
                        </tr>
                        <tr>
                            <td>002</td>
                            <td>Colors</td>
                            <td>100</td>
                        </tr>
                        <tr>
                            <td>003</td>
                            <td>Pads</td>
                            <td>30</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="right-section">
            <h3>Stock Out</h3>
            <form>
                <div id="item-details">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="item-name" class="form-label">Item Code</label>
                            <input type="text" class="form-control" id="item-code">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="current-stock" class="form-label">Current Stock</label>
                            <input type="number" class="form-control" id="current-stock" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock-update" class="form-label">Stock Update</label>
                            <input type="number" class="form-control" id="stock-update" required>
                            <small id="stock-update-help" class="form-text text-muted">Enter the quantity of items used since the last stock update.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity-type" class="form-label">Quantity Type</label>
                            <select class="form-select" id="quantity-type">
                                <option value="pieces">Pieces</option>
                                <option value="packets">Packets</option>
                                <option value="boxes">Boxes</option>
                                <!-- Add more options if needed -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>