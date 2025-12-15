/**
 * WP Restaurant Menu - Live Search & Filter
 */
(function() {
    'use strict';
    
    // Warte bis DOM geladen ist
    document.addEventListener('DOMContentLoaded', function() {
        initMenuSearch();
    });
    
    function initMenuSearch() {
        const searchInput = document.querySelector('.wpr-search-input');
        const clearBtn = document.querySelector('.wpr-search-clear');
        const menuItems = document.querySelectorAll('.wpr-menu-item');
        const resultsDiv = document.querySelector('.wpr-search-results');
        const resultsCount = document.querySelector('.wpr-results-count');
        const filterBtns = document.querySelectorAll('.wpr-filter-btn');
        
        if (!searchInput || !menuItems.length) return;
        
        let currentCategory = 'all';
        
        // Live-Suche
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            // Clear-Button anzeigen/verstecken
            if (clearBtn) {
                clearBtn.style.display = searchTerm ? 'block' : 'none';
            }
            
            filterItems(searchTerm, currentCategory);
        });
        
        // Clear-Button
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                this.style.display = 'none';
                filterItems('', currentCategory);
                searchInput.focus();
            });
        }
        
        // Kategorie-Filter
        filterBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                // Aktive Klasse umschalten
                filterBtns.forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                
                currentCategory = this.dataset.category;
                filterItems(searchInput.value.toLowerCase().trim(), currentCategory);
            });
        });
        
        // Filter-Funktion
        function filterItems(searchTerm, category) {
            let visibleCount = 0;
            
            menuItems.forEach(function(item) {
                const title = item.dataset.title || '';
                const description = item.dataset.description || '';
                
                // Suchfilter
                const matchesSearch = !searchTerm || 
                    title.includes(searchTerm) || 
                    description.includes(searchTerm);
                
                // Kategorie-Filter
                const matchesCategory = category === 'all' || 
                    item.classList.contains('wpr-cat-' + category);
                
                // Anzeigen/Verstecken mit Animation
                if (matchesSearch && matchesCategory) {
                    item.style.display = '';
                    item.style.animation = 'wprFadeIn 0.3s ease-in';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Ergebnis-Anzeige
            if (resultsDiv && resultsCount) {
                if (searchTerm || category !== 'all') {
                    resultsDiv.style.display = 'block';
                    resultsCount.textContent = visibleCount + ' Gericht' + (visibleCount !== 1 ? 'e' : '') + ' gefunden';
                } else {
                    resultsDiv.style.display = 'none';
                }
            }
        }
    }
    
    // CSS Animation hinzufügen
    const style = document.createElement('style');
    style.textContent = `
        @keyframes wprFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
})();
