<?php

namespace CognitiveProcessDesigner\Job;

use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use Job;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Page\MovePageFactory;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

class MoveDescriptionPage extends Job {

	public const JOBCOMMAND = 'cpdMoveDescriptionPage';

	/**
	 * @var MovePageFactory
	 */
	private MovePageFactory $movePageFactory;

	/**
	 * @param Title $existingTitle
	 * @param Title $newTitle
	 * @param UserIdentity $actor
	 */
	public function __construct(
		private readonly Title $existingTitle,
		private readonly Title $newTitle,
		private readonly UserIdentity $actor,
	) {
		parent::__construct( static::JOBCOMMAND, [] );
		$services = MediaWikiServices::getInstance();
		$this->movePageFactory = $services->getService( 'MovePageFactory' );
	}

	/**
	 * TODO: Update all incoming and outgoing links on move
	 *
	 * Move the description page if CpdElement has changed
	 *
	 * @throws CpdSaveException
	 */
	public function run() {
		if ( $this->existingTitle->equals( $this->newTitle ) ) {
			throw new CpdSaveException( 'Old and new titles are the same' );
		}

		$this->movePageFactory->newMovePage( $this->existingTitle, $this->newTitle )->move(
			$this->actor,
			Message::newFromKey( 'cpd-api-move-description-page-comment' )->escaped(),
			false
		);
	}
}
