<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\CpdNavigationConnection;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use CognitiveProcessDesigner\Util\CpdElementConnectionUtil;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Message\Message;
use MediaWiki\Output\Hook\OutputPageBeforeHTMLHook;
use MediaWiki\Output\OutputPage;

class ModifyDescriptionPage implements OutputPageBeforeHTMLHook {
	public const RETURN_TO_QUERY_PARAM = 'backTo';
	public const REVISION_QUERY_PARAM = 'rev';

	/** @var TemplateParser */
	private TemplateParser $templateParser;

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param CpdElementConnectionUtil $connectionUtil
	 */
	public function __construct(
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly CpdElementConnectionUtil $connectionUtil
	) {
		$this->templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);
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

		$connections = $this->connectionUtil->getConnections( $title, $revId ? (int)$revId : null );

		$text = $this->createNavigation(
				$connections['incoming'],
				$connections['outgoing'],
			) . $text;

		$out->addModuleStyles( 'ext.cpd.description.page' );
	}

	/**
	 * @param CpdNavigationConnection[] $incomingCon
	 * @param CpdNavigationConnection[] $outgoingCon
	 *
	 * @return string
	 */
	private function createNavigation(
		array $incomingCon,
		array $outgoingCon,
	): string {
		$incoming = $this->buildConnection( $incomingCon, 'incoming' );
		$outgoing = $this->buildConnection( $outgoingCon, 'outgoing' );

		return $this->templateParser->processTemplate(
			'DescriptionPageNavigation',
			[
				'incoming' => $incoming,
				'outgoing' => $outgoing,
				'incomingheading' => Message::newFromKey( 'cpd-description-navigation-incoming-label' )->text(),
				'outgoingheading' => Message::newFromKey( 'cpd-description-navigation-outgoing-label' )->text()
			]
		);
	}

	/**
	 * @param CpdNavigationConnection[] $connections
	 * @param string $direction
	 *
	 * @return array
	 */
	private function buildConnection( array $connections, string $direction ): array {
		$result = [];
		foreach ( $connections as $connection ) {
			$con = $connection->toArray();
			$item = [
				'link' => $con['link'],
				'text' => $con['text'],
				'class' => $direction . ' ' . $con['type'],
				'title' => $con['link']
			];
			if ( $con['isLaneChange'] ) {
				$item['class'] .= ' cpd-lane-change';
			}
			$result[] = $item;
		}

		return $result;
	}
}
