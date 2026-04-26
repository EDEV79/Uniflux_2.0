document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  var sidebar = document.getElementById('appSidebar');
  var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  var sidebarToggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
  var sidebarOverlay = document.getElementById('sidebarOverlay');
  var quickFilterInput = document.querySelector('[data-table-filter]');
  var loadingForms = document.querySelectorAll('[data-loading-form]');
  var perPageSelect = document.querySelector('select[name="per_page"]');
  var animatedItems = document.querySelectorAll('.crm-animate-in, .metric-card, .surface-card');
  var mobileWidth = 768;

  function isMobile() {
    return window.innerWidth < mobileWidth;
  }

  function syncSidebarUI() {
    if (!sidebarToggle || !sidebarToggleIcon) {
      return;
    }

    var open = body.classList.contains('sidebar-open');
    sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    sidebarToggle.setAttribute('aria-label', open ? 'Cerrar menu' : 'Abrir menu');
    sidebarToggleIcon.classList.remove('bi-list', 'bi-x-lg');
    sidebarToggleIcon.classList.add(open ? 'bi-x-lg' : 'bi-list');
  }

  function openSidebar() {
    if (!isMobile()) {
      return;
    }

    body.classList.add('sidebar-open');
    syncSidebarUI();
  }

  function closeSidebar() {
    body.classList.remove('sidebar-open');
    syncSidebarUI();
  }

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function () {
      if (!isMobile()) {
        return;
      }

      if (body.classList.contains('sidebar-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function () {
      closeSidebar();
    });
  }

  if (sidebar) {
    sidebar.addEventListener('click', function (event) {
      var link = event.target.closest('a');
      if (link && isMobile()) {
        closeSidebar();
      }
    });
  }

  window.addEventListener('resize', function () {
    if (!isMobile()) {
      closeSidebar();
    }
  });

  animatedItems.forEach(function (item, idx) {
    item.style.opacity = '0';
    item.style.transform = 'translateY(8px)';
    setTimeout(function () {
      item.style.transition = 'opacity .35s ease, transform .35s ease';
      item.style.opacity = '1';
      item.style.transform = 'translateY(0)';
    }, 60 * idx);
  });

  if (quickFilterInput) {
    quickFilterInput.addEventListener('input', function (event) {
      var targetSelector = event.target.getAttribute('data-table-filter');
      var table = document.querySelector(targetSelector);
      if (!table) {
        return;
      }

      var rows = table.querySelectorAll('[data-filter-row]');
      var term = event.target.value.toLowerCase();

      rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
      });
    });
  }

  if (perPageSelect) {
    perPageSelect.addEventListener('change', function () {
      var form = perPageSelect.closest('form');
      if (form) {
        form.submit();
      }
    });
  }

  loadingForms.forEach(function (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('[data-loading-button]');
      if (!button) {
        return;
      }

      button.classList.add('is-loading');
      button.setAttribute('disabled', 'disabled');
      var original = button.innerHTML;
      button.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...';

      window.setTimeout(function () {
        button.innerHTML = original;
        button.classList.remove('is-loading');
        button.removeAttribute('disabled');
      }, 8000);
    });
  });

  if (window.flatpickr) {
    window.flatpickr('[data-datepicker]', {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'd-m-Y',
      allowInput: true,
      disableMobile: true,
      animate: true,
    });

    window.flatpickr('[data-timepicker]', {
      enableTime: true,
      noCalendar: true,
      dateFormat: 'h:i K',
      altInput: true,
      altFormat: 'h:i K',
      time_24hr: false,
      minuteIncrement: 30,
      allowInput: true,
      disableMobile: true,
      animate: true,
    });
  } else if (window.jQuery) {
    var $ = window.jQuery;
    $('[data-datepicker]').datepicker({
      dateFormat: 'dd-mm-yy',
    });

    $('[data-timepicker]').timepicker({
      timeFormat: 'h:i A',
      interval: 30,
      minTime: '12:00am',
      maxTime: '11:30pm',
      dynamic: false,
      dropdown: true,
      scrollbar: true,
    });
  }

  syncSidebarUI();

  // ── Sidebar Collapse (Desktop) ──────────────────────────────────────────
  var sidebarCollapseBtn = document.querySelector('[data-sidebar-collapse]');
  var contentPanel = document.querySelector('.content-panel');
  var sidebarLinks = document.querySelectorAll('.sidebar-link');

  function loadSidebarState() {
    var collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (collapsed && !isMobile()) {
      collapseSidebar();
    }
  }

  function collapseSidebar() {
    if (isMobile()) return;
    sidebar.classList.add('is-collapsed');
    if (contentPanel) contentPanel.classList.add('is-expanded');
    if (sidebarCollapseBtn) {
      sidebarCollapseBtn.setAttribute('aria-expanded', 'false');
      sidebarCollapseBtn.querySelector('i').classList.remove('bi-chevron-left');
      sidebarCollapseBtn.querySelector('i').classList.add('bi-chevron-right');
      sidebarCollapseBtn.setAttribute('title', 'Expandir barra lateral');
    }
    localStorage.setItem('sidebar-collapsed', 'true');
    addTooltipsToLinks();
  }

  function expandSidebar() {
    sidebar.classList.remove('is-collapsed');
    if (contentPanel) contentPanel.classList.remove('is-expanded');
    if (sidebarCollapseBtn) {
      sidebarCollapseBtn.setAttribute('aria-expanded', 'true');
      sidebarCollapseBtn.querySelector('i').classList.add('bi-chevron-left');
      sidebarCollapseBtn.querySelector('i').classList.remove('bi-chevron-right');
      sidebarCollapseBtn.setAttribute('title', 'Contraer barra lateral');
    }
    localStorage.setItem('sidebar-collapsed', 'false');
    removeTooltipsFromLinks();
  }

  function addTooltipsToLinks() {
    sidebarLinks.forEach(function (link) {
      var label = link.querySelector('span');
      if (label) {
        link.setAttribute('title', label.textContent);
      }
    });
  }

  function removeTooltipsFromLinks() {
    sidebarLinks.forEach(function (link) {
      link.removeAttribute('title');
    });
  }

  if (sidebarCollapseBtn) {
    sidebarCollapseBtn.addEventListener('click', function () {
      if (sidebar.classList.contains('is-collapsed')) {
        expandSidebar();
      } else {
        collapseSidebar();
      }
    });
  }

  window.addEventListener('resize', function () {
    if (!isMobile() && sidebar.classList.contains('is-collapsed')) {
      addTooltipsToLinks();
    } else if (isMobile()) {
      removeTooltipsFromLinks();
    }
  });

  loadSidebarState();

  // ── User Dropdown ────────────────────────────────────────────────────────────
  var userDropdowns = document.querySelectorAll('[data-user-dropdown]');

  userDropdowns.forEach(function (dropdown) {
    var trigger = dropdown.querySelector('[data-user-dropdown-trigger]');

    if (!trigger) {
      return;
    }

    // Click-toggle for touch/mobile devices (hover handles desktop via CSS)
    trigger.addEventListener('click', function (event) {
      event.stopPropagation();
      var isOpen = dropdown.classList.toggle('is-open');
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
  });

  // Close all dropdowns when clicking outside
  document.addEventListener('click', function () {
    userDropdowns.forEach(function (dropdown) {
      dropdown.classList.remove('is-open');
      var trigger = dropdown.querySelector('[data-user-dropdown-trigger]');
      if (trigger) {
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  });
});
