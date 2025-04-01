/*!
 * VisualEditor UserInterface DiffElement class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */



ve.ui.DiffElement = function VeUiDiffElement( visualDiff, config ) {
	ve.ui.DiffElement.super.call( this, config );
	this.$document = $( '<div>' ).addClass( 've-ui-diffElement-document' );


	this.$element.append(  this.$content );

	mw.loader.using('ext.cpd.cpddiffer').then(() => {
		debugger
		const CpdBpmnDiffer = require('foobar');
		const differInstance = new CpdBpmnDiffer();
		differInstance.sayHello();
	});
};
OO.inheritClass( ve.ui.DiffElement, OO.ui.Element );
