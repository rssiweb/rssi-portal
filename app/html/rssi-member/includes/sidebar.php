<?php

// Add CSS at the beginning
echo '<style>
@keyframes blink {
    50% { opacity: 0.5; background-color: #ff4444; }
}
.blink-red-icon {
    display: inline-block;
    width: 10px;
    height: 10px;
    background-color: #dc3545;
    border-radius: 50%;
    margin-left: 8px;
    animation: blink 1s step-end infinite;
}
.pending-badge {
    background-color: #dc3545;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
    margin-left: 8px;
    display: inline-block;
}
</style>';

require_once __DIR__ . '/../config/menu_config.php';

function generateSidebarMenu()
{
    $menuStructure = MenuConfig::getMenuStructure();
    $currentPage = basename($_SERVER['PHP_SELF']);

    echo '<ul class="sidebar-nav" id="sidebar-nav">';

    foreach ($menuStructure as $section => $sectionData) {

        /* ================= TOP LEVEL ================= */
        if ($sectionData['type'] === 'top_level') {

            foreach ($sectionData['pages'] as $page => $info) {

                $isActive = ($page === $currentPage) ? 'active' : '';
                $idAttr = isset($info['link_id']) ? 'id="' . $info['link_id'] . '"' : '';

                echo <<<HTML
<li class="nav-item">
    <a class="nav-link collapsed {$isActive}" {$idAttr} href="{$page}">
        <span>{$info['title']}</span>
    </a>
</li>
HTML;
            }

            /* ================= COLLAPSIBLE ================= */
        } else {

            $sidebarId = $sectionData['sidebar_id'];
            $isExpanded = false;

            foreach ($sectionData['pages'] as $page => $info) {
                if ($page === $currentPage) {
                    $isExpanded = true;
                    break;
                }
            }

            $collapsedClass = $isExpanded ? '' : 'collapsed';
            $showClass = $isExpanded ? 'show' : '';

            // For Worklist parent menu - show blinking red icon if ANY pending exists
            $parentBadge = '';
            if ($section === 'Worklist') {
                $totalPending = getTotalPendingWorklistCount();
                if ($totalPending > 0) {
                    $parentBadge = '<span class="blink-red-icon"></span>';
                }
            }

            echo <<<HTML
<li class="nav-item">
    <a class="nav-link {$collapsedClass}"
       data-bs-target="#{$sidebarId}"
       data-bs-toggle="collapse"
       href="#">
        <span>{$section}</span>
        {$parentBadge}
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="{$sidebarId}" class="nav-content collapse {$showClass}" data-bs-parent="#sidebar-nav">
HTML;

            foreach ($sectionData['pages'] as $page => $info) {

                $isActive = ($page === $currentPage) ? 'active' : '';
                $idAttr = isset($info['link_id']) ? 'id="' . $info['link_id'] . '"' : '';

                // For child menu items - show number badge
                $childBadge = '';
                if ($page === 'hrms_worklist.php') {
                    $hrmsCount = getHrmsPendingCount();
                    $studentCount = getStudentPendingCount();
                    $totalCount = $hrmsCount + $studentCount;
                    if ($totalCount > 0) {
                        $childBadge = '<span class="pending-badge">' . $totalCount . '</span>';
                    }
                } elseif ($page === 'post_worklist.php') {
                    $count = getPostPendingCount();
                    if ($count > 0) {
                        $childBadge = '<span class="pending-badge">' . $count . '</span>';
                    }
                } elseif ($page === 'iexplore_worklist.php') {
                    $count = getIexplorePendingCount();
                    if ($count > 0) {
                        $childBadge = '<span class="pending-badge">' . $count . '</span>';
                    }
                }

                echo <<<HTML
<li>
    <a {$idAttr} href="{$page}" class="{$isActive}">
        <span>{$info['title']}</span>
        {$childBadge}
    </a>
</li>
HTML;
            }

            echo '</ul></li>';
        }
    }

    /* ================= TEMP STATIC PAGES ================= */
    echo <<<HTML
<li class="nav-heading">Pages</li>

<li class="nav-item">
    <a class="nav-link collapsed" href="hrms.php">
        <i class="bi bi-person"></i>
        <span>Profile</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link collapsed" href="#">
        <i class="bi bi-question-circle"></i>
        <span>F.A.Q</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link collapsed" href="#">
        <i class="bi bi-envelope"></i>
        <span>Contact</span>
    </a>
</li>
HTML;

    echo '</ul>';
}
