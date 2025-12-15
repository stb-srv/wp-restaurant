/**
 * Gutenberg Block für Restaurant Menü
 * Modern und benutzerfreundlich
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

registerBlockType('wp-restaurant-menu/menu-display', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        const { menuList, category, columns, showImages, limit } = attributes;

        // Lade verfügbare Menükarten
        const menuLists = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'menu_list', {
                per_page: -1,
            });
        }, []);

        // Lade verfügbare Kategorien
        const categories = useSelect((select) => {
            return select('core').getEntityRecords('taxonomy', 'menu_category', {
                per_page: -1,
            });
        }, []);

        // Optionen für Menükarten-Dropdown
        const menuListOptions = [
            { label: __('Alle Karten', 'wp-restaurant-menu'), value: '' },
            ...(menuLists || []).map((menu) => ({
                label: menu.name,
                value: menu.slug,
            })),
        ];

        // Optionen für Kategorien-Dropdown
        const categoryOptions = [
            { label: __('Alle Kategorien', 'wp-restaurant-menu'), value: '' },
            ...(categories || []).map((cat) => ({
                label: cat.name,
                value: cat.slug,
            })),
        ];

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Menü-Einstellungen', 'wp-restaurant-menu')} initialOpen={true}>
                        <SelectControl
                            label={__('Menükarte wählen', 'wp-restaurant-menu')}
                            value={menuList}
                            options={menuListOptions}
                            onChange={(value) => setAttributes({ menuList: value })}
                            help={__('Wähle eine spezifische Karte (z.B. Hauptkarte, Getränkekarte)', 'wp-restaurant-menu')}
                        />

                        <SelectControl
                            label={__('Kategorie filtern', 'wp-restaurant-menu')}
                            value={category}
                            options={categoryOptions}
                            onChange={(value) => setAttributes({ category: value })}
                            help={__('Optional: Zeige nur eine bestimmte Kategorie', 'wp-restaurant-menu')}
                        />
                    </PanelBody>

                    <PanelBody title={__('Darstellung', 'wp-restaurant-menu')} initialOpen={true}>
                        <RangeControl
                            label={__('Anzahl Spalten', 'wp-restaurant-menu')}
                            value={columns}
                            onChange={(value) => setAttributes({ columns: value })}
                            min={1}
                            max={4}
                        />

                        <ToggleControl
                            label={__('Bilder anzeigen', 'wp-restaurant-menu')}
                            checked={showImages}
                            onChange={(value) => setAttributes({ showImages: value })}
                        />

                        <RangeControl
                            label={__('Maximale Anzahl Gerichte', 'wp-restaurant-menu')}
                            value={limit === -1 ? 100 : limit}
                            onChange={(value) => setAttributes({ limit: value === 100 ? -1 : value })}
                            min={1}
                            max={100}
                            help={limit === -1 ? __('Zeige alle Gerichte', 'wp-restaurant-menu') : ''}
                        />
                    </PanelBody>
                </InspectorControls>

                <div className="wpr-block-preview">
                    <ServerSideRender
                        block="wp-restaurant-menu/menu-display"
                        attributes={attributes}
                    />
                </div>
            </div>
        );
    },

    save: () => {
        // Server-side rendering
        return null;
    },
});
