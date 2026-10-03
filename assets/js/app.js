// assets/js/app.js - Modern Interactive UI Enhancements

// ═══ Page Loading Skeleton Loader Dismissal ═══
(function () {
    function hideSkeleton() {
        const skeleton = document.getElementById('pageSkeletonLoader');
        if (skeleton) {
            skeleton.classList.add('fade-out');
            setTimeout(() => {
                skeleton.style.display = 'none';
            }, 400);
        }
    }

    if (document.readyState === 'complete') {
        setTimeout(hideSkeleton, 150);
    } else {
        window.addEventListener('load', function () {
            setTimeout(hideSkeleton, 150);
        });
        // Fallback safety timeout so skeleton never blocks if an asset fails to load
        setTimeout(hideSkeleton, 1200);
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    // Mobile Sidebar Drawer Toggle & Overlay
    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (sidebarOverlay) sidebarOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (sidebar && sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function (e) {
            e.stopPropagation();
            closeSidebar();
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    // Auto close sidebar when clicking a nav link on mobile
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }

    // Close on window resize if resized to desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992 && sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Auto calculate BMI in Clinic forms
    const heightInput = document.getElementById('HeightCM');
    const weightInput = document.getElementById('WeightKG');
    const bmiDisplay = document.getElementById('bmi_display');
    const bmiCategory = document.getElementById('bmi_category');

    function calculateBMI() {
        if (!heightInput || !weightInput || !bmiDisplay) return;
        const height = parseFloat(heightInput.value);
        const weight = parseFloat(weightInput.value);

        if (height > 0 && weight > 0) {
            const heightM = height / 100;
            const bmi = (weight / (heightM * heightM)).toFixed(1);
            bmiDisplay.value = bmi;

            if (bmiCategory) {
                if (bmi < 18.5) {
                    bmiCategory.textContent = 'Underweight';
                    bmiCategory.className = 'badge bg-warning text-dark';
                } else if (bmi >= 18.5 && bmi < 24.9) {
                    bmiCategory.textContent = 'Normal weight';
                    bmiCategory.className = 'badge bg-success';
                } else if (bmi >= 25 && bmi < 29.9) {
                    bmiCategory.textContent = 'Overweight';
                    bmiCategory.className = 'badge bg-warning text-dark';
                } else {
                    bmiCategory.textContent = 'Obese';
                    bmiCategory.className = 'badge bg-danger';
                }
            }
        } else {
            bmiDisplay.value = '';
            if (bmiCategory) bmiCategory.textContent = '';
        }
    }

    if (heightInput && weightInput) {
        heightInput.addEventListener('input', calculateBMI);
        weightInput.addEventListener('input', calculateBMI);
        calculateBMI();
    }

    // Confirm dialog for critical action buttons
    document.querySelectorAll('[data-confirm]').forEach(button => {
        button.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed with this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Auto dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(alert => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                try {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                } catch (e) {
                    alert.style.display = 'none';
                }
            } else {
                alert.style.display = 'none';
            }
        });
    }, 5000);
});
