<?php

namespace CognitiveProcessDesigner\Panel;

use Exception;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;

class MainLinkPanel extends RestrictedTextLink {

	/**
	 * @param string $id
	 */
	public function __construct( $id ) {
		parent::__construct( [ 'id' => $id ] );
	}

	/**
	 *
	 * @return array
	 */
	public function getPermissions(): array {
		return [ 'read' ];
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getHref(): string {
		$specialPage = SpecialPage::getTitleFor( 'ProcessOverview' );

		return $specialPage->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-cpd-mainlinks-label' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-cpd-mainlinks-label' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-cpd-mainlinks-label' );
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [];
	}
}
