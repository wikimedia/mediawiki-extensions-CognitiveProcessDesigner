<?php

namespace CognitiveProcessDesigner\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Registration\ExtensionRegistry;
use SkinTemplate;

class AddNewProcess implements SkinTemplateNavigation__UniversalHook {
	/**
	 * // phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'StandardDialogs' ) ) {
			return;
		}

		$title = $sktemplate->getTitle();

		$config = [
			'href' => '',
			'class' => 'cpd-create-new-process',
		];

		if ( $title->isSpecial( 'ProcessOverview' ) ) {
			$links['actions']['cpd-create-new-process'] = array_merge( [
				'position' => 1,
				'text' => $sktemplate->msg( 'bs-cpd-actionmenuentry-create-new-process' )->text(),
				'title' => $sktemplate->msg( 'bs-cpd-actionmenuentry-create-new-process' )->text(),
			], $config );
		}

		$links['actions']['cpd-create-process'] = array_merge( [
			'text' => $sktemplate->msg( 'bs-cpd-actionmenuentry-new-process' )->text(),
			'title' => $sktemplate->msg( 'bs-cpd-actionmenuentry-new-process' )->text(),
		], $config );;

		$sktemplate->getOutput()->addModules( 'ext.cpd.newprocessdialog' );
	}
}
