<?php

namespace CognitiveProcessDesigner\Util;

class CpdSequenceFlowUtil {

	/**
	 * Creates real sequence flows based on description page eligible elements.
	 * e.g. skip gateways
	 *
	 * @param array $edges
	 * @param array $validNodes
	 *
	 * @return array
	 */
	public static function fixSubpageSequenceFlows( array $edges, array $validNodes ): array {
		$invalidSet = self::findInvalidNodes( $edges, $validNodes );

		$graph = [];
		$resultSet = [];

		foreach ( $edges as $edge ) {
			$graph[ $edge['sourceRef'] ][] = $edge['targetRef'];
		}

		foreach ( $edges as $edge ) {
			$from = $edge['sourceRef'];
			$to = $edge['targetRef'];

			if ( isset( $invalidSet[ $to ] ) ) {
				// Follow invalid chain from 'to' to valid targets
				$visited = [];
				$targets = self::findValidTargetsDirected( $to, $graph, $invalidSet, $visited );
				foreach ( $targets as $target ) {
					$resultSet["$from->$target"] = [
						$from,
						$target
					];
				}
			} elseif ( !isset( $invalidSet[ $from ] ) ) {
				// Valid edge, keep it
				$resultSet["$from->$to"] = [
					$from,
					$to
				];
			}
		}

		return array_map( function ( $edge ) {
			return [
				'sourceRef' => $edge[0],
				'targetRef' => $edge[1]
			];
		}, array_values( $resultSet ) );
	}

	/**
	 * @param array $edges
	 * @param array $validNodes
	 *
	 * @return array
	 */
	private static function findInvalidNodes( array $edges, array $validNodes ): array {
		$invalidSet = [];

		$validIds = array_map( fn ( $node ) => $node['id'], $validNodes );

		foreach ( $edges as $edge ) {
			if ( !in_array( $edge['sourceRef'], $validIds, true ) ) {
				$invalidSet[] = $edge['sourceRef'];
			}

			if ( !in_array( $edge['targetRef'], $validIds, true ) ) {
				$invalidSet[] = $edge['targetRef'];
			}
		}

		return array_flip( array_unique( $invalidSet ) );
	}

	/**
	 * @param string $node
	 * @param array $graph
	 * @param array $invalidSet
	 * @param array $visited
	 *
	 * @return array
	 */
	private static function findValidTargetsDirected(
		string $node,
		array &$graph,
		array &$invalidSet,
		array &$visited
	): array {
		if ( isset( $visited[ $node ] ) ) {
			return [];
		}
		$visited[ $node ] = true;

		$results = [];

		foreach ( $graph[ $node ] ?? [] as $next ) {
			if ( isset( $invalidSet[ $next ] ) ) {
				$results = array_merge(
					$results,
					self::findValidTargetsDirected( $next, $graph, $invalidSet, $visited )
				);
			} else {
				$results[] = $next;
			}
		}

		return $results;
	}
}
