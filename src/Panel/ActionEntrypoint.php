<?php

namespace CognitiveProcessDesigner\Panel;

use CognitiveProcessDesigner\Util\CpdProcessUtil;
use MediaWiki\Context\RequestContext;
use MediaWiki\Language\RawMessage;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\ActionLink;

class ActionEntrypoint extends ActionLink {
	/**
	 * @param CpdProcessUtil $processUtil
	 */
	public function __construct( private readonly CpdProcessUtil $processUtil ) {
		parent::__construct( [] );
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'n-cpd';
	}

	/**
	 * @inheritDoc
	 */
	public function getHref(): string {
		/** @var Title */
		$specialpage = SpecialPage::getTitleFor( 'ProcessOverview' );
		return $specialpage->getLocalURL();
	}

	/**
	 * @inheritDoc
	 */
	public function getPermissions(): array {
		return [ 'read' ];
	}

	/**
	 * @inheritDoc
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-cpd-mainlinks-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-cpd-mainlinks-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-cpd-mainlinks-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function showAction(): bool {
		$user = RequestContext::getMain()->getUser();
		if ( $this->processUtil->hasPermission( $user, 'edit' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getActionClass(): string {
		return 'cpd-create-new-process';
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'bi-bs-create-page';
	}

	/**
	 * @inheritDoc
	 */
	public function getActionAriaLabel(): Message {
		return Message::newFromKey( 'cpd-entrypoint-action-process-aria-label' );
	}

	/**
	 * @inheritDoc
	 */
	public function getActionTitle(): Message {
		return Message::newFromKey( 'cpd-entrypoint-action-process-title' );
	}

	/**
	 * @inheritDoc
	 */
	public function getActionLabel(): Message {
		return new RawMessage( '' );
	}

	/**
	 * @inheritDoc
	 */
	public function showActionLabel(): bool {
		return false;
	}
}
