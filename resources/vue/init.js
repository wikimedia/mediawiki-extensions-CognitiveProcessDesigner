( function ( mw, $ ) {
	const Vue = require( 'vue' );
	const App = require( './components/App.vue' );

	function render() {
		const deferred = $.Deferred();
		const dfdList = getStoreData();
		const h = Vue.h;

		dfdList.done( function ( items ) {
			const vm = Vue.createMwApp( {
				mounted: function () {
					deferred.resolve( this.$el );
				}, render: function () {
					return h( App, { items } );
				}
			} );

			vm.mount( '#bs-cpd-wrapper' );
			$( '#bs-cpd-wrapper' ).removeClass( 'loading' );
		} );

		return deferred;
	}

	function getStoreData() {
		const dfd = $.Deferred();

		mw.loader.using( 'mediawiki.api' ).done( function () {
			const api = new mw.Api();
			api.abort();
			api.get( {action: "cpd-process-overview-store"} )
				.done( function ( response ) {
					dfd.resolve( JSON.parse( response.results ) );
				} ).fail( function () {
				console.error( 'loading processes failed' );
				dfd.reject();
			} );
		} );

		return dfd.promise();
	}

	render();
}( mediaWiki, jQuery, document ) );
