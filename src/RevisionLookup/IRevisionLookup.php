<?php

namespace CognitiveProcessDesigner\RevisionLookup;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;

interface IRevisionLookup {

	public function getLastStableRevision( PageIdentity $page ): ?RevisionRecord;

	public function isStabilizationEnabled( PageIdentity $page ): bool;

	public function getRevisionByTitle( PageIdentity $page ): ?RevisionRecord;

	public function getRevisionById( int $revId ): ?RevisionRecord;
}
