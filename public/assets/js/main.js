/**
 * TERIMA JASA - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Bootstrap Tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // 2. Interactive Category Filter in Hero Search Bar
    const searchInput = document.getElementById('heroSearchInput');
    const categoryCards = document.querySelectorAll('.tj-category-card');

    if (searchInput && categoryCards.length > 0) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            categoryCards.forEach(card => {
                const title = card.querySelector('h3')?.textContent.toLowerCase() || '';
                const desc = card.querySelector('p')?.textContent.toLowerCase() || '';
                const parentCol = card.closest('.col-lg-3, .col-md-6, .col-12');
                
                if (title.includes(query) || desc.includes(query)) {
                    if (parentCol) parentCol.style.display = '';
                } else {
                    if (parentCol) parentCol.style.display = 'none';
                }
            });
        });
    }

    // 3. Navbar Sticky Glassmorphism Effect on Scroll
    const navbar = document.querySelector('.tj-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('shadow-sm');
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.classList.remove('shadow-sm');
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.92)';
            }
        });
    }
});
