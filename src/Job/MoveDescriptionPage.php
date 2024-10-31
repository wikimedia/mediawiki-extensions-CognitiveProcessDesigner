<?php

namespace CognitiveProcessDesigner\Job;

use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use Job;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\MovePageFactory;
use MediaWiki\User\UserIdentity;
use Title;

class MoveDescriptionPage extends Job {

	public const JOBCOMMAND = 'cpdMoveDescriptionPage';

	/**
	 * @var MovePageFactory
	 */
	private MovePageFactory $movePageFactory;

	/**
	 * @var UserIdentity
	 */
	private UserIdentity $actor;

	/**
	 * @var Title
	 */
	private Title $existingTitle;

	/**
	 * @var Title
	 */
	private Title $newTitle;

	/**
	 * @param Title $existingTitle
	 * @param Title $newTitle
	 * @param UserIdentity $actor
	 */
	public function __construct(
		Title $existingTitle,
		Title $newTitle,
		UserIdentity $actor,
	) {
		parent::__construct( static::JOBCOMMAND, [] );
		$services = MediaWikiServices::getInstance();
		$this->movePageFactory = $services->getService( 'MovePageFactory' );
		$this->actor = $actor;
		$this->existingTitle = $existingTitle;
		$this->newTitle = $newTitle;
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
			'Cpd Element has changed',
			false
		);
	}
}
