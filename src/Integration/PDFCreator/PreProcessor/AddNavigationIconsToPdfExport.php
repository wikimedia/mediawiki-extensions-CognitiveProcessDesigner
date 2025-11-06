<?php

namespace CognitiveProcessDesigner\Integration\PDFCreator\PreProcessor;

use CognitiveProcessDesigner\Util\CpdDescriptionPageUtil;
use MediaWiki\Extension\PDFCreator\IPreProcessor;
use MediaWiki\Extension\PDFCreator\Utility\ExportContext;
use MediaWiki\Title\TitleFactory;

class AddNavigationIconsToPdfExport implements IPreProcessor {

	/**
	 * @param CpdDescriptionPageUtil $descriptionPageUtil
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly CpdDescriptionPageUtil $descriptionPageUtil,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function execute(
		array &$pages,
		array &$images,
		array &$attachments,
		ExportContext $context,
		string $module = '',
		$params = []
	): void {
		$isDescriptionPageIncluded = false;
		foreach ( $pages as $page ) {
			if ( $isDescriptionPageIncluded ) {
				break;
			}

			$title = $this->titleFactory->newFromDBkey( $page->getPrefixedDBKey() );

			if ( !$title ) {
				continue;
			}

			$isDescriptionPageIncluded = $this->descriptionPageUtil->isDescriptionPage( $title );
		}

		if ( !$isDescriptionPageIncluded ) {
			return;
		}

		self::addIcons( $images );
	}

	/**
	 * @param array &$images
	 *
	 * @return void
	 */
	public static function addIcons( array &$images ): void {
		$imagesPath = dirname( __DIR__, 4 ) . '/resources/img';
		$imageNames = [
			'start-incoming.png',
			'end-outgoing.png',
			'task-incoming.png',
			'task-outgoing.png'
		];

		foreach ( $imageNames as $name ) {
			$images[$name] = "$imagesPath/$name";
		}
	}
}
