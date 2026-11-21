(function () {
    'use strict';

    var body = document.body;
    var sidebar = document.getElementById('sidebar');
    var shell = document.getElementById('appShell');
    var backdrop = document.getElementById('sidebarBackdrop');
    var mobileButton = document.getElementById('hamburgerButton');
    var desktopButton = document.getElementById('desktopToggle');
    var closeButton = document.getElementById('sidebarClose');
    var indicator = document.getElementById('activeIndicator');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // จดจำสถานะเมนูย่อโดยไม่ให้ข้อจำกัดของเบราว์เซอร์รบกวนการใช้งาน
    try {
        if (window.innerWidth >= 992 && window.localStorage.getItem('mekesin-sidebar-collapsed') === 'true') {
            body.classList.add('sidebar-collapsed');
        }
    } catch (error) {
        console.warn('ไม่สามารถอ่านสถานะเมนูจาก localStorage ได้', error);
    }

    function updateToggleState() {
        if (desktopButton) {
            desktopButton.setAttribute('aria-expanded', String(!body.classList.contains('sidebar-collapsed')));
        }
    }

    function setIndicator() {
        if (!indicator || window.innerWidth < 992 || body.classList.contains('sidebar-collapsed')) {
            if (indicator) indicator.classList.remove('is-visible');
            return;
        }
        var activeLink = sidebar.querySelector('.side-link.active');
        if (!activeLink) return;
        var group = activeLink.closest('.nav-group');
        indicator.style.transform = 'translateY(' + (activeLink.offsetTop - group.offsetTop) + 'px)';
        indicator.style.height = activeLink.offsetHeight + 'px';
        indicator.classList.add('is-visible');
    }

    function openMobileSidebar() {
        sidebar.classList.add('mobile-open');
        backdrop.classList.add('is-visible');
        mobileButton.setAttribute('aria-expanded', 'true');
        document.documentElement.classList.add('mobile-menu-open');
        closeButton.focus();
    }

    function closeMobileSidebar(returnFocus) {
        sidebar.classList.remove('mobile-open');
        backdrop.classList.remove('is-visible');
        mobileButton.setAttribute('aria-expanded', 'false');
        document.documentElement.classList.remove('mobile-menu-open');
        if (returnFocus) mobileButton.focus();
    }

    mobileButton.addEventListener('click', openMobileSidebar);
    closeButton.addEventListener('click', function () { closeMobileSidebar(true); });
    backdrop.addEventListener('click', function () { closeMobileSidebar(true); });
    desktopButton.addEventListener('click', function () {
        body.classList.toggle('sidebar-collapsed');
        updateToggleState();
        setIndicator();
        try {
            window.localStorage.setItem('mekesin-sidebar-collapsed', String(body.classList.contains('sidebar-collapsed')));
        } catch (error) {
            console.warn('ไม่สามารถบันทึกสถานะเมนูลง localStorage ได้', error);
        }
    });

    var accordion = document.querySelector('.accordion-toggle');
    var submenu = document.getElementById('dataSubmenu');
    accordion.addEventListener('click', function () {
        var expanded = accordion.getAttribute('aria-expanded') === 'true';
        accordion.setAttribute('aria-expanded', String(!expanded));
        accordion.classList.toggle('is-open', !expanded);
        submenu.classList.toggle('is-open', !expanded);
        submenu.setAttribute('aria-hidden', String(expanded));
    });

    function setDropdown(button, menu, open) {
        button.setAttribute('aria-expanded', String(open));
        menu.hidden = !open;
    }
    var dropdownPairs = [
        [document.getElementById('notificationButton'), document.getElementById('notificationMenu')],
        [document.getElementById('accountButton'), document.getElementById('accountMenu')]
    ];
    dropdownPairs.forEach(function (pair) {
        var button = pair[0];
        var menu = pair[1];
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            var shouldOpen = button.getAttribute('aria-expanded') !== 'true';
            dropdownPairs.forEach(function (otherPair) { setDropdown(otherPair[0], otherPair[1], false); });
            setDropdown(button, menu, shouldOpen);
        });
        menu.addEventListener('click', function (event) { event.stopPropagation(); });
    });
    document.addEventListener('click', function () {
        dropdownPairs.forEach(function (pair) { setDropdown(pair[0], pair[1], false); });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            dropdownPairs.forEach(function (pair) {
                var wasOpen = pair[0].getAttribute('aria-expanded') === 'true';
                setDropdown(pair[0], pair[1], false);
                if (wasOpen) pair[0].focus();
            });
            if (sidebar.classList.contains('mobile-open')) closeMobileSidebar(true);
        }
        if (event.key === '/' && !/input|textarea/i.test(document.activeElement.tagName)) {
            event.preventDefault();
            document.getElementById('recordSearch').focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && sidebar.classList.contains('mobile-open')) closeMobileSidebar(false);
        setIndicator();
        updateToggleState();
    });
    updateToggleState();
    requestAnimationFrame(setIndicator);

    // นับตัวเลขเมื่อการ์ดเข้ามาในพื้นที่แสดงผล
    var counters = document.querySelectorAll('[data-count]');
    function animateCount(element) {
        var target = Number(element.getAttribute('data-count')) || 0;
        if (reduceMotion) {
            element.textContent = target.toLocaleString('th-TH');
            return;
        }
        var startedAt = 0;
        var duration = 1050;
        function frame(time) {
            if (!startedAt) startedAt = time;
            var progress = Math.min((time - startedAt) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = Math.floor(target * eased).toLocaleString('th-TH');
            if (progress < 1) window.requestAnimationFrame(frame);
        }
        window.requestAnimationFrame(frame);
    }
    if ('IntersectionObserver' in window) {
        var counterObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.25 });
        counters.forEach(function (counter) { counterObserver.observe(counter); });
    } else {
        counters.forEach(animateCount);
    }

    var tableLoading = document.getElementById('tableLoading');
    window.setTimeout(function () { tableLoading.classList.add('is-visible'); }, 0);
    window.setTimeout(function () { tableLoading.classList.remove('is-visible'); }, reduceMotion ? 0 : 650);

    // ค้นหาเฉพาะแถวในตาราง โดยไม่ต้องส่งข้อมูลออกจากหน้า
    var search = document.getElementById('recordSearch');
    var tableRows = Array.prototype.slice.call(document.querySelectorAll('#recordsTable tbody tr'));
    var noResults = document.getElementById('noResults');
    search.addEventListener('input', function () {
        var query = search.value.trim().toLocaleLowerCase('th-TH');
        var visibleCount = 0;
        tableRows.forEach(function (row) {
            var matches = row.textContent.toLocaleLowerCase('th-TH').indexOf(query) !== -1;
            row.hidden = !matches;
            if (matches) visibleCount++;
        });
        noResults.hidden = visibleCount !== 0;
    });

    // แสดงแถบความคืบหน้าเฉพาะลิงก์ที่นำทางไปหน้าอื่น
    var progress = document.getElementById('pageProgress');
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin || destination.href === window.location.href || destination.hash) return;
        progress.classList.add('is-running');
        window.setTimeout(function () { progress.classList.remove('is-running'); }, 1800);
    });

    // สร้าง ripple จากตำแหน่งคลิกให้ปุ่มที่กดได้
    document.addEventListener('click', function (event) {
        var button = event.target.closest('button, .button');
        if (!button || reduceMotion) return;
        var rect = button.getBoundingClientRect();
        var ripple = document.createElement('span');
        ripple.className = 'ripple';
        ripple.style.left = (event.clientX - rect.left - 5) + 'px';
        ripple.style.top = (event.clientY - rect.top - 5) + 'px';
        button.appendChild(ripple);
        ripple.addEventListener('animationend', function () { ripple.remove(); }, { once: true });
    });
}());
