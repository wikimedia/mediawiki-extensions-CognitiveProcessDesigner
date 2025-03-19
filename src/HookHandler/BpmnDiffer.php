<?php

namespace CognitiveProcessDesigner\HookHandler;

use CognitiveProcessDesigner\Exceptions\CpdInvalidNamespaceException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Config\Config;
use MediaWiki\Diff\Hook\DifferenceEngineViewHeaderHook;
use MediaWiki\Html\Html;

class BpmnDiffer implements DifferenceEngineViewHeaderHook {

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
	 * @throws CpdInvalidNamespaceException
	 */
	public function onDifferenceEngineViewHeader( $differenceEngine ) {
		$new = $differenceEngine->getNewRevision();
		$old = $differenceEngine->getOldRevision();

		$out = $differenceEngine->getOutput();

		$diffContainerId = 'cpd-diff-container';
		$diffContainerHeight = $this->config->get( 'CPDCanvasProcessHeight' );
		$diffContainerHtml = Html::rawElement(
			'div',
			[
				'id' => $diffContainerId,
				'style' => "height: {$diffContainerHeight}px;"
			]
		);

		$out->addJsConfigVars( [
			'cpdDiffContainer' => $diffContainerId,
			'cpdProcess' => CpdDiagramPageUtil::getProcess( $new->getPage() ),
			'cpdDiffNewRevision' => $new->getId(),
			'cpdDiffOldRevision' => $old->getId()
		] );
		$out->enableOOUI();
		$out->addModules( [ 'ext.cpd.bpmndiffer' ] );
		$out->prependHTML( $diffContainerHtml );
	}
}
