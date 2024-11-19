<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use MWStake\MediaWiki\Component\DataStore\FieldType;

class Schema extends \MWStake\MediaWiki\Component\DataStore\Schema {
	public function __construct() {
		parent::__construct( [
			Record::PROCESS => [
				self::FILTERABLE => true,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			Record::TITLE => [
				self::FILTERABLE => false,
				self::SORTABLE => true,
				self::TYPE => FieldType::STRING
			],
			Record::TITLE_URL => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			],
			Record::PROCESS_URL => [
				self::FILTERABLE => false,
				self::SORTABLE => false,
				self::TYPE => FieldType::STRING
			]
		] );
	}
}
