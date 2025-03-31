<?php
/**
 * Main Controller for DataIngestor
 *
 * This is the primary entry point for the DataIngestor application.
 * It handles data submission, retrieval, and rendering the web interface.
 *
 * @package DataIngestor
 * @author Original Developer
 * @version 1.1
 */

// Set up CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Headers: X-Requested-With');

/**
 * Handle CORS preflight OPTIONS requests
 *
 * Browsers send an OPTIONS request before cross-origin POST requests
 * to check if the request will be allowed.
 */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include the access control module to validate the client's IP
require_once 'check_access.php';

/**
 * Database Setup
 *
 * Connect to the SQLite database that stores all submitted data.
 */
$db = new SQLite3('306d717c3f592af0186ed31e2f056a7d/data.db');

/**
 * Handle data deletion
 *
 * If a deletion request is made via POST with the 'delete' parameter,
 * remove the corresponding record from the database.
 */
if (isset($_POST['delete'])) {
    $deleteId = $_POST['delete'];

    // Use prepared statement to prevent SQL injection
    $stmt = $db->prepare('DELETE FROM entries WHERE id = :id');
    $stmt->bindValue(':id', $deleteId, SQLITE3_INTEGER);
    $stmt->execute();

    // Redirect back to the main page after deletion
    header('Location: index.php');
    exit();
}

/**
 * Handle data submission
 *
 * Process incoming data from POST requests and store it in the database
 * along with metadata like referrer, client IP, and notes.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = $_POST['data'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $clientIP = getClientIP(); // Get the client's IP address

    // Insert the data and metadata into the database using prepared statements
    $stmt = $db->prepare('INSERT INTO entries (data, notes, referrer, client_ip, entry_datetime) VALUES (:data, :notes, :referrer, :client_ip, :entry_datetime)');
    $stmt->bindValue(':data', $data, SQLITE3_TEXT);
    $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
    $stmt->bindValue(':referrer', $referrer, SQLITE3_TEXT);
    $stmt->bindValue(':client_ip', $clientIP, SQLITE3_TEXT);
    $stmt->bindValue(':entry_datetime', date('Y-m-d H:i:s'), SQLITE3_TEXT); // Add current datetime
    $stmt->execute();

    // Redirect to prevent form resubmission on page refresh
    header('Location: index.php');
    exit();
}

/**
 * Retrieve all data entries
 *
 * Fetch all records from the database, ordered by newest first.
 */
$query = $db->query('SELECT * FROM entries ORDER BY id DESC');
$dataArray = [];
while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
    $dataArray[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Ingestor</title>
    <link href="/public/css/bootstrap.min.css" rel="stylesheet">
    <script src="/public/js/jquery.min.js"></script>
    <script src="/public/js/popper.min.js"></script>
    <script src="/public/js/bootstrap.min.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="UTF-8">
    <style>
        /* Custom styles to improve usability */
        .data-container {
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }
        .btn-copy {
            margin-left: 5px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .notes-content {
            white-space: pre-wrap;
            max-height: 100px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1>Data Ingestor</h1>

    <!-- Data submission form -->
    <form method="post" class="mb-4">
        <div class="form-group">
            <label for="data">Data:</label>
            <input type="text" class="form-control" name="data" id="data" placeholder="Enter data to submit">
        </div>
        <div class="form-group">
            <label for="notes">Notes:</label>
            <textarea class="form-control" name="notes" id="notes" placeholder="Optional notes for this entry"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    <!-- Auto Update Toggle -->
    <div class="mb-4">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="autoUpdateToggle" checked>
            <label class="custom-control-label" for="autoUpdateToggle">Auto Update</label>
        </div>
    </div>

    <div class="mb-4 d-flex justify-content-between">
        <h2>Saved Data:</h2>
        <button id="deleteAllBtn" class="btn btn-danger">Delete All Data</button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th style="width: 300px;">Data</th>
                <th>Notes</th>
                <th>Referrer</th>
                <th>Client IP</th>
                <th>Date and Time</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dataArray as $dataItem) : ?>
                <tr>
                    <td><?php echo htmlentities($dataItem['id']); ?></td>
                    <td style="width: 300px;">
                        <div id="data_<?php echo $dataItem['id']; ?>" class="data-container">
                            <?php echo htmlentities(substr($dataItem['data'], 0, 50)); ?>
                        </div>
                        <div id="fullData_<?php echo $dataItem['id']; ?>" style="display: none;" class="data-container">
                            <?php echo htmlentities($dataItem['data']); ?>
                        </div>
                        <?php if (strlen($dataItem['data']) > 50) : ?>
                            <button class="btn btn-link btn-sm" onclick="toggleData(<?php echo $dataItem['id']; ?>)">
                                <span id="toggleText_<?php echo $dataItem['id']; ?>">Show All</span>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm ml-2 btn-copy"
                                onclick="copyToClipboard(<?php echo $dataItem['id']; ?>)">Copy to Clipboard
                        </button>
                    </td>
                    <td>
                        <div id="notes_<?php echo $dataItem['id']; ?>" class="notes-content">
                            <?php echo htmlentities($dataItem['notes'] ?? ''); ?>
                        </div>
                        <button class="btn btn-link btn-sm" onclick="editNotes(<?php echo $dataItem['id']; ?>)">
                            Edit Notes
                        </button>
                    </td>
                    <td><?php echo htmlentities($dataItem['referrer']); ?></td>
                    <td><?php echo htmlentities($dataItem['client_ip']); ?></td>
                    <td><?php echo htmlentities($dataItem['entry_datetime']); ?></td>
                    <td>
                        <form method="POST" action="index.php">
                            <input type="hidden" name="delete" value="<?php echo $dataItem['id']; ?>">
                            <button type="button" class="btn btn-danger btn-sm btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Copy to Clipboard Modal -->
<div class="modal fade" id="copyModal" tabindex="-1" role="dialog" aria-labelledby="copyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="copyModalLabel">Data Copied</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="copyModalMessage"></p>
                <div class="form-group">
                    <label for="copiedData">Copied Data:</label>
                    <textarea class="form-control" id="copiedData" rows="5" readonly></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this item?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete All Confirmation Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" role="dialog" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAllModalLabel">Confirm Delete All</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-danger">Warning: This will permanently delete all data entries. This action cannot be undone.</p>
                <p>Are you sure you want to delete all data?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAll">Delete All</button>
            </div>
        </div>
    </div>
</div>

<!-- Notes Editing Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" role="dialog" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Edit Notes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="notesForm">
                    <input type="hidden" id="notesEntryId" value="">
                    <div class="form-group">
                        <label for="notesText">Notes:</label>
                        <textarea class="form-control" id="notesText" rows="5"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNotes">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Toggle between showing truncated data and full data
     *
     * @param {number} id - The ID of the data entry to toggle
     */
    function toggleData(id) {
        var dataElement = document.getElementById('data_' + id);
        var fullDataElement = document.getElementById('fullData_' + id);
        var toggleTextElement = document.getElementById('toggleText_' + id);

        if (fullDataElement.style.display === 'none') {
            // Show full data
            dataElement.style.display = 'none';
            fullDataElement.style.display = 'block';
            toggleTextElement.innerText = 'Show Less';
        } else {
            // Show truncated data
            dataElement.style.display = 'block';
            fullDataElement.style.display = 'none';
            toggleTextElement.innerText = 'Show All';
        }
    }

    /**
     * Copy data to clipboard, with automatic base64 detection and decoding
     * Shows a Bootstrap modal instead of an alert
     *
     * @param {number} id - The ID of the data entry to copy
     */
    function copyToClipboard(id) {
        var dataElement = document.getElementById('fullData_' + id);
        var decodedData = tryBase64Decode(dataElement.innerText);
        var dataToCopy = decodedData !== null ? decodedData : dataElement.innerText;

        // Create a temporary textarea element to perform the copy operation
        var textArea = document.createElement('textarea');
        textArea.value = dataToCopy;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);

        // Show confirmation in Bootstrap modal
        var copiedLabel = decodedData !== null ? 'Decoded data' : 'Data';
        document.getElementById('copyModalMessage').textContent = copiedLabel + ' copied to clipboard:';
        document.getElementById('copiedData').value = dataToCopy;
        $('#copyModal').modal('show');
    }

    /**
     * Edit notes for a data entry
     * Uses a Bootstrap modal for editing
     *
     * @param {number} id - The ID of the data entry to edit notes for
     */
    function editNotes(id) {
        // Get the notes content element
        var notesElement = document.getElementById('notes_' + id);

        if (notesElement) {
            var notes = notesElement.textContent.trim();

            // Set values in the modal
            document.getElementById('notesEntryId').value = id;
            document.getElementById('notesText').value = notes;

            // Show the modal
            $('#notesModal').modal('show');
        } else {
            console.error('Notes element not found for ID: ' + id);
        }
    }

    /**
     * Attempt to decode a string as base64
     *
     * @param {string} input - The string to try decoding
     * @return {string|null} The decoded string if successful, null otherwise
     */
    function tryBase64Decode(input) {
        try {
            return window.atob(input);
        } catch (e) {
            return null;
        }
    }

    /**
     * Periodically check for new data and refresh the page when new entries are found
     * Respects the auto-update toggle setting
     */
    function setupAutoUpdate() {
        var latestId = <?php echo !empty($dataArray) ? $dataArray[0]['id'] : 0; ?>; // Get the latest saved data ID
        var updateInterval;

        function performCheck() {
            if ($('#autoUpdateToggle').is(':checked')) {
                $.ajax({
                    url: 'check_new_data.php',
                    type: 'GET',
                    data: {latestId: latestId},
                    success: function (response) {
                        if (response === 'true') {
                            window.location = 'index.php'; // Reload the page to show new data
                        }
                    }
                });
            }
        }

        // Initialize based on localStorage value
        if (localStorage.getItem('autoUpdate') === 'false') {
            $('#autoUpdateToggle').prop('checked', false);
        }

        // Initial update interval
        updateInterval = setInterval(performCheck, 5000); // Check every 5 seconds

        // Handle toggle changes
        $('#autoUpdateToggle').change(function() {
            localStorage.setItem('autoUpdate', $(this).is(':checked'));

            if ($(this).is(':checked')) {
                // Restart interval if it was cleared
                if (!updateInterval) {
                    updateInterval = setInterval(performCheck, 5000);
                }
            } else {
                // Clear interval if it exists
                if (updateInterval) {
                    clearInterval(updateInterval);
                    updateInterval = null;
                }
            }
        });
    }

    // Document ready handler
    $(document).ready(function() {
        // Initialize auto-update
        setupAutoUpdate();

        // Handle save notes button click
        $('#saveNotes').click(function() {
            var id = document.getElementById('notesEntryId').value;
            var notes = document.getElementById('notesText').value;

            // Send AJAX request to update notes
            $.ajax({
                url: 'update_notes.php',
                type: 'POST',
                data: {
                    id: id,
                    notes: notes
                },
                success: function(response) {
                    var notesElement = document.getElementById('notes_' + id);
                    if (notesElement) {
                        notesElement.textContent = notes;
                    }
                    $('#notesModal').modal('hide');
                },
                error: function() {
                    alert('Failed to update notes.');
                    $('#notesModal').modal('hide');
                }
            });
        });

        // Initialize delete confirmation for individual items
        $('.btn-delete').click(function(e) {
            e.preventDefault();
            var deleteForm = $(this).closest('form');
            $('#confirmDelete').data('form', deleteForm);
            $('#deleteModal').modal('show');
        });

        // Handle delete confirmation
        $('#confirmDelete').click(function() {
            var form = $(this).data('form');
            form.submit();
        });

        // Handle delete all button
        $('#deleteAllBtn').click(function() {
            $('#deleteAllModal').modal('show');
        });

        // Handle delete all confirmation
        $('#confirmDeleteAll').click(function() {
            $.ajax({
                url: 'delete_all.php',
                type: 'POST',
                success: function(response) {
                    $('#deleteAllModal').modal('hide');
                    window.location.reload(); // Reload the page to show the empty table
                },
                error: function() {
                    alert('Error: Failed to delete all entries.');
                    $('#deleteAllModal').modal('hide');
                }
            });
        });
    });
</script>

</body>
</html>