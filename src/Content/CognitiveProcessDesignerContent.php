<?php

namespace CognitiveProcessDesigner\Content;

use Exception;
use MediaWiki\Content\TextContent;

class CognitiveProcessDesignerContent extends TextContent {

	public const MODEL = 'CPD';

	/**
	 * @param string $text
	 *
	 * @throws Exception
	 */
	public function __construct( $text ) {
		parent::__construct( $text, self::MODEL );
	}
}
