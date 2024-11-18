<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use MWStake\MediaWiki\Component\DataStore\FieldType;

class Schema extends \MWStake\MediaWiki\Component\DataStore\Schema {
	public function __construct() {
		parent::__construct( [
			Record::PROCESS => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			Record::DB_KEY => [
				self::FILTERABLE => true,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			]
		] );
	}
}
