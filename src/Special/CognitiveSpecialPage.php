<?php

namespace CognitiveProcessDesigner\Special;

use SpecialPage;
use TemplateParser;

class CognitiveSpecialPage extends SpecialPage {

	/**
	 * @param string $par
	 * @return string|void
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$templateParser = new TemplateParser( __DIR__ . '/../../resources/templates' );
		$html = $templateParser->processTemplate(
			'CognitiveProcessDesign',
			$this->getTemplateMessages() + [ 'edit' => $this->isEditPage() ]
		);
		$this->getOutput()->addHTML( $html );
		$this->getOutput()->addModuleStyles( [
			'ext.cognitiveProcessDesignerEdit.styles'
		] );
	}

	/**
	 * @return string ID of the HTML element being added
	 */
	protected function getId() {
		return 'js-drop-zone';
	}

	/**
	 * @return array
	 */
	protected function getTemplateMessages() {
		return [
			'loading_diagram_msg' => $this->msg( 'cpd-loading-diagram' ),
			'err_display_diagram_msg' => $this->msg( 'cpd-err-display-diagram' ),
			'error_log' => $this->msg( 'cpd-err-details' ),
			'bpmn_header' => $this->msg( 'cpd-bpmn-diagram-header' ),
			'bpmn_id_input_placeholder' => $this->msg( 'cpd-enter-bpmn-id-placeholder' ),
			'load_bpmn_input_placeholder' => $this->msg( 'cpd-load-bpmn-from-wiki-placeholder' ),
			'create_bpmn_input_placeholder' => $this->msg( 'cpd-create-bpmn-placeholder' ),
			'bpmn_id_placeholder' => $this->msg( 'cpd-bpmn-id-placeholder' ),
			'overwrite_wiki_page_question' => $this->msg( 'cpd-overwrite-wiki-page-question' ),
			'yes' => $this->msg( 'cpd-yes' ),
			'no' => $this->msg( 'cpd-no' ),
			'create_new_bpmn' => $this->msg( 'cpd-create-new-bpmn' ),
			'open_bpmn_from_local' => $this->msg( 'cpd-open-bpmn-from-local-file' ),
			'import_warnings' => $this->msg( 'cpd-err-import-warning' ),
			'show_details' => $this->msg( 'cpd-show-details' ),
			'you_edited_diagram' => $this->msg( 'cpd-you-edited-diagram' ),
			'undo_last_change' => $this->msg( 'cpd-undo-last-change' ),
			'download_bpmn' => $this->msg( 'cpd-download-bpmn' ),
			'download_svg' => $this->msg( 'cpd-download-svg' ),
			'keyboard_shortcuts' => $this->msg( 'cpd-keyboard-shortcuts' ),
			'undo' => $this->msg( 'cpd-keyboard-shortcuts-undo' ),
			'redo' => $this->msg( 'cpd-keyboard-shortcuts-redo' ),
			'select_all' => $this->msg( 'cpd-keyboard-shortcuts-select-all' ),
			'vscroll' => $this->msg( 'cpd-keyboard-shortcuts-vscroll' ),
			'hscroll' => $this->msg( 'cpd-keyboard-shortcuts-hscroll' ),
			'direct_editing' => $this->msg( 'cpd-keyboard-shortcuts-direct-editing' ),
			'lasso' => $this->msg( 'cpd-keyboard-shortcuts-lasso' ),
			'space' => $this->msg( 'cpd-keyboard-shortcuts-space' ),
		];
	}

	/**
	 * @return bool
	 */
	protected function isEditPage() {
		return false;
	}
}
