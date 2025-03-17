window.ext = window.ext || {};
window.ext.cpd = window.ext.cpd || {};
window.ext.cpd.special = window.ext.cpd.special || {};

( ( mw, $ ) => {
	ext.cpd.special.OrphanedDescriptionPageGrid = function () {
		const store = new OOJSPlus.ui.data.store.RemoteStore( {
			action: 'cpd-orphaned-description-pages-store'
		} );

		ext.cpd.special.OrphanedDescriptionPageGrid.parent.call( this, {
			store,
			columns: {
				title: {
					headerText: mw.message( 'cpd-orphaned-description-pages-column-dbkey-title' ).text(),
					sortable: true,
					type: 'url',
					urlProperty: 'title_url'
				},
				process: {
					headerText: mw.message( 'cpd-orphaned-description-pages-column-process-title' ).text(),
					filterable: true,
					type: 'url',
					urlProperty: 'process_url',
					filter: {
						type: 'text'
					}
				}
			}
		} );
	};

	OO.inheritClass( ext.cpd.special.OrphanedDescriptionPageGrid, OOJSPlus.ui.data.GridWidget );

	const grid = new ext.cpd.special.OrphanedDescriptionPageGrid();
	$( '#cpd-special-orphaned-pages' ).html( grid.$element ); // eslint-disable-line no-jquery/no-global-selector
} )( mediaWiki, $ );
