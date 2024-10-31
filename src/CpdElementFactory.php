<?php

namespace CognitiveProcessDesigner;

class CpdElementFactory {
	/**
	 * @param array $elements
	 *
	 * @return CpdElement[]
	 */
	public function makeElements( array $elements ): array {
		return array_map( function ( $element ) {
			return $this->makeElement( $element );
		}, $elements );
	}

	/**
	 * @param array $element
	 *
	 * @return CpdElement
	 */
	public function makeElement( array $element ): CpdElement {
		return CpdElement::fromElementJson( $element );
	}
}
