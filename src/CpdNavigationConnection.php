<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\HookHandler\ModifyDescriptionPage;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Message\Message;
use MediaWiki\Title\Title;

class CpdNavigationConnection {

	/**
	 * @param string $text
	 * @param string $link
	 * @param string $title
	 * @param string $type
	 * @param bool $isLaneChange
	 */
	private function __construct(
		private readonly string $text,
		private readonly string $link,
		private readonly string $title,
		private readonly string $type,
		private readonly bool $isLaneChange,
	) {
	}

	/**
	 * @throws CpdInvalidNamespaceException
	 */
	public static function createFromTitle(
		Title $title,
		string $type,
		bool $isLaneChange = true,
		?int $revId = null
	): self {
		$text = self::createConnectionText( $title, $isLaneChange );
		$link = self::createUrl( $title, $revId );
		$type = self::mapTypeToCls( $type );
		$tooltip = self::createTooltip( $title );

		return new CpdNavigationConnection( $text, $link, $tooltip, $type, $isLaneChange );
	}

	/**
	 * Class names are derived by type by convention:
	 * - without bpmn:
	 * - all lowercase
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private static function mapTypeToCls( string $type ): string {
		return str_replace( 'bpmn:', '', strtolower( $type ) );
	}

	/**
	 * Include last lane in the connection text when it is a lane change
	 *
	 * @param Title $title
	 * @param bool $isLaneChange
	 *
	 * @return string
	 * @throws CpdInvalidNamespaceException
	 */
	private static function createConnectionText( Title $title, bool $isLaneChange ): string {
		$lanes = CpdDiagramPageUtil::getLanesFromTitle( $title );
		$lastLane = array_pop( $lanes );
		if ( !$lastLane || !$isLaneChange ) {
			return $title->getSubpageText();
		}

		return sprintf( '%s:<br/>%s', $lastLane, $title->getSubpageText() );
	}

	/**
	 * @param Title $title
	 * @param int|null $revId
	 *
	 * @return string
	 */
	private static function createUrl( Title $title, ?int $revId = null ): string {
		$queryParam = $revId ? ModifyDescriptionPage::REVISION_QUERY_PARAM . '=' . $revId : '';

		return $title->getFullURL( $queryParam );
	}

	/**
	 * @param Title $title
	 *
	 * @return string
	 */
	private static function createTooltip( Title $title ): string {
		return Message::newFromKey( 'cpd-description-navigation-tooltip-title', $title->getPrefixedDBkey() );
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		return [
			'text' => $this->text,
			'link' => $this->link,
			'title' => $this->title,
			'type' => $this->type,
			'isLaneChange' => $this->isLaneChange
		];
	}
}
