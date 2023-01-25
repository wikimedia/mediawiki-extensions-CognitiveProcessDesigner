<?php

declare( strict_types = 1 );

namespace CognitiveProcessDesigner\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use Message;

class BPMNDiagramDroplet extends TagDroplet {

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return Message::newFromKey( 'cpd-droplet-bpmn-name' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return Message::newFromKey( 'cpd-droplet-bpmn-description' );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'droplet-bpmn';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModules(): array {
		return [ 'ext.cpd.ve.tagdefinition' ];
	}

	/**
	 * @return array
	 */
	public function getCategories(): array {
		return [ 'visualization' ];
	}

	/**
	 * @return string
	 */
	protected function getTagName(): string {
		return 'bpmn';
	}

	/**
	 * @return array
	 */
	protected function getAttributes(): array {
		return [ 'name' ];
	}

	/**
	 * @return bool
	 */
	protected function hasContent(): bool {
		return true;
	}

	/**
	 * @return string|null
	 */
	public function getVeCommand(): ?string {
		return 'bpmnCommand';
	}
}
