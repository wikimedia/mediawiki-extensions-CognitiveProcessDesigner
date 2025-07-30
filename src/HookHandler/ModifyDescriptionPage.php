<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use MediaWiki\Output\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Output\OutputPage;

class ModifyDescriptionPage implements OutputPageBeforeHTMLHook {
	public const RETURN_TO_QUERY_PARAM = 'backTo';
	public const REVISION_QUERY_PARAM = 'rev';

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param CpdElementConnectionUtil $connectionUtil
	 */
	public function __construct(
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly CpdElementConnectionUtil $connectionUtil
	) {
	}

	/**
	 * @param OutputPage $out
	 * @param string &$text
	 *
	 * @return void
	 * @throws CpdInvalidContentException
	 * @throws CpdInvalidNamespaceException
	 * @throws CpdXmlProcessingException
	 * @throws CpdCreateElementException
	 * @throws CpdInvalidArgumentException
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$title = $out->getTitle();
		if ( !$title ) {
			return;
		}

		if ( !$title->exists() ) {
			return;
		}

		if ( !$this->descriptionPageUtil->isDescriptionPage( $title ) ) {
			return;
		}

		$revId = $out->getRequest()->getVal( self::REVISION_QUERY_PARAM );

		$navigationHtml = $this->connectionUtil->createNavigationHtml( $title, $revId ? (int)$revId : null );
		$text = $navigationHtml . $text;

		$out->addModuleStyles( 'ext.cpd.description.page' );
	}
}
