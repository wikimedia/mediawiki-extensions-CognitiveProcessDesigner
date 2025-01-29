( function ( mw, $ ) {
	const Vue = require( 'vue' );
	const App = require( './components/App.vue' );

	function render() {
		const deferred = $.Deferred();
		const dfdList = getStoreData();
		const h = Vue.h;

		dfdList.done( ( items ) => {
			const vm = Vue.createMwApp( {
				mounted: function () {
					deferred.resolve( this.$el );
				}, render: function () {
					return h( App, { items } );
				}
			} );

			vm.mount( '#bs-cpd-wrapper' );
			$( '#bs-cpd-wrapper' ).removeClass( 'loading' ); // eslint-disable-line no-jquery/no-global-selector
		} );

		return deferred;
	}

	function getStoreData() {
		const dfd = $.Deferred();

		mw.loader.using( 'mediawiki.api' ).done( () => {
			const api = new mw.Api();
			api.abort();
			api.get( { action: 'cpd-process-overview-store' } )
				.done( ( response ) => {
					dfd.resolve( JSON.parse( response.results ) );
				} ).fail( () => {
					console.error( 'loading processes failed' ); // eslint-disable-line no-console
					dfd.reject();
				} );
		} );

		return dfd.promise();
	}

	render();
}( mediaWiki, jQuery, document ) );
