<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use SkinTemplate;

class AddActions implements SkinTemplateNavigation__UniversalHook {
	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$title = $sktemplate->getTitle();
		if ( $title->exists() && $title->getContentModel() === CognitiveProcessDesignerContent::MODEL ) {
			$this->addEditDiagramXmlAction( $sktemplate, $links );
		}
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 *
	 * @return void
	 */
	private function addEditDiagramXmlAction( SkinTemplate $sktemplate, array &$links ) {
		if ( !isset( $links['views']['edit'] ) ) {
			return;
		}
		$links['views']['edit']['text'] = $sktemplate->msg( 'cpd-ui-action-edit' )->plain();
		$links['views']['edit']['title'] = $sktemplate->msg( 'cpd-ui-action-edit' )->plain();
		$links['views']['editxml'] = $links['views']['edit'];
		$links['views']['editxml']['text'] = $sktemplate->msg( 'cpd-ui-action-edit-xml' )->plain();
		$links['views']['editxml']['title'] = $sktemplate->msg( 'cpd-ui-action-edit-xml' )->plain();
		$links['views']['editxml']['href'] = $sktemplate->getTitle()->getLinkURL( [ 'action' => 'editxml' ] );
	}
}
