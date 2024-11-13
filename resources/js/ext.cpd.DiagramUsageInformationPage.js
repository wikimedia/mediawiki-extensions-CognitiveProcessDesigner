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
					this.$diagramUsageHtml.text( mw.message( 'cpd-diagram-no-process-description' ).plain() + "." );
					return;
				}

				if ( data.links.length === 0 ) {
					this.$diagramUsageHtml.text( mw.message( 'cpd-diagram-no-usage-description' ).plain() + "." );
					return;
				}

				const $description = $( '<p>' );
				$description.text( mw.message( 'cpd-diagram-usage-description' ).plain() + ":" );
				this.$diagramUsageHtml.append( $description );

				const $linkList = $( '<ul>' );
				this.$diagramUsageHtml.append( $linkList );


				data.links.forEach( ( link ) => $linkList.append( $( '<li>' ).append( link ) ) );
			} );

			this.$element.append( this.$diagramUsageHtml );
		}
	};

	registryPageInformation.register( 'diagram_usage_infos', bs.cpd.info.DiagramUsageInformationPage ); // eslint-disable-line no-undef

} )( mediaWiki, blueSpice );
