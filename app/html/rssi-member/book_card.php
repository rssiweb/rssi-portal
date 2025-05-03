<?php
$is_admin = ($role === 'Admin' || $role === 'Offline Manager');
?>

<div class="col">
    <div class="card book-card h-100 <?= ($book['available_copies'] <= 0 || $book['status'] == 'unavailable') ? 'unavailable-book' : '' ?>">
        <div class="book-cover">
            <?php if (!empty($book['cover_image'])): ?>
                <?php
                // Extract photo ID from Google Drive link
                $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
                if (preg_match($pattern, $book['cover_image'], $matches)) {
                    $photoID = $matches[1];
                    $previewUrl = "https://drive.google.com/file/d/{$photoID}/preview";
                    echo '<iframe src="' . $previewUrl . '" width="150" height="200" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                } else {
                    echo '<div class="text-center p-3">
                            <i class="bi bi-book" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">Invalid Cover URL</p>
                          </div>';
                }
                ?>
            <?php else: ?>
                <div class="text-center p-3">
                    <i class="bi bi-book" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0">No Cover Available</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($book['author']) ?></h6>
            <div class="d-flex justify-content-between mb-2">
                <small class="text-muted">Publisher: <?= htmlspecialchars($book['publisher'] ?? 'N/A') ?></small>
                <small class="text-muted">Year: <?= $book['publication_year'] ?? 'N/A' ?></small>
            </div>
            <p class="card-text text-truncate-3"><?= htmlspecialchars($book['description']) ?></p>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Copies: <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?></small>
                <small class="text-muted"><?= htmlspecialchars($book['category'] ?? 'N/A') ?></small>
            </div>
        </div>
        <div class="card-footer bg-white border-0">
            <?php if ($book['available_copies'] > 0 && $book['status'] != 'unavailable'): ?>
                <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#orderModal<?= $book['book_id'] ?>">
                    <i class="bi bi-cart-plus"></i> Place Order
                </button>
            <?php else: ?>
                <button class="btn btn-sm btn-secondary w-100" disabled>
                    <i class="bi bi-cart-x"></i> Not Available
                </button>
            <?php endif; ?>

            <?php if ($is_admin): ?>
                <button class="btn btn-sm btn-outline-primary w-100 mt-2" data-bs-toggle="modal" data-bs-target="#editBookModal<?= $book['book_id'] ?>">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal<?= $book['book_id'] ?>" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Place Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Book: <strong><?= htmlspecialchars($book['title']) ?></strong></label>
                    </div>
                    <div class="mb-3">
                        <label for="student_id_<?= $book['book_id'] ?>" class="form-label">Student ID (optional)</label>
                        <input type="text" class="form-control" id="student_id_<?= $book['book_id'] ?>" name="student_id"
                            placeholder="Leave blank to order for yourself">
                        <small class="text-muted">Only required if ordering for a student</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> You are ordering as: <strong><?= $fullname ?></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="place_order" class="btn btn-primary">Confirm Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal<?= $book['book_id'] ?>" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="#" enctype="multipart/form-data">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_title_<?= $book['book_id'] ?>" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="edit_title_<?= $book['book_id'] ?>" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_author_<?= $book['book_id'] ?>" class="form-label">Author *</label>
                            <input type="text" class="form-control" id="edit_author_<?= $book['book_id'] ?>" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_isbn_<?= $book['book_id'] ?>" class="form-label">ISBN</label>
                            <input type="text" class="form-control" id="edit_isbn_<?= $book['book_id'] ?>" name="isbn" value="<?= htmlspecialchars($book['isbn']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_publisher_<?= $book['book_id'] ?>" class="form-label">Publisher</label>
                            <input type="text" class="form-control" id="edit_publisher_<?= $book['book_id'] ?>" name="publisher" value="<?= htmlspecialchars($book['publisher']) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_publication_year_<?= $book['book_id'] ?>" class="form-label">Publication Year</label>
                            <input type="number" class="form-control" id="edit_publication_year_<?= $book['book_id'] ?>" name="publication_year"
                                min="1800" max="<?= date('Y') ?>" value="<?= $book['publication_year'] ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_category_<?= $book['book_id'] ?>" class="form-label">Category</label>
                            <select class="form-select category-select" id="edit_category_<?= $book['book_id'] ?>" name="category" required>
                                <option value="">Select a category</option>
                                <?php
                                $categories_query = pg_query($con, "SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category");
                                while ($cat = pg_fetch_assoc($categories_query)) {
                                    $selected = ($book['category'] == $cat['category']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($cat['category']) . '" ' . $selected . '>' . htmlspecialchars($cat['category']) . '</option>';
                                }
                                ?>
                                <option value="__new__">+ Add New Category</option>
                            </select>
                            <div class="mt-2 new-category-group" style="display: none;">
                                <input type="text" class="form-control new-category-input" placeholder="Enter new category">
                                <div class="invalid-feedback new-category-feedback">Please enter a category name</div>
                                <button type="button" class="btn btn-outline-secondary mt-2 add-category-btn">Add Category</button>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_location_<?= $book['book_id'] ?>" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_location_<?= $book['book_id'] ?>" name="location" value="<?= htmlspecialchars($book['location']) ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_total_copies_<?= $book['book_id'] ?>" class="form-label">Total Copies *</label>
                            <input type="number" class="form-control" id="edit_total_copies_<?= $book['book_id'] ?>" name="total_copies" min="1" value="<?= $book['total_copies'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status_<?= $book['book_id'] ?>" class="form-label">Status</label>
                            <select class="form-select" id="edit_status_<?= $book['book_id'] ?>" name="status">
                                <option value="available" <?= $book['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="unavailable" <?= $book['status'] == 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cover_image_<?= $book['book_id'] ?>" class="form-label">Upload New Cover Image</label>
                        <input type="file" class="form-control" id="edit_cover_image_<?= $book['book_id'] ?>" name="cover_image" accept="image/*">

                        <?php if (!empty($book['cover_image'])): ?>
                            <div class="mt-2">
                                <small>Current Image: <a href="<?= htmlspecialchars($book['cover_image']) ?>" target="_blank">View Cover Image</a></small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description_<?= $book['book_id'] ?>" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description_<?= $book['book_id'] ?>" name="description" rows="4"><?= htmlspecialchars($book['description']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>