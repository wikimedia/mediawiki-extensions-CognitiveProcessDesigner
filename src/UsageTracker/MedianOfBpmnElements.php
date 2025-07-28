<?php

namespace CognitiveProcessDesigner\UsageTracker;

use BS\UsageTracker\CollectorResult;
use BS\UsageTracker\Collectors\Base as UsageTrackerBase;
use CognitiveProcessDesigner\Content\CognitiveProcessDesignerContent;
use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidArgumentException;
use CognitiveProcessDesigner\Exceptions\CpdInvalidContentException;
use CognitiveProcessDesigner\Exceptions\CpdXmlProcessingException;
use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use CognitiveProcessDesigner\Util\CpdXmlProcessor;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class MedianOfBpmnElements extends UsageTrackerBase {

	public const IDENTIFIER = 'cpd-median-of-bpmn-elements';

	/** @var CpdXmlProcessor */
	private CpdXmlProcessor $xmlProcessor;

	/** @var CpdDiagramPageUtil */
	private CpdDiagramPageUtil $diagramPageUtil;

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function __construct() {
		parent::__construct();
		$this->xmlProcessor = $this->services->get( 'CpdXmlProcessor' );
		$this->loadBalancer = $this->services->getDBLoadBalancer();
		$this->diagramPageUtil = $this->services->get( 'CpdDiagramPageUtil' );
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return 'Median of description page eligible bpmn elements';
	}

	/**
	 *
	 * @return string
	 */
	public function getIdentifier(): string {
		return self::IDENTIFIER;
	}

	/**
	 * @return CollectorResult
	 * @throws CpdInvalidArgumentException
	 * @throws CpdInvalidContentException
	 */
	public function getUsageData(): CollectorResult {
		$res = new CollectorResult( $this );

		$processes = $this->getAllProcesses();
		$descriptionElementsCount = [];
		foreach ( $processes as $process ) {
			$xml = $this->diagramPageUtil->getXml( $process );
			if ( !$xml ) {
				continue;
			}

			try {
				$elements = $this->xmlProcessor->createElements( $process, $xml );
				$descriptionElementsCount[ $process ] = count( $elements );
			} catch ( CpdCreateElementException | CpdXmlProcessingException $e ) {
				// Skip for invalid content
				continue;
			}
		}

		$res->count = $this->median( $descriptionElementsCount );

		return $res;
	}

	/**
	 * @return string[]
	 */
	private function getAllProcesses(): array {
		$pages = [];
		$db = $this->loadBalancer->getConnection( DB_REPLICA );
		$rows = $db->newSelectQueryBuilder()->select( 'page_title' )->from( 'page' )->where( [
			'page_namespace' => NS_PROCESS,
			'page_content_model' => CognitiveProcessDesignerContent::MODEL,
			'page_is_redirect' => 0,
		] )->caller( __METHOD__ )->fetchResultSet();

		foreach ( $rows as $row ) {
			$pages[] = $row->page_title;
		}

		return $pages;
	}

	/**
	 * @param array $elements
	 *
	 * @return float
	 */
	private function median( array $elements ): float {
		sort( $elements );
		$count = count( $elements );
		$middle = floor( $count / 2 );

		if ( $count % 2 ) {
			// Odd number of elements
			return $elements[ $middle ];
		} else {
			// Even number of elements
			return ( $elements[ $middle - 1 ] + $elements[ $middle ] ) / 2;
		}
	}
}
