<?php
require_once __DIR__ . '../../includes/functions.php';
require_once __DIR__ . '../../includes/sidebar.php';

// For JavaScript usage
$menuConfigForJS = json_encode(MenuConfig::getAllPages());
?>
<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index.php" class="logo d-flex align-items-center">
      <!-- <img src="../img/phoenix-36-logo-png-transparent.png" alt=""> -->
      <span class="d-none d-lg-block">Phoenix</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="POST" action="#">
      <input type="text" name="query" placeholder="Search" title="Enter search keyword">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle " href="#">
          <i class="bi bi-search"></i>
        </a>
      </li><!-- End Search Icon-->

      <li class="nav-item dropdown pe-3">

        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <img src="<?php echo $photo ?>" alt="Profile" class="rounded-circle" width="30" height="30">
          <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $fullname ?></span>
        </a><!-- End Profile Iamge Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            <h6><?php echo $fullname ?></h6>
            <span>
              Role : <?php echo $role ?>
              <?php if (count($active_roles) > 1) { ?>
                <a href="#" data-bs-toggle="modal" data-bs-target="#switchRoleModal" class="ms-2">
                  <i class="bi bi-arrow-repeat text-muted" title="Switch to another role"></i>
                </a>
              <?php } ?>
            </span>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="hrms.php">
              <i class="bi bi-person"></i>
              <span>My Profile</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="resetpassword.php">
              <i class="bi bi-gear"></i>
              <span>Reset Password</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center" href="setup_2fa.php">
              <i class="bi bi-person-lock"></i>
              <span>Enable 2FA</span>
            </a>
          </li>
          <hr class="dropdown-divider">
          <li>
            <a class="dropdown-item d-flex align-items-center" href="#">
              <i class="bi bi-question-circle"></i>
              <span>Need Help?</span>
            </a>
          </li>
          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="logout.php">
              <i class="bi bi-box-arrow-right"></i>
              <span>Sign Out</span>
            </a>
          </li>

        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header><!-- End Header -->

<!-- Switch Role Modal -->
<div class="modal fade" id="switchRoleModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="switchRoleForm" class="modal-content" method="POST" action="switch_role.php">
      <div class="modal-header">
        <h5 class="modal-title">Switch Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <label class="form-label">Select a Role</label>
        <select name="new_role" id="roleDropdown" class="form-select" required>
          <option value="">Loading...</option>
        </select>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="updateBtn" class="btn btn-primary" type="submit" disabled>
          <span class="button-text">Update Role</span>
          <span class="spinner-border spinner-border-sm d-none" role="status"></span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <?php generateSidebarMenu(); ?>
</aside><!-- End Sidebar-->

<script>
  // ==================== MENU ACTIVATION ====================
  document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();

    // Get menu configuration from PHP
    const menuConfig = <?php echo json_encode(MenuConfig::getAllPages()); ?>;

    // Find current page info
    let pageInfo = menuConfig[currentPage] || {};

    // If page not found directly, check if it's in a group
    if (!pageInfo.title) {
      for (const [page, info] of Object.entries(menuConfig)) {
        if (info.group_pages && info.group_pages.includes(currentPage)) {
          pageInfo = info;
          break;
        }
      }
    }

    // Activate sidebar based on PHP data
    if (pageInfo.sidebar_id) {
      document.getElementById(pageInfo.sidebar_id)?.classList.add('show');
      document.getElementById(pageInfo.link_id)?.classList.add('active');
    }

    // Remove collapsed class for top-level pages
    if (pageInfo.remove_collapsed && pageInfo.link_id) {
      document.getElementById(pageInfo.link_id)?.classList.remove('collapsed');
    }
  });

  // ==================== SWITCH ROLE MODAL ====================
  document.getElementById('switchRoleModal')?.addEventListener('show.bs.modal', function() {
    const dropdown = document.getElementById('roleDropdown');
    const updateBtn = document.getElementById('updateBtn');

    dropdown.innerHTML = "<option value=''>Loading...</option>";
    updateBtn.disabled = true;

    fetch('fetch_roles.php')
      .then(response => response.json())
      .then(data => {
        dropdown.innerHTML = "";

        if (!data.current_role) {
          const defaultOption = document.createElement("option");
          defaultOption.value = "";
          defaultOption.textContent = "Select Role";
          dropdown.appendChild(defaultOption);
        }

        data.roles.forEach(role => {
          const option = document.createElement("option");
          option.value = role.id;
          option.textContent = role.role_name;

          if (role.role_name === data.current_role) {
            option.selected = true;
          }

          dropdown.appendChild(option);
        });

        updateBtn.disabled = false;
      })
      .catch(err => {
        dropdown.innerHTML = "<option>Error loading roles</option>";
        updateBtn.disabled = true;
        console.error("Error fetching roles:", err);
      });
  });

  // ==================== SWITCH ROLE FORM ====================
  document.getElementById("switchRoleForm")?.addEventListener("submit", function(e) {
    e.preventDefault();

    const updateBtn = document.getElementById("updateBtn");
    const buttonText = updateBtn.querySelector(".button-text");
    const spinner = updateBtn.querySelector(".spinner-border");

    updateBtn.disabled = true;
    buttonText.textContent = "Updating...";
    spinner.classList.remove("d-none");

    const formData = new FormData(this);

    fetch("switch_role.php", {
        method: "POST",
        body: formData
      })
      .then(r => r.json())
      .then(res => {
        alert(res.message);
        if (res.status === "success") {
          location.reload();
        }
      })
      .catch(() => {
        alert("Something went wrong!");
      })
      .finally(() => {
        updateBtn.disabled = false;
        buttonText.textContent = "Update Role";
        spinner.classList.add("d-none");
      });
  });

  // ==================== EXTERNAL LINK CONFIRMATION ====================
  document.addEventListener("click", function(e) {
    const link = e.target.closest("a");
    if (!link) return;

    const currentDomain = window.location.hostname;

    try {
      const linkUrl = new URL(link.href, window.location.origin);

      if (link.href.startsWith("javascript:")) return;

      if (linkUrl.hostname && linkUrl.hostname !== currentDomain) {
        e.preventDefault();

        const confirmLeave = confirm(
          "You are being redirected to an external website. Do you want to continue?"
        );

        if (confirmLeave) {
          window.open(link.href, "_blank");
        }
      }
    } catch (err) {}
  });

  // ==================== SEARCH FUNCTIONALITY ====================
  document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = searchForm?.querySelector('input[name="query"]');

    if (searchForm && searchInput) {
      searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch(searchInput.value.trim());
      });

      searchInput.addEventListener('input', function() {
        if (this.value.length >= 2) {
          highlightMenuItems(this.value);
        } else {
          clearHighlights();
        }
      });
    }

    function highlightMenuItems(searchTerm) {
      clearHighlights();
      if (!searchTerm) return;

      const searchLower = searchTerm.toLowerCase();
      const navItems = document.querySelectorAll('#sidebar-nav a.nav-link, #sidebar-nav .nav-content a');

      navItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        const parentLi = item.closest('li.nav-item');

        if (text.includes(searchLower)) {
          item.style.backgroundColor = '#fff3cd';
          item.style.fontWeight = 'bold';

          const parentCollapse = item.closest('.collapse');
          if (parentCollapse && !parentCollapse.classList.contains('show')) {
            parentCollapse.classList.add('show');
          }

          if (parentLi) {
            parentLi.scrollIntoView({
              behavior: 'smooth',
              block: 'nearest'
            });
          }
        }
      });
    }

    function clearHighlights() {
      const navItems = document.querySelectorAll('#sidebar-nav a.nav-link, #sidebar-nav .nav-content a');
      navItems.forEach(item => {
        item.style.backgroundColor = '';
        item.style.fontWeight = '';
      });
    }

    function performSearch(searchTerm) {
      if (!searchTerm) return;

      const searchLower = searchTerm.toLowerCase();
      const navLinks = document.querySelectorAll('#sidebar-nav a[href]:not([href="#"])');
      let firstMatch = null;

      navLinks.forEach(link => {
        const text = link.textContent.toLowerCase();
        const href = link.getAttribute('href');

        if (text.includes(searchLower) && href && !firstMatch) {
          firstMatch = link;
        }
      });

      if (firstMatch) {
        window.location.href = firstMatch.getAttribute('href');
      } else {
        alert('No matching menu items found.');
      }
    }
  });
</script>