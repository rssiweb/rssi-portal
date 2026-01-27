<?php
// includes/sidebar_generator.php or directly in your header.php

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

            echo <<<HTML
<li class="nav-item">
    <a class="nav-link {$collapsedClass}"
       data-bs-target="#{$sidebarId}"
       data-bs-toggle="collapse"
       href="#">
        <span>{$section}</span>
        <i class="bi bi-chevron-down ms-auto"></i>
    </a>

    <ul id="{$sidebarId}" class="nav-content collapse {$showClass}" data-bs-parent="#sidebar-nav">
HTML;

            foreach ($sectionData['pages'] as $page => $info) {

                $isActive = ($page === $currentPage) ? 'active' : '';
                $idAttr = isset($info['link_id']) ? 'id="' . $info['link_id'] . '"' : '';

                echo <<<HTML
<li>
    <a {$idAttr} href="{$page}" class="{$isActive}">
        <span>{$info['title']}</span>
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
