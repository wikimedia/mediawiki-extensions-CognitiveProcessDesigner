<?php
namespace CognitiveProcessDesigner\Hook\ParserFirstCallInit;

use CognitiveProcessDesigner\Tag\BPMNHandler;
use Parser;
use PPFrame;

class RenderBPMNTag {

	/**
	 * @param Parser $parser
	 */
	public static function callback( Parser $parser ) {
		$parser->setHook( 'bpmn', [ self::class, 'renderTag' ] );
		$parser->setHook( 'bs:bpmn', [ self::class, 'renderTag' ] );
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	public static function renderTag(
		$input, array $args, Parser $parser, PPFrame $frame
	) {
		$parser->getOutput()->updateCacheExpiry( 0 );
		$bpmnHandler = new BPMNHandler( $input, $args, $parser, $frame );
		return $bpmnHandler->handle();
	}
}
