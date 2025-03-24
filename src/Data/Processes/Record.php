<?php

namespace CognitiveProcessDesigner\Data\Processes;

class Record extends \MWStake\MediaWiki\Component\DataStore\Record {
	public const PROCESS = 'process';
	public const DB_KEY = 'db_key';
	public const URL = 'url';
	public const EDIT_URL = 'edit_url';
	public const IS_NEW = 'is_new';
	public const IMAGE_URL = 'image_url';
}
