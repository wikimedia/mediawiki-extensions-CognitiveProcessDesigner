<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use File;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\PPFrame;
use MWException;

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

		$imageFile = $this->diagramPageUtil->getSvgFile( $process );

		// Show svg image if the page is in edit mode
		if ( $this->isEdit() ) {
			return $this->buildEditOutput( $imageFile, $templateParser, $parser->getOutput(), $process, $args );
		}

		return $this->buildViewOutput( $imageFile, $templateParser, $parser, $process, $args );
	}

	/**
	 * Show placeholder image if the svg file is not found
	 *
	 * @param File|null $imageFile
	 * @param TemplateParser $templateParser
	 * @param ParserOutput $output
	 *
	 * @param string $process
	 * @param array $args
	 *
	 * @return string
	 */
	private function buildEditOutput(
		?File $imageFile,
		TemplateParser $templateParser,
		ParserOutput $output,
		string $process,
		array $args
	): string {
		$output->addModuleStyles( [ 'ext.cpd.diagram.preview' ] );

		return $templateParser->processTemplate(
			'CpdDiagramPreview', [
				'process' => $process,
				'img' => $imageFile?->getFullUrl(),
				'width' => !empty( $args['width'] ) ? $args['width'] . 'px' : '100%',
				'height' => !empty( $args['height'] ) ? $args['height'] . 'px' : '100%'
			]
		);
	}

	/**
	 * @param File|null $imageFile
	 * @param TemplateParser $templateParser
	 * @param Parser $parser
	 * @param string $process
	 * @param array $args
	 *
	 * @return string
	 */
	private function buildViewOutput(
		?File $imageFile,
		TemplateParser $templateParser,
		Parser $parser,
		string $process,
		array $args
	): string {
		$output = $parser->getOutput();
		$this->addProcessPageProperty( $output, $process );
		$this->diagramPageUtil->setJsConfigVars( $output, $process );
		$output->addModules( [ 'ext.cpd.viewer' ] );

		// Embed svg image in the viewer hidden
		$imageDbKey = $imageFile?->getTitle()->getPrefixedDBkey();

		return $templateParser->processTemplate(
			'CpdContainer', [
				'process' => $process,
				'showToolbar' => !empty( $args['toolbar'] ) ? !( $args['toolbar'] === "false" ) : true,
				'width' => !empty( $args['width'] ) ? $args['width'] . 'px' : '100%',
				'height' => !empty( $args['height'] ) ? $args['height'] . 'px' : '100%',
				'diagramImage' => $imageDbKey ? $parser->recursiveTagParse( "[[$imageDbKey]]" ) : null
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
		$processes = unserialize( $output->getPageProperty( self::PROCESS_PROP_NAME ) );

		if ( $processes ) {
			$processes[] = $process;
		} else {
			$processes = [ $process ];
		}

		$output->setPageProperty( self::PROCESS_PROP_NAME, serialize( $processes ) );
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
