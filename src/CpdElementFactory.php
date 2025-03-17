<?php

namespace CognitiveProcessDesigner;

use CognitiveProcessDesigner\Exceptions\CpdCreateElementException;

class CpdElementFactory {

	/**
	 * @param array $elements
	 *
	 * @return CpdElement[]
	 * @throws CpdCreateElementException
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
	 * @throws CpdCreateElementException
	 */
	public function makeElement( array $element ): CpdElement {
		return CpdElement::fromElementJson( $element );
	}
}
