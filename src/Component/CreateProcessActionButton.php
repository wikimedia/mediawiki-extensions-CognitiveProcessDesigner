<?php

namespace CognitiveProcessDesigner\Component;

use MediaWiki\Message\Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleLink;

class CreateProcessActionButton extends SimpleLink {

	public function __construct() {
		return parent::__construct( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'create-process-btn';
	}

	/**
	 * @inheritDoc
	 */
	public function getSubComponents(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getClasses(): array {
		return [ 'cpd-create-new-process', 'ico-btn', 'bi-bs-create-page' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getRole(): string {
		return 'button';
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-cpd-actionmenuentry-create-new-process' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-cpd-actionmenuentry-create-new-process' );
	}

	/**
	 * @inheritDoc
	 */
	public function getHref(): string {
		return '';
	}
}
