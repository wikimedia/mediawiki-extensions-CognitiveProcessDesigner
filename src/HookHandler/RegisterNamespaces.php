<?php

namespace CognitiveProcessDesigner\HookHandler;

use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Hook\CanonicalNamespacesHook;

class RegisterNamespaces implements CanonicalNamespacesHook {

	private const NS_NAME = 'Process';
	private const NS_NAME_TALK = 'Process_talk';
	private const NS = 1530;
	private const NS_TALK = 1531;

	/**
	 * @var Config
	 */
	private Config $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @param array &$namespaces
	 *
	 * @throws Exception
	 */
	public function onCanonicalNamespaces( &$namespaces ): void {
		$name = self::NS_NAME;
		$nameTalk = self::NS_NAME_TALK;

		if (
			$this->checkForNamingCollisions(
				[
					$name,
					$nameTalk
				],
				$namespaces
			)
		) {
			// Try fallback on collision
			$fallbackNames = $this->config->get( 'CPDFallbackNSNames' );

			$name = $fallbackNames[0];
			$nameTalk = $fallbackNames[1];
			if (
				$this->checkForNamingCollisions(
					[
						$name,
						$nameTalk
					],
					$namespaces
				)
			) {
				throw new Exception(
					'CognitiveProcessDesigner: Namespace names "' .
					self::NS_NAME .
					'" and "' .
					$name .
					'" are already assigned'
				);
			}
		}

		if ( !defined( 'NS_PROCESS' ) ) {
			define( 'NS_PROCESS', self::NS );
			define( 'NS_PROCESS_TALK', self::NS_TALK );
		}

		$namespaces[self::NS] = $name;
		$namespaces[self::NS_TALK] = $nameTalk;
	}

	/**
	 * @param array $names
	 * @param array $namespaces
	 *
	 * @return bool
	 */
	private function checkForNamingCollisions( array $names, array $namespaces ): bool {
		return !empty( array_intersect( $names, array_values( $namespaces ) ) );
	}
}
