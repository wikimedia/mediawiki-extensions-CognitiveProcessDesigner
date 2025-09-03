<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Panel\MainLinkPanel;
use CognitiveProcessDesigner\Util\CpdProcessUtil;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @param Config $config
	 */
	public function __construct( private readonly Config $config, private readonly CpdProcessUtil $processUtil ) {
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$context = RequestContext::getMain();
		if ( !$this->processUtil->hasPermission( $context->getUser() ) ) {
			return;
		}

		if ( $this->config->get( 'CPDMainLinksCognitiveProcessDesigner' ) ) {
			$registry->register(
				'MainLinksPanel',
				[
					'bs-special-cpd' => [
						'factory' => static function () {
							return new MainLinkPanel( 'n-cpd' );
						},
						'position' => 40
					]
				]
			);
		}

		$registry->register(
			'GlobalActionsOverview',
			[
				'bs-special-cpd' => [
					'factory' => static function () {
						return new MainLinkPanel( 'ga-cpd-overview' );
					}
				]
			]
		);
	}
}
