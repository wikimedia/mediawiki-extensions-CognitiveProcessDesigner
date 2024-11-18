ext.cpd.special.OrphanedDescriptionPageGrid = function ( cfg ) {
	const store = new OOJSPlus.ui.data.store.RemoteStore( {
		action: 'cpd-orphaned-description-pages-store'
	} );
	ext.cpd.special.OrphanedDescriptionPageGrid.parent.call( this, {
		store
	} );
};

OO.inheritClass( ext.cpd.special.OrphanedDescriptionPageGrid, OOJSPlus.ui.data.GridWidget );
