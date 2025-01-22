<?php

namespace CognitiveProcessDesigner\Job;

use CognitiveProcessDesigner\Exceptions\CpdSaveException;
use CommentStoreComment;
use Content;
use Job;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MWException;

class SaveDescriptionPage extends Job {

	public const JOBCOMMAND = 'cpdSaveDescriptionPage';

	/**
	 * @var PageUpdater
	 */
	private PageUpdater $updater;

	/**
	 * @var Content|null
	 */
	private ?Content $content;

	/**
	 * @param Title $title
	 * @param Content|null $content
	 * @param UserIdentity $actor
	 */
	public function __construct(
		Title $title,
		?Content $content,
		UserIdentity $actor,
	) {
		parent::__construct( static::JOBCOMMAND, [] );

		$services = MediaWikiServices::getInstance();
		$wikiPageFactory = $services->getService( 'WikiPageFactory' );
		$descriptionPage = $wikiPageFactory->newFromTitle( $title );

		$this->updater = $descriptionPage->newPageUpdater( $actor );
		$this->content = $content;
	}

	/**
	 * @return void
	 * @throws CpdSaveException
	 */
	public function run() {
		$this->updater->setContent( SlotRecord::MAIN, $this->content );

		$comment = Message::newFromKey( 'cpd-api-save-description-page-comment' );
		$commentStore = CommentStoreComment::newUnsavedComment( $comment );

		try {
			$result = $this->updater->saveRevision( $commentStore, EDIT_NEW );
		} catch ( MWException $e ) {
			throw new CpdSaveException( $e->getMessage() );
		}
		if ( !$this->updater->wasSuccessful() ) {
			throw new CpdSaveException( $this->updater->getStatus()->getMessage() );
		}
		if ( $result === null ) {
			throw new CpdSaveException( "Failed to save description page {$this->updater->getPage()->getDBkey()}" );
		}
	}
}
