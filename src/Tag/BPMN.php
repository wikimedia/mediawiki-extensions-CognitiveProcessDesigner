<?php
namespace CognitiveProcessDesigner\Tag;

use BlueSpice\Tag\Tag;

class BPMN extends Tag {

	/**
	 * @return bool
	 */
	public function needsDisabledParserCache() {
		return true;
	}

	/**
	 * @param mixed $processedInput
	 * @param array $processedArgs
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 * @return \BlueSpice\Tag\IHandler|BPMNHandler
	 */
	public function getHandler( $processedInput, array $processedArgs,
		\Parser $parser, \PPFrame $frame
	) {
		return new BPMNHandler(
			$processedInput,
			$processedArgs,
			$parser,
			$frame
		);
	}

	/**
	 * @return string[]
	 */
	public function getTagNames() {
		return [
			'bpmn',
			'bs:bpmn'
		];
	}
}
