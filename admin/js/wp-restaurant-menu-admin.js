/**
 * Admin JavaScript für WP Restaurant Menu
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Einfache Validierung für Preis-Eingabe
        $('#menu_item_price').on('blur', function() {
            var price = $(this).val();
            
            // Ersetze Komma durch Punkt für Validierung
            price = price.replace(',', '.');
            
            // Prüfe ob es eine gültige Zahl ist
            if (price && isNaN(price)) {
                alert('Bitte geben Sie einen gültigen Preis ein (z.B. 12.50 oder 12,50)');
                $(this).focus();
            }
        });

        // Vegan-Checkbox aktiviert automatisch Vegetarisch
        $('input[name="menu_item_vegan"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('input[name="menu_item_vegetarian"]').prop('checked', true);
            }
        });

        // Verhindere das Deaktivieren von Vegetarisch wenn Vegan aktiv ist
        $('input[name="menu_item_vegetarian"]').on('change', function() {
            if (!$(this).is(':checked') && $('input[name="menu_item_vegan"]').is(':checked')) {
                $(this).prop('checked', true);
                alert('Vegane Gerichte sind automatisch vegetarisch.');
            }
        });

    });

})(jQuery);
