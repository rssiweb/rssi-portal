<?php
require_once __DIR__ . '../../includes/functions.php';

echo '<title>' . htmlspecialchars(getPageTitle()) . ' - Phoenix Portal</title>';
echo '<meta name="description" content="' . htmlspecialchars(getMetaDescription()) . '">';
echo '<meta name="author" content="' . htmlspecialchars(getMetaAuthor()) . '">';
echo '<meta name="robots" content="index, follow">';