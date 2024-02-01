( function( mw ) {

	mw.cpdSemanticForms = {

		entries: [],

		formsNamespace: 106,

		currentElement: null,

		init: function() {
			mw.cpdSemanticForms.entries = [];
			mw.cpdSemanticForms.currentElement = null;
			this.loadSemanticForms( '' );
		},

		loadSemanticForms: function( pageToken ) {
			new mw.Api().get( {
				action: 'query',
				list: 'allpages',
				apnamespace: mw.cpdSemanticForms.formsNamespace,
				format: 'json',
				apcontinue: pageToken
			} ).done( function( data ) {
				if (data && data.error) {
					console.warn(data);
				} else {
					if ( pageToken === '' ) {
						mw.cpdSemanticForms.entries = [];
					}
					var dataArray = data.query.allpages;
					for (var i = 0; i < dataArray.length; i++) {
						var prefixedTitle = dataArray[i].title;
						var labelParts = prefixedTitle.split( ':' );
						labelParts.shift(); // Remove namespace
						var labelEntry = labelParts.join( ':' );
						var dataEntry = {
							label: labelEntry,
							id: dataArray[i].pageid,
							className: 'Form',
							target: {
								type: 'Form1'
							},
							action: {
								call: function(x, event, element) {
									var formPage = element.label.replace(' ', '_');
									var pagePath = mw.cpdManager.bpmnPagePath + '/' +
										mw.cpdSemanticForms.currentElement.id;
									window.ext.popupform.handlePopupFormLink(
										mw.config.get( 'wgScriptPath') +
										'/index.php?title=Special:FormEdit/' +
										formPage +
										'/' + pagePath,
										$( '#formIframe' )
									);
								}
							}
						};
						mw.cpdSemanticForms.entries.push( dataEntry );
					}
					if ( data && data.continue && data.continue.apcontinue ) {
						mw.cpdSemanticForms.loadSemanticForms( data.continue.apcontinue );
					}
				}
			} );
		}
	};

}( mw ) );