<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Panel\MainLinkPanel;
use MediaWiki\Config\Config;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/** @var Config */
	private Config $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		if ( $this->config->get( 'CPDMainLinksCognitiveProcessDesigner' ) ) {
			$registry->register(
				'MainLinksPanel', [
					'bs-special-cpd' => [
						'factory' => static function () {
							return new MainLinkPanel();
						},
						'position' => 40
					]
				]
			);
		}

		$registry->register(
			'GlobalActionsOverview', [
				'bs-special-cpd' => [
					'factory' => static function () {
						return new MainLinkPanel();
					}
				]
			]
		);
	}
}
