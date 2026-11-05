<?php

namespace CognitiveProcessDesigner\Data\OrphanedDescriptionPages;

use CognitiveProcessDesigner\Util\CpdDiagramPageUtil;
use MediaWiki\Title\TitleFactory;
use MWStake\MediaWiki\Component\DataStore\IStore;
use Wikimedia\Rdbms\ILoadBalancer;

class Store implements IStore {
	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param CpdDiagramPageUtil $cpdDiagramPageUtil
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		private readonly ILoadBalancer $loadBalancer,
		private readonly CpdDiagramPageUtil $cpdDiagramPageUtil,
		private readonly TitleFactory $titleFactory
	) {
	}

	/**
	 * @return null
	 */
	public function getWriter() {
		return null;
	}

	/**
	 * @return Reader
	 */
	public function getReader() {
		return new Reader( $this->loadBalancer, $this->cpdDiagramPageUtil, $this->titleFactory );
	}
}
