<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Extension\ContentStabilization\StabilizationLookup;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MWException;
use Parser;
use ParserOutput;
use PPFrame;
use RequestContext;
use TemplateParser;
use WikiPage;

class BpmnTag implements ParserFirstCallInitHook {
	public const PROCESS_PROP_NAME = 'cpd-process';

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/** @var StabilizationLookup */
	private StabilizationLookup $lookup;

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param StabilizationLookup $lookup
	 */
	public function __construct( CpdDiagramPageUtil $diagramPageUtil, StabilizationLookup $lookup ) {
		$this->diagramPageUtil = $diagramPageUtil;
		$this->lookup = $lookup;
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
		$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );
		$file = $this->diagramPageUtil->getSvgFile(
			$process,
			$this->getStableRevision( $diagramPage )
		);

		return $templateParser->processTemplate(
			'CpdDiagramPreview', [
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

		$data = [
			'process' => $process,
			'showToolbar' => !( $args['toolbar'] === "false" ),
			'width' => $args['width'] ? $args['width'] . 'px' : '100%',
			'height' => $args['height'] ? $args['height'] . 'px' : '100%'
		];

		$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );
		$stableRevision = $this->getStableRevision( $diagramPage );
		if ( $stableRevision ) {
			$data['revision'] = $stableRevision->getId();
		}

		$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		$revision = $revisionLookup->getRevisionByPageId( $diagramPage->getId() );

		if ( $revision ) {
			$output->addTemplate(
				$revision->getPageAsLinkTarget(),
				$diagramPage->getId(),
				$revision->getId()
			);
		}

		return $templateParser->processTemplate( 'CpdContainer', $data );
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

	/**
	 * @param WikiPage $page
	 *
	 * @return RevisionRecord|null
	 */
	private function getStableRevision( WikiPage $page ): RevisionRecord|null {
		$lastStablePoint = $this->lookup->getLastStablePoint( $page );

		return $lastStablePoint?->getRevision();
	}
}
