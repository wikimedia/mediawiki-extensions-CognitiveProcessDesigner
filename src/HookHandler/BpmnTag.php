<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;
use ParserOutput;
use PPFrame;
use RequestContext;
use TemplateParser;

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
			'bpmn',
			[
				$this,
				'renderTag'
			]
		);
		$parser->setHook(
			'bs:bpmn',
			[
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
		// Validate required parameters
		if ( !isset( $args['process'] ) ) {
			throw new CpdInvalidArgumentException( 'Missing required parameter "process"' );
		}

		if ( !isset( $args['height'] ) ) {
			throw new CpdInvalidArgumentException( 'Missing required parameter "height"' );
		}

		// Sanitize the process parameter as db key. Replace spaces with underscores.
		$process = str_replace( ' ', '_', $args['process'] );

		$templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);

		// Show svg image if the page is in edit mode
		if ( $this->isEdit() ) {
			return $this->buildEditOutput( $templateParser, $parser->getOutput(), $process, $args );
		}

		return $this->buildViewOutput( $templateParser, $parser->getOutput(), $process, $args );
	}

	/**
	 * Show placeholder image if the svg file is not found
	 *
	 * @param TemplateParser $templateParser
	 * @param ParserOutput $output
	 *
	 * @param string $process
	 * @param array $args
	 *
	 * @return string
	 */
	private function buildEditOutput(
		TemplateParser $templateParser,
		ParserOutput $output,
		string $process,
		array $args
	): string {
		$output->addModuleStyles( [ 'ext.cpd.diagram.preview' ] );
		$file = $this->diagramPageUtil->getSvgFile( $process );

		return $templateParser->processTemplate(
			'CpdDiagramPreview',
			[
				'process' => $process,
				'img' => $file?->getFullUrl(),
				'width' => $args['width'] ? $args['width'] . 'px' : '100%',
				'height' => $args['height'] ? $args['height'] . 'px' : '100%'
			]
		);
	}

	/**
	 * @param TemplateParser $templateParser
	 * @param ParserOutput $output
	 * @param string $process
	 * @param array $args
	 *
	 * @return string
	 */
	private function buildViewOutput(
		TemplateParser $templateParser,
		ParserOutput $output,
		string $process,
		array $args
	): string {
		$this->addProcessPageProperty( $output, $process );
		$this->diagramPageUtil->setJsConfigVars( $output, $process );
		$output->addModules( [ 'ext.cpd.viewer' ] );

		return $templateParser->processTemplate(
			'CpdContainer',
			[
				'process' => $process,
				'showToolbar' => !( $args['toolbar'] === "false" ),
				'width' => $args['width'] ? $args['width'] . 'px' : '100%',
				'height' => $args['height'] ? $args['height'] . 'px' : '100%'
			]
		);
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

	/**
	 * Check if the current action is a ve edit
	 *
	 * @return bool
	 */
	private function isEdit(): bool {
		$request = RequestContext::getMain()->getRequest();
		$action = $request->getVal( 'action', $request->getVal( 'veaction', null ) );

		return $action === 'edit' || $action === 'visualeditor';
	}
}
