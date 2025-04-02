<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use Exception;
use File;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Html\TemplateParser;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Revision\RevisionRecord;
use WikiPage;

class BpmnTag implements ParserFirstCallInitHook {
	public const PROCESS_PROP_NAME = 'cpd-process';

	/**
	 * @param CpdDiagramPageUtil $diagramPageUtil
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		private readonly CpdDiagramPageUtil $diagramPageUtil,
		private readonly HookContainer $hookContainer
	) {
	}

	/**
	 * @param Parser $parser
	 *
	 * @throws Exception
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook(
			'bpmn',
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
		if ( empty( $args['process'] ) && empty( $args['name'] ) ) {
			return Message::newFromKey( "cpd-error-message-missing-parameter-process" )->escaped();
		}

		// Sanitize the process parameter as db key. Replace spaces with underscores.
		$process = str_replace( ' ', '_', $args['process'] ?? $args['name'] );

		$templateParser = new TemplateParser(
			dirname( __DIR__, 2 ) . '/resources/templates'
		);

		try {
			$diagramPage = $this->diagramPageUtil->getDiagramPage( $process );
		} catch ( CpdInvalidArgumentException $e ) {
			return Message::newFromKey( "cpd-error-message-missing-diagram-page", $process )->escaped();
		}

		$diagramRevision = $diagramPage->getRevisionRecord();
		$this->hookContainer->run(
			'CognitiveProcessDesignerBeforeRender',
			[
				$parser->getPage(),
				$diagramPage,
				&$diagramRevision
			]
		);

		$revId = null;
		if ( $diagramRevision ) {
			if ( !$diagramRevision->isCurrent() ) {
				$revId = $diagramRevision->getId();
			}
		}

		$imageFile = $this->diagramPageUtil->getSvgFile( $process, $revId );

		// Show svg image if the page is in edit mode
		if ( $this->isEdit() ) {
			return $this->buildEditOutput( $imageFile, $templateParser, $parser->getOutput(), $process, $args );
		}

		return $this->buildViewOutput(
			$imageFile,
			$templateParser,
			$parser,
			$process,
			$args,
			$diagramPage,
			$diagramRevision
		);
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
			'CpdDiagramPreview',
			[
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
	 * @param WikiPage $diagramPage
	 * @param RevisionRecord|null $diagramRevision
	 *
	 * @return string
	 */
	private function buildViewOutput(
		?File $imageFile,
		TemplateParser $templateParser,
		Parser $parser,
		string $process,
		array $args,
		WikiPage $diagramPage,
		?RevisionRecord $diagramRevision
	): string {
		$output = $parser->getOutput();
		$this->addProcessPageProperty( $output, $process );
		$this->diagramPageUtil->setJsConfigVars( $output, $process );
		$output->addModules( [ 'ext.cpd.viewer' ] );

		// Embed svg image in the viewer hidden
		$imageDbKey = $imageFile?->getTitle()->getPrefixedDBkey();

		$data = [
			'process' => $process,
			'showToolbar' => !empty( $args['toolbar'] ) ? !( $args['toolbar'] === "false" ) : null,
			'width' => !empty( $args['width'] ) ? $args['width'] . 'px' : '100%',
			'height' => !empty( $args['height'] ) ? $args['height'] . 'px' : '100%',
			'diagramImage' => $imageDbKey ? $parser->recursiveTagParse( "[[$imageDbKey]]" ) : null
		];

		if ( $diagramRevision instanceof RevisionRecord ) {
			$data['revision'] = $diagramRevision->getId();
			$output->addTemplate(
				$diagramPage->getTitle(),
				$diagramPage->getId(),
				$diagramRevision->getId()
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
		$action = $request->getVal( 'action', $request->getVal( 'veaction' ) );

		return $action === 'edit' || $action === 'visualeditor';
	}
}
