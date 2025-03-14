<?php

namespace CognitiveProcessDesigner\RevisionLookup;

use MediaWiki\Linker\LinkTarget;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;

interface IRevisionLookup {

	public function getLastStableRevision( PageIdentity $page ): ?RevisionRecord;

	public function isStabilizationEnabled( PageIdentity $page ): bool;

	public function getRevisionByTitle( PageIdentity $page ): ?RevisionRecord;

	public function getRevisionById( int $revId ): ?RevisionRecord;

	public function getFirstRevision( LinkTarget|PageIdentity $page ): ?RevisionRecord;
}
