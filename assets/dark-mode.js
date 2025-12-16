/**
 * WP Restaurant Menu - Full WordPress Dark Mode Toggle
 */

(function() {
    'use strict';
    
    const STORAGE_KEY = 'wpr_dark_mode';
    const CLASS_NAME = 'wpr-dark-mode';
    
    // Dark Mode Manager
    class DarkModeManager {
        constructor() {
            this.settings = window.wprDarkMode || {};
            this.init();
        }
        
        init() {
            // Initiale Mode bestimmen
            const savedMode = this.getSavedMode();
            const initialMode = savedMode !== null ? savedMode : this.getSystemMode();
            
            if (initialMode) {
                this.enableDarkMode(false);
            }
            
            // Toggle Button erstellen
            if (this.settings.method === 'manual') {
                this.createToggleButton();
            }
            
            // System-Änderungen beobachten (bei auto mode)
            if (this.settings.method === 'auto') {
                this.watchSystemMode();
            }
        }
        
        getSavedMode() {
            const saved = localStorage.getItem(STORAGE_KEY);
            return saved === 'dark' ? true : (saved === 'light' ? false : null);
        }
        
        getSystemMode() {
            if (!this.settings.method || this.settings.method === 'manual') {
                return false;
            }
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        
        enableDarkMode(save = true) {
            // Füge Klasse zu <body> hinzu (nicht nur .wpr-menu-wrapper)
            document.body.classList.add(CLASS_NAME);
            document.documentElement.classList.add(CLASS_NAME);
            
            if (save) {
                localStorage.setItem(STORAGE_KEY, 'dark');
            }
        }
        
        disableDarkMode(save = true) {
            // Entferne Klasse von <body>
            document.body.classList.remove(CLASS_NAME);
            document.documentElement.classList.remove(CLASS_NAME);
            
            if (save) {
                localStorage.setItem(STORAGE_KEY, 'light');
            }
        }
        
        toggleDarkMode() {
            if (document.body.classList.contains(CLASS_NAME)) {
                this.disableDarkMode();
            } else {
                this.enableDarkMode();
            }
        }
        
        createToggleButton() {
            const button = document.createElement('button');
            button.className = 'wpr-dark-mode-toggle';
            button.setAttribute('aria-label', 'Dark Mode umschalten');
            button.setAttribute('title', 'Dark Mode umschalten');
            button.innerHTML = `
                <span class="wpr-icon-sun">☀️</span>
                <span class="wpr-icon-moon">🌙</span>
            `;
            
            button.addEventListener('click', () => {
                this.toggleDarkMode();
            });
            
            // Position
            if (this.settings.position === 'bottom-left') {
                button.style.right = 'auto';
                button.style.left = '30px';
            }
            
            document.body.appendChild(button);
        }
        
        watchSystemMode() {
            if (!window.matchMedia) return;
            
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            const handler = (e) => {
                // Nur wenn User nichts manuell gesetzt hat
                if (this.getSavedMode() === null) {
                    if (e.matches) {
                        this.enableDarkMode(false);
                    } else {
                        this.disableDarkMode(false);
                    }
                }
            };
            
            // Modern browsers
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handler);
            } else {
                mediaQuery.addListener(handler);
            }
        }
    }
    
    // Init when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new DarkModeManager();
        });
    } else {
        new DarkModeManager();
    }
})();
