<?php

namespace CognitiveProcessDesigner\UsageTracker;

class RegisterUsageTracker {

	/**
	 * @param array &$collectorConfig
	 * @return void
	 */
	public function onBSUsageTrackerRegisterCollectors( array &$collectorConfig ) {
		$collectorConfig[NumberOfProcesses::IDENTIFIER] = [
			'class' => NumberOfProcesses::class,
			'config' => []
		];
		$collectorConfig[NumberOfDescriptionPages::IDENTIFIER] = [
			'class' => NumberOfDescriptionPages::class,
			'config' => []
		];
		$collectorConfig[MedianOfBpmnElements::IDENTIFIER] = [
			'class' => MedianOfBpmnElements::class,
			'config' => []
		];
	}
}
