( ( mw, bs ) => {
	bs.util.registerNamespace( 'bs.cpd.info' );

	bs.cpd.info.DiagramUsageInformationPage = function DiagramUsageInformationPage( name, config ) {
		bs.cpd.info.DiagramUsageInformationPage.super.call( this, name, config );
	};

	OO.inheritClass( bs.cpd.info.DiagramUsageInformationPage, StandardDialogs.ui.BasePage ); // eslint-disable-line no-undef

	bs.cpd.info.DiagramUsageInformationPage.prototype.setupOutlineItem = function () {
		bs.cpd.info.DiagramUsageInformationPage.super.prototype.setupOutlineItem.apply( this, arguments );

		if ( this.outlineItem ) {
			this.outlineItem.setLabel( mw.message( 'bs-cpd-info-dialog' ).plain() );
		}
	};

	bs.cpd.info.DiagramUsageInformationPage.prototype.setup = function () {
		this.$diagramUsageHtml = null;
		return;
	};

	bs.cpd.info.DiagramUsageInformationPage.prototype.onInfoPanelSelect = async function () {
		if ( !this.$diagramUsageHtml ) {
			this.$diagramUsageHtml = $( '<div>' );
			const api = new mw.Api();
			api.get( {
				action: 'cpd-diagram-usage',
				page: this.pageName
			} ).done( ( data ) => {
				if ( !data.links ) {
					this.$diagramUsageHtml.text( mw.message( 'cpd-process-usage-undocumented-error' ).plain() + "." );
					return;
				}

				Object.entries( data.links ).forEach( ( data ) => {
					const [process, processLinks] = data;
					this.addDiagramUsageLinkList( process, processLinks );
				} );
			} ).fail( ( errorType, data ) => {
				if ( data.error === 'isSpecial' ) {
					this.$diagramUsageHtml.text( mw.message( 'cpd-process-usage-special-page-description' ).plain() + "." );
					return;
				}

				if ( data.error === 'noProcess' ) {
					this.$diagramUsageHtml.text( mw.message( 'cpd-process-usage-not-embedded-description' ).plain() + "." );
					return;
				}

				this.$diagramUsageHtml.text( mw.message( 'cpd-process-usage-undocumented-error' ).plain() + "." );
			} );

			this.$element.append( this.$diagramUsageHtml );
		}
	};

	bs.cpd.info.DiagramUsageInformationPage.prototype.addDiagramUsageLinkList = function ( process, links ) {
		if ( !this.$diagramUsageHtml ) {
			return;
		}

		const $container = $( '<div>' );
		this.$diagramUsageHtml.append( $container );

		const $description = $( '<p>' );
		$container.append( $description );

		if ( links.length === 0 ) {
			$description.text( mw.message( 'cpd-process-usage-no-pages-description', process ).plain() + "." );
			return;
		}

		$description.text( mw.message( 'cpd-process-usage-description', process ).plain() + ":" );

		const $linkList = $( '<ul>' );
		$container.append( $linkList );

		links.forEach( ( link ) => $linkList.append( $( '<li>' ).append( link ) ) );
	};

	registryPageInformation.register( 'diagram_usage_infos', bs.cpd.info.DiagramUsageInformationPage ); // eslint-disable-line no-undef

} )( mediaWiki, blueSpice );
