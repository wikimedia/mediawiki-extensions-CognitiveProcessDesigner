<?php

namespace CognitiveProcessDesigner\Hook;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;
use WikiPage;

interface CognitiveProcessDesignerBeforeRenderHook {

	/**
	 * @param PageIdentity|null $forPage
	 * @param WikiPage $diagramPage
	 * @param RevisionRecord|null &$diagramRevision
	 * @return mixed
	 */
	public function onCognitiveProcessDesignerBeforeRender(
		?PageIdentity $forPage, WikiPage $diagramPage, ?RevisionRecord &$diagramRevision
	);
}
