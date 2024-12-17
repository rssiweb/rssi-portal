<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");
?>

<?php
$application_number = isset($_GET['application_number']) ? $_GET['application_number'] : null;

// If no application number is provided, show the input form
if (!$application_number): ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Enter Application Number</title>
        <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body>
        <div class="container mt-5">
            <h4 class="mb-3">Enter Application Number</h4>
            <form method="GET" action="">
                <div class="input-group mb-3">
                    <input type="text" name="application_number" class="form-control" placeholder="Enter Application Number" required>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            </form>
        </div>
    </body>

    </html>
    <?php exit; ?>
<?php endif; ?>

<?php
// If application_number is provided, proceed to fetch the data
if ($application_number > 0) {
    $result = pg_query($con, "
            SELECT DISTINCT ON (archive.file_name) archive.remarks as aremarks, archive.*, signup.*
            FROM archive
            JOIN signup ON archive.uploaded_for = signup.application_number
            WHERE archive.uploaded_for = '$application_number'
            ORDER BY archive.file_name, archive.doc_id DESC");
} else {
    echo "Invalid User ID.";
    exit;
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Documents Pending Approval</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <style>
        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 300px;
            border-right: 1px solid #ddd;
            padding: 20px;
            overflow-y: auto;
        }

        .document-list {
            list-style-type: none;
            padding: 0;
        }

        .document-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            cursor: pointer;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .document-item:hover {
            background-color: #e9ecef;
        }

        .preview-panel {
            flex-grow: 1;
            padding: 20px;
        }

        .approval-buttons {
            display: flex;
            gap: 15px;
        }

        .remarks-box {
            width: 100%;
            height: 100px;
            margin-top: 20px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: 10px;
        }

        .status-indicator.yellow {
            background-color: #FFBF00;
        }

        .status-indicator.green {
            background-color: green;
        }

        .status-indicator.red {
            background-color: red;
        }


        /* Style for active document item */
        .document-item.active {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <div class="container">
        <!-- Sidebar with Document List -->
        <div class="sidebar">
            <h4>Documents Pending Approval</h4>
            <input class="form-control mb-3" type="text" id="search" placeholder="Search Document">
            <ul class="document-list" id="documentList">
                <?php
                if ($resultArr) {
                    foreach ($resultArr as $doc) {
                        echo '<li class="document-item" data-doc-id="' . $doc['doc_id'] . '" data-file-name="' . htmlspecialchars($doc['file_name']) . '">
                        ' . htmlspecialchars($doc['file_name']) . ' 
                        <span class="status-indicator yellow"></span>
                      </li>';
                    }
                } else {
                    echo '<li>No documents found.</li>';
                }
                ?>
            </ul>
        </div>

        <!-- Document Preview & Approval -->
        <div class="preview-panel">
            <h4>Document Preview</h4>
            <div id="documentPreview">
                <!-- Document content will be displayed here -->
                <p>Select a document to preview.</p>
            </div>

            <!-- Document Approval Panel -->
            <!-- Document Approval Panel -->
            <div id="approvalPanel" class="approval-panel mt-3" style="display: none;">
                <!-- Form content is dynamically inserted here -->
            </div>

        </div>

    </div>
    <!-- Bootstrap JS -->

    <!-- jQuery Library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 JavaScript Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

    <script>
        // Fetch document data from PHP as a JavaScript array
        const documents = <?php echo json_encode($resultArr); ?>;

        // Render document list dynamically using JavaScript
        const documentList = document.getElementById('documentList');
        documentList.innerHTML = ''; // Clear any existing list before rendering

        // Store the currently active document in a variable
        let activeDoc = null;

        // If documents are fetched successfully, render them in the list
        if (documents.length > 0) {
            documents.forEach(doc => {
                const listItem = document.createElement('li');
                listItem.classList.add('document-item');
                listItem.dataset.docId = doc.doc_id;
                listItem.dataset.fileName = doc.file_name; // Add the file name here

                // Set the status indicator class based on verification_status
                let statusClass = 'yellow'; // Default to yellow if no status
                if (doc.verification_status) {
                    // Handle different verification statuses, ensuring case-insensitivity
                    if (doc.verification_status.toLowerCase() === 'verified') {
                        statusClass = 'green';
                    } else if (doc.verification_status.toLowerCase() === 'rejected') {
                        statusClass = 'red';
                    }
                }

                listItem.innerHTML = `${doc.file_name} <span class="status-indicator ${statusClass}"></span>`;

                // Add the click event listener for each item
                listItem.addEventListener('click', function() {
                    const fileName = this.getAttribute('data-file-name');
                    const currentUrl = new URL(window.location.href);

                    // Set file_name in the URL
                    currentUrl.searchParams.set('file_name', fileName);

                    // Update the browser's URL without reloading the page
                    window.history.pushState({}, '', currentUrl);

                    // Highlight the selected document tab in the sidebar
                    document.querySelectorAll('.document-item').forEach(tab => {
                        tab.classList.remove('active'); // Remove active class from all tabs
                    });
                    this.classList.add('active'); // Add active class to the clicked tab

                    // Store the active document
                    activeDoc = doc;

                    // Pass the whole document object to loadDocument instead of just the file name
                    loadDocument(doc); // Function to preview the document, if needed
                });

                documentList.appendChild(listItem);
            });
        } else {
            // Show a message if no documents are available
            const noDocsMessage = document.createElement('li');
            noDocsMessage.textContent = 'No documents found.';
            documentList.appendChild(noDocsMessage);
        }

        // Automatically select and highlight the document if file_name is in the URL
        const urlParams = new URLSearchParams(window.location.search);
        const selectedFileName = urlParams.get('file_name');

        // Find the corresponding document from the documents array
        if (selectedFileName) {
            const selectedDoc = documents.find(doc => doc.file_name === selectedFileName);
            if (selectedDoc) {
                // Find the corresponding list item and mark it as active
                const selectedListItem = document.querySelector(`[data-file-name="${selectedFileName}"]`);
                if (selectedListItem) {
                    selectedListItem.classList.add('active'); // Highlight the selected tab
                }

                // Load the document preview
                loadDocument(selectedDoc);

                // Store the active document
                activeDoc = selectedDoc;
            }
        }

        // Search functionality for document list
        const searchInput = document.getElementById('search');
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            const filteredDocs = documents.filter(doc => doc.file_name.toLowerCase().includes(query));
            documentList.innerHTML = '';

            filteredDocs.forEach(doc => {
                const listItem = document.createElement('li');
                listItem.classList.add('document-item');
                listItem.dataset.fileName = doc.file_name; // Add the file name here
                listItem.dataset.status = doc.verification_status; // Add the status here

                // Set the status indicator class based on verification_status
                let statusClass = 'yellow'; // Default to yellow if no status
                if (doc.verification_status) {
                    // Handle different verification statuses, ensuring case-insensitivity
                    if (doc.verification_status.toLowerCase() === 'verified') {
                        statusClass = 'green';
                    } else if (doc.verification_status.toLowerCase() === 'rejected') {
                        statusClass = 'red';
                    }
                }

                listItem.innerHTML = `${doc.file_name} <span class="status-indicator ${statusClass}"></span>`;

                // Add click listener for each filtered document item
                listItem.addEventListener('click', function() {
                    const fileName = this.getAttribute('data-file-name');
                    const currentUrl = new URL(window.location.href);

                    // Set file_name in the URL
                    currentUrl.searchParams.set('file_name', fileName);

                    // Update the browser's URL without reloading the page
                    window.history.pushState({}, '', currentUrl);

                    // Highlight the selected document tab in the sidebar
                    document.querySelectorAll('.document-item').forEach(tab => {
                        tab.classList.remove('active'); // Remove active class from all tabs
                    });
                    this.classList.add('active'); // Add active class to the clicked tab

                    // Store the active document
                    activeDoc = doc;

                    // Load the document preview
                    loadDocument(doc); // Function to preview the document, if needed
                });

                documentList.appendChild(listItem);
            });

            // Reapply the active class to the previously active document if it's in the filtered list
            if (activeDoc) {
                const activeItem = document.querySelector(`[data-file-name="${activeDoc.file_name}"]`);
                if (activeItem) {
                    activeItem.classList.add('active'); // Reapply active class to the previously active document
                }
            }
        });

        // Load selected document into the preview area
        function loadDocument(doc) {
            const preview = document.getElementById('documentPreview');
            const approvalPanel = document.getElementById('approvalPanel'); // Get the approval panel element

            // Check if a valid document object is passed
            if (doc) {
                // Generate a unique ID for the form (e.g., based on doc_id)
                const formId = `updateDocumentForm-${doc.doc_id}`;

                // Check if the file is a Google Drive document and format the URL for embedding
                let previewContent = '';
                if (doc.file_path.includes('drive.google.com')) {
                    // Extract the Google Drive file ID and generate embed link
                    const fileId = doc.file_path.split('/')[5];
                    previewContent = `
            <h5>${doc.file_name}</h5>
            <p>Uploaded by: ${doc.uploaded_by} ${doc.uploaded_on ? new Date(doc.uploaded_on).toLocaleString() : ''}</p>
            <iframe src="https://drive.google.com/file/d/${fileId}/preview" width="100%" height="400px"></iframe>
        `;
                } else {
                    // Handle other file types
                    previewContent = `
            <h5>${doc.file_name}</h5>
            <p>Uploaded by: ${doc.uploaded_by} ${doc.uploaded_on ? new Date(doc.uploaded_on).toLocaleString() : ''}</p>
            <p class="text-muted">Preview not available for this file type.</p>
        `;
                }

                // Insert the preview content into the preview area
                preview.innerHTML = previewContent;

                // Show the approval panel
                approvalPanel.style.display = 'block';

                // Set up the form content dynamically
                const formHTML = `
                    <form id="${formId}">
                        <fieldset ${doc.field_status ? doc.field_status : ''}>
                            <!-- Status Dropdown -->
                            <div class="mb-3">
                                <label for="statusDropdown" class="form-label">Document Status</label>
                                <select id="statusDropdown" class="form-select">
                                    <option value="" disabled ${!doc.verification_status ? 'selected' : ''}>Select Status</option>
                                    <option value="Verified" ${doc.verification_status === 'Verified' ? 'selected' : ''}>Verified</option>
                                    <option value="Rejected" ${doc.verification_status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                                </select>
                            </div>

                            <!-- Remarks Textarea -->
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea id="remarks" class="form-control" rows="3" placeholder="Add remarks...">${doc.remarks ? doc.remarks : ''}</textarea>
                            </div>
                    <!-- Row for Reviewed by and Submit Button -->
                            <div class="d-flex justify-content-between">
                                <!-- Reviewed by Section -->
                                <div>
                                    ${doc.reviewed_by ? `<p>Reviewed by: ${doc.reviewed_by} ${doc.reviewed_on ? new Date(doc.reviewed_on).toLocaleString() : ''}</p>` : ''}
                                </div>

                                <!-- Submit Button -->
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" id="updateStatusButton">Submit</button>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                `;
                // Replace the existing form or approval panel content with the new form
                approvalPanel.innerHTML = formHTML;

                // Attach event listener to the dynamically created form
                const form = document.getElementById(formId);
                const updateButton = document.getElementById('updateStatusButton');

                // Reset status and remarks to default
                // document.getElementById('statusDropdown').value = '';
                // document.getElementById('remarks').value = '';

                form.onsubmit = (e) => {
                    e.preventDefault(); // Prevent form submission

                    const status = document.getElementById('statusDropdown').value;
                    const remarks = document.getElementById('remarks').value;

                    if (status && remarks) {
                        // If both status and remarks are filled, proceed with AJAX request
                        const formData = new FormData();
                        formData.append('doc_id', doc.doc_id);
                        formData.append('status', status);
                        formData.append('remarks', remarks);
                        formData.append('reviewed_by', '<?php echo $associatenumber; ?>'); // Output PHP variable to JavaScript
                        formData.append('form-type', 'update-document'); // The form-type value

                        // AJAX request to update the document status
                        fetch('payment-api.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Handle success (e.g., show success message, update UI)
                                    alert(data.message);
                                    location.reload(); // Reload the page after success
                                } else {
                                    // Handle error
                                    alert(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred.');
                            });
                    } else {
                        alert('Please select a status and add remarks.');
                    }
                };
            }
        }
    </script>



</body>

</html>