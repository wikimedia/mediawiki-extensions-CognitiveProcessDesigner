<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use SkinTemplate;

class AddNewProcess implements SkinTemplateNavigation__UniversalHook {
	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		$title = $sktemplate->getTitle();

		$config = [
			'text' => $sktemplate->msg( 'bs-cpd-actionmenuentry-new-process' )->text(),
			'title' => $sktemplate->msg( 'bs-cpd-actionmenuentry-new-process' )->text(),
			'href' => '',
			'class' => '',
		];

		if ( $title->isSpecial( 'ProcessOverview' ) ) {
			$links['actions']['cpd-create-process'] = array_merge( [
				'position' => 1,
			], $config );
		}
		$links['actions']['cpd-create-new-process'] = $config;

		$sktemplate->getOutput()->addModules( 'ext.cpd.newprocessdialog' );
	}
}
