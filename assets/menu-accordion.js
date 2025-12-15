/**
 * WP Restaurant Menu - Accordion Functionality
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        initAccordion();
    });
    
    function initAccordion() {
        const headers = document.querySelectorAll('.wpr-accordion-header');
        
        if (!headers.length) return;
        
        // Erste Kategorie standardmäßig öffnen
        if (headers[0]) {
            headers[0].classList.add('active');
            const firstContent = headers[0].nextElementSibling;
            if (firstContent) {
                firstContent.style.maxHeight = firstContent.scrollHeight + 'px';
            }
        }
        
        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const isActive = this.classList.contains('active');
                
                // Schließe alle anderen
                headers.forEach(function(h) {
                    if (h !== header) {
                        h.classList.remove('active');
                        const c = h.nextElementSibling;
                        if (c) c.style.maxHeight = '0';
                    }
                });
                
                // Toggle aktuelles Element
                if (isActive) {
                    this.classList.remove('active');
                    content.style.maxHeight = '0';
                } else {
                    this.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        });
        
        // "Alle aufklappen" Button hinzufügen
        const accordion = document.querySelector('.wpr-accordion-menu');
        if (accordion) {
            const controlsDiv = document.createElement('div');
            controlsDiv.className = 'wpr-accordion-controls';
            controlsDiv.innerHTML = `
                <button class="wpr-expand-all">▼ Alle aufklappen</button>
                <button class="wpr-collapse-all">▲ Alle zuklappen</button>
            `;
            accordion.insertBefore(controlsDiv, accordion.firstChild);
            
            // Expand All
            controlsDiv.querySelector('.wpr-expand-all').addEventListener('click', function() {
                headers.forEach(function(header) {
                    header.classList.add('active');
                    const content = header.nextElementSibling;
                    if (content) content.style.maxHeight = content.scrollHeight + 'px';
                });
            });
            
            // Collapse All
            controlsDiv.querySelector('.wpr-collapse-all').addEventListener('click', function() {
                headers.forEach(function(header) {
                    header.classList.remove('active');
                    const content = header.nextElementSibling;
                    if (content) content.style.maxHeight = '0';
                });
            });
        }
    }
})();
