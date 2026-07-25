function toggleMobileMenu() {
    document.getElementById('mobileNav').classList.toggle('active');
}

document.querySelectorAll('.radio-option input').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.radio-option').forEach(opt => opt.classList.remove('active'));
        this.closest('.radio-option').classList.add('active');
    });
});

document.querySelectorAll('.file-input').forEach(input => {
    input.addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'اختر ملفاً';
        const label = this.nextElementSibling;
        if (label) label.textContent = fileName;
    });
});

function apiRequest(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    }).then(r => r.json());
}

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

function confirmAction(message, callback) {
    if (confirm(message)) callback();
}
