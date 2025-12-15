/**
 * WP Restaurant Menu - Fullscreen Overlay Search
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        initFullscreenSearch();
    });
    
    function initFullscreenSearch() {
        const searchInput = document.querySelector('.wpr-search-input');
        const searchOverlay = document.querySelector('.wpr-search-overlay');
        const closeBtn = document.querySelector('.wpr-search-close');
        const menuGrid = document.querySelector('.wpr-menu-grid');
        const resultsGrid = document.querySelector('.wpr-search-results-grid');
        const resultsCount = document.querySelector('.wpr-results-count');
        const filterBtns = document.querySelectorAll('.wpr-filter-btn');
        
        if (!searchInput || !searchOverlay) return;
        
        const allMenuItems = Array.from(document.querySelectorAll('.wpr-menu-item'));
        let currentCategory = 'all';
        
        // Klick auf Suchfeld öffnet Overlay
        searchInput.addEventListener('click', function(e) {
            e.preventDefault();
            openSearchOverlay();
        });
        
        // Schließen-Button
        if (closeBtn) {
            closeBtn.addEventListener('click', closeSearchOverlay);
        }
        
        // ESC-Taste zum Schließen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
                closeSearchOverlay();
            }
        });
        
        // Klick außerhalb schließt
        searchOverlay.addEventListener('click', function(e) {
            if (e.target === searchOverlay) {
                closeSearchOverlay();
            }
        });
        
        // Live-Suche im Overlay
        const overlayInput = searchOverlay.querySelector('.wpr-overlay-search-input');
        if (overlayInput) {
            overlayInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                filterAndDisplayResults(searchTerm, currentCategory);
            });
        }
        
        // Kategorie-Filter
        filterBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                filterBtns.forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                currentCategory = this.dataset.category;
                
                const searchTerm = overlayInput ? overlayInput.value.toLowerCase().trim() : '';
                filterAndDisplayResults(searchTerm, currentCategory);
            });
        });
        
        function openSearchOverlay() {
            searchOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            setTimeout(function() {
                if (overlayInput) {
                    overlayInput.focus();
                }
            }, 300);
            
            // Initial alle Gerichte anzeigen
            filterAndDisplayResults('', 'all');
        }
        
        function closeSearchOverlay() {
            searchOverlay.classList.remove('active');
            document.body.style.overflow = '';
            if (overlayInput) {
                overlayInput.value = '';
            }
            currentCategory = 'all';
            filterBtns.forEach(function(btn) {
                btn.classList.remove('active');
                if (btn.dataset.category === 'all') {
                    btn.classList.add('active');
                }
            });
        }
        
        function filterAndDisplayResults(searchTerm, category) {
            if (!resultsGrid) return;
            
            // Leere bisherige Ergebnisse
            resultsGrid.innerHTML = '';
            
            let visibleCount = 0;
            
            allMenuItems.forEach(function(item) {
                const title = item.dataset.title || '';
                const description = item.dataset.description || '';
                
                const matchesSearch = !searchTerm || 
                    title.includes(searchTerm) || 
                    description.includes(searchTerm);
                
                const matchesCategory = category === 'all' || 
                    item.classList.contains('wpr-cat-' + category);
                
                if (matchesSearch && matchesCategory) {
                    const clonedItem = item.cloneNode(true);
                    clonedItem.style.animation = 'wprFadeIn 0.3s ease-in';
                    resultsGrid.appendChild(clonedItem);
                    visibleCount++;
                }
            });
            
            // Ergebnis-Zähler aktualisieren
            if (resultsCount) {
                if (searchTerm) {
                    resultsCount.textContent = visibleCount + ' Gericht' + (visibleCount !== 1 ? 'e' : '') + ' gefunden für "' + searchTerm + '"';
                } else if (category !== 'all') {
                    resultsCount.textContent = visibleCount + ' Gericht' + (visibleCount !== 1 ? 'e' : '') + ' in dieser Kategorie';
                } else {
                    resultsCount.textContent = visibleCount + ' Gericht' + (visibleCount !== 1 ? 'e' : '') + ' verfügbar';
                }
            }
            
            // Keine Ergebnisse Meldung
            if (visibleCount === 0) {
                resultsGrid.innerHTML = '<div class="wpr-no-results"><p>🔍 Keine Gerichte gefunden</p><p class="wpr-no-results-hint">Versuche einen anderen Suchbegriff oder wähle eine andere Kategorie.</p></div>';
            }
        }
    }
    
    // CSS Animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes wprFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
})();
