<?php
require_once __DIR__ . '/../config/menu_config.php';

// Page title
function getPageTitle()
{
    return MenuConfig::getCurrentPageData()['title'];
}

// Breadcrumb
function generateDynamicBreadcrumb()
{
    $pageInfo = MenuConfig::getCurrentPageData();
    $currentPage = basename($_SERVER['PHP_SELF']);

    $html = '
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>';

    if ($currentPage !== 'home.php') {
        $html .= '<li class="breadcrumb-item">'
            . htmlspecialchars($pageInfo['section']) .
            '</li>';
    }

    $html .= '<li class="breadcrumb-item active" aria-current="page">'
        . htmlspecialchars($pageInfo['title']) .
        '</li>
        </ol>
    </nav>';

    return $html;
}

// Meta description
function getMetaDescription()
{
    return MenuConfig::getCurrentPageData()['description']
        ?? 'Phoenix Portal internal management system.';
}

// Meta author
function getMetaAuthor()
{
    return 'RSSI NGO';
}
