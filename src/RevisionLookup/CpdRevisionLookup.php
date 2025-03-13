<?php

namespace CognitiveProcessDesigner\RevisionLookup;

use MediaWiki\Extension\ContentStabilization\StabilizationLookup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;

class CpdRevisionLookup implements IRevisionLookup {

	private StabilizationLookup|null $stabilizationLookup = null;
	private RevisionLookup $revisionLookup;

	public function __construct( MediaWikiServices $services ) {
		if ( $services->hasService( 'ContentStabilization.Lookup' ) ) {
			$this->stabilizationLookup = $services->getService( 'ContentStabilization.Lookup' );
		}

		$this->revisionLookup = $services->getRevisionLookup();
	}

	/**
	 * @param PageIdentity $page
	 *
	 * @return RevisionRecord|null
	 */
	public function getLastStableRevision( PageIdentity $page ): ?RevisionRecord {
		if ( !$this->stabilizationLookup ) {
			return $this->revisionLookup->getRevisionByTitle( $page );
		}

		return $this->stabilizationLookup->getLastStablePoint( $page )?->getRevision();
	}

	/**
	 * @param PageIdentity $page
	 *
	 * @return bool
	 */
	public function isStabilizationEnabled( PageIdentity $page ): bool {
		if ( !$this->stabilizationLookup ) {
			return false;
		}

		return $this->stabilizationLookup->isStabilizationEnabled( $page );
	}

	/**
	 * @param PageIdentity $page
	 *
	 * @return RevisionRecord|null
	 */
	public function getRevisionByTitle( PageIdentity $page ): ?RevisionRecord {
		return $this->revisionLookup->getRevisionByTitle( $page );
	}

	/**
	 * @param int $revId
	 *
	 * @return RevisionRecord|null
	 */
	public function getRevisionById( int $revId ): ?RevisionRecord {
		return $this->revisionLookup->getRevisionById( $revId );
	}
}
