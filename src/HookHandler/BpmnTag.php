<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;
use ParserOutput;
use PPFrame;

class BpmnTag implements ParserFirstCallInitHook {
	public const PROCESS_PROP_NAME = 'cpd-process';

	/**
	 * @var CpdDiagramPageUtil
	 */
	private CpdDiagramPageUtil $diagramPageUtil;

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 */
	public function __construct( CpdDiagramPageUtil $diagramPageUtil ) {
		$this->diagramPageUtil = $diagramPageUtil;
	}

	/**
	 * @param Parser $parser
	 *
	 * @throws MWException
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook(
			'bpmn', [
				$this,
				'renderTag'
			]
		);
		$parser->setHook(
			'bs:bpmn', [
				$this,
				'renderTag'
			]
		);
	}

	/**
	 * @param string|null $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return string
	 * @throws CpdInvalidArgumentException
	 */
	public function renderTag(
		?string $input,
		array $args,
		Parser $parser,
		PPFrame $frame
	): string {
		if ( !isset( $args['process'] ) ) {
			throw new CpdInvalidArgumentException( 'Missing required parameter "process"' );
		}

		// Sanitize the process parameter as db key. Replace spaces with underscores.
		$process = str_replace( ' ', '_', $args['process'] );

		$output = $parser->getOutput();
		$this->addProcessPageProperty( $output, $process );
		$this->diagramPageUtil->setJsConfigVars( $output, $process );
		$output->addModules( [ 'ext.cpd.viewer' ] );

		return '';
	}

	/**
	 * @param ParserOutput $output
	 * @param string $process
	 *
	 * @return void
	 */
	private function addProcessPageProperty( ParserOutput $output, string $process ): void {
		$processes = $output->getPageProperty( self::PROCESS_PROP_NAME );

		if ( $processes ) {
			$processes[] = $process;
		} else {
			$processes = [ $process ];
		}

		$output->setPageProperty( self::PROCESS_PROP_NAME, $processes );
	}
}
