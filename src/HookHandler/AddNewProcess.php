<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use SkinTemplate;

class AddNewProcess implements SkinTemplateNavigation__UniversalHook {
	/**
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$links['actions']['cpd-create-process'] = [
			'text' => $sktemplate->msg( 'bs-cpd-actionmenuentry-new-process' )->text(),
			'title' => $sktemplate->msg( 'bs-cpd-actionmenuentry-new-process' )->text(),
			'href' => '',
			'class' => 'new-process-action',
			'id' => 'ca-cpd-create-process'
		];
	}
}
