<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use MediaWiki\Diff\Hook\TextSlotDiffRendererTablePrefixHook;
use MediaWiki\Registration\ExtensionRegistry;
use OOUI\ButtonGroupWidget;
use OOUI\ButtonWidget;
use TextSlotDiffRenderer;

class BpmnDiffer implements DifferenceEngineViewHeaderHook, TextSlotDiffRendererTablePrefixHook {

	/**
	 * @param Config $config
	 */
	public function __construct( private readonly Config $config ) {
	}

	/**
	 * @inheritDoc
	 *
	 * @param $differenceEngine
	 *
	 * @return void
	 */
	public function onDifferenceEngineViewHeader( $differenceEngine ): void {
		if ( !$this->isBlueSpiceEnabled() ) {
			return;
		}

		$new = $differenceEngine->getNewRevision();

		try {
			$process = CpdDiagramPageUtil::getProcess( $new->getPage() );
		} catch ( CpdInvalidNamespaceException $e ) {
			// If the page is not a CPD diagram, do nothing
			return;
		}

		$out = $differenceEngine->getOutput();
		$out->addJsConfigVars( [
			'cpdProcess' => $process,
			'cpdDiffContainerHeight' => $this->config->get( 'CPDCanvasProcessHeight' ),
		] );
		$out->enableOOUI();
		$out->addModules( [ 'ext.cpd.bpmndiffer' ] );
	}

	/**
	 * Handler for the DifferenceEngineViewHeader hook, to add visual diffs code as configured
	 *
	 * @param TextSlotDiffRenderer $textSlotDiffRenderer
	 * @param IContextSource $context
	 * @param string[] &$parts
	 *
	 * @return void
	 */
	public function onTextSlotDiffRendererTablePrefix(
		TextSlotDiffRenderer $textSlotDiffRenderer,
		IContextSource $context,
		array &$parts
	): void {
		if ( !$this->isBlueSpiceEnabled() ) {
			return;
		}

		if ( $textSlotDiffRenderer->getContentModel() !== CognitiveProcessDesignerContent::MODEL ) {
			return;
		}

		$output = $context->getOutput();
		$parts['50_ve-init-mw-diffPage-diffMode'] = '<div class="ve-init-mw-diffPage-diffMode">' .
													// Will be replaced by a ButtonSelectWidget in JS
													new ButtonGroupWidget( [
														'items' => [
															new ButtonWidget( [
																'data' => 'visual',
																'icon' => 'eye',
																'disabled' => true,
																'label' => $output->msg(
																	'visualeditor-savedialog-review-visual'
																)->plain()
															] ),
															new ButtonWidget( [
																'data' => 'source',
																'icon' => 'wikiText',
																'active' => true,
																'label' => $output->msg(
																	'visualeditor-savedialog-review-wikitext'
																)->plain()
															] )
														]
													] ) .
													'</div>';
	}

	private function isBlueSpiceEnabled(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'BlueSpiceFoundation' );
	}
}
