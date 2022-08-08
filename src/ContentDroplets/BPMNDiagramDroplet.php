<?php

declare( strict_types = 1 );

namespace CognitiveProcessDesigner\ContentDroplets;

use MediaWiki\Extension\ContentDroplets\Droplet\TagDroplet;
use Message;
use RawMessage;

class BPMNDiagramDroplet extends TagDroplet {

	/**
	 */
	public function __construct() {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): Message {
		return new RawMessage( 'BPMN Diagram' );
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): Message {
		return new RawMessage( "Create BPMN diagram" );
	}

	/**
	 * @inheritDoc
	 */
	public function getIcon(): string {
		return 'chart';
	}

	/**
	 * @inheritDoc
	 */
	public function getRLModule(): string {
		return 'ext.cpd.ve.tagdefinition';
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
