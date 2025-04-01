<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use MediaWiki\Diff\Hook\TextSlotDiffRendererTablePrefixHook;
use MediaWiki\Html\Html;
use OOUI\ButtonGroupWidget;
use OOUI\ButtonWidget;
use TextSlotDiffRenderer;

class BpmnDiffer implements DifferenceEngineViewHeaderHook, TextSlotDiffRendererTablePrefixHook {

	private const CPD_DIFF_CONTAINER_ID = 'cpd-diff-container';
	private const CPD_DIFF_BUTTON_CONTAINER_ID = 'cpd-diff-button-container';

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
	public function onDifferenceEngineViewHeader( $differenceEngine ) {
		$new = $differenceEngine->getNewRevision();
		$old = $differenceEngine->getOldRevision();

		try {
			$process = CpdDiagramPageUtil::getProcess( $new->getPage() );
		} catch ( CpdInvalidNamespaceException $e ) {
			// If the page is not a CPD diagram, do nothing
			return;
		}

		$diffContainerHeight = $this->config->get( 'CPDCanvasProcessHeight' );
		$diffContainerHtml = Html::rawElement(
			'div',
			[
				'id' => self::CPD_DIFF_CONTAINER_ID,
				'style' => "height: {$diffContainerHeight}px; display: flex;"
			]
		);

		$out = $differenceEngine->getOutput();
		$out->addJsConfigVars( [
			'cpdDiffContainer' => self::CPD_DIFF_CONTAINER_ID,
			'cpdDiffButtonContainer' => self::CPD_DIFF_BUTTON_CONTAINER_ID,
			'cpdProcess' => $process,
			'cpdDiffNewRevision' => $new->getId(),
			'cpdDiffOldRevision' => $old->getId()
		] );
		$out->enableOOUI();
		$out->addModules( [ 'ext.cpd.bpmndiffer' ] );
		$out->prependHTML( $diffContainerHtml );
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
	) {
		if ( $textSlotDiffRenderer->getContentModel() !== CognitiveProcessDesignerContent::MODEL ) {
			return;
		}

		/**
		 * new ButtonGroupWidget( [
		 * 'items' => [
		 * new ButtonWidget( [
		 * 'data' => 'visual',
		 * 'icon' => 'eye',
		 * 'label' => $output->msg( 'visualeditor-savedialog-review-visual' )->plain()
		 * ] ),
		 * new ButtonWidget( [
		 * 'data' => 'source',
		 * 'icon' => 'wikiText',
		 * 'active' => true,
		 * 'label' => $output->msg( 'visualeditor-savedialog-review-wikitext' )->plain()
		 * ] )
		 * ]
		 * ] ) .
		 * '</div>';
		 */

		$output = $context->getOutput();
		$diffButtonContainerId = self::CPD_DIFF_BUTTON_CONTAINER_ID;
		$parts['50_ve-init-mw-diffPage-diffMode'] = '<div class="ve-init-mw-diffPage-diffMode">' .
			// Will be replaced by a ButtonSelectWidget in JS
			new ButtonGroupWidget( [
				'items' => [
					new ButtonWidget( [
						'data' => 'visual',
						'icon' => 'eye',
						'disabled' => true,
						'label' => $output->msg( 'visualeditor-savedialog-review-visual' )->plain()
					] ),
					new ButtonWidget( [
						'data' => 'source',
						'icon' => 'wikiText',
						'active' => true,
						'label' => $output->msg( 'visualeditor-savedialog-review-wikitext' )->plain()
					] )
				]
			] ) .
			'</div>';
	}
}
