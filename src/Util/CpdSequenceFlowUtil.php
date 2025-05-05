<?php

namespace CognitiveProcessDesigner\Util;

class CpdSequenceFlowUtil {
	/**
	 * Creates real sequence flows based on description page eligible elements.
	 * e.g. skip gateways
	 *
	 * @param array $elementsData
	 * @param array $dedicatedSubpageTypes
	 *
	 * @return array
	 */
	public static function createSubpageSequenceFlows( array $elementsData, array $dedicatedSubpageTypes ): array {
		$nodesAndEdges = self::createNodesAndEdges( $elementsData, $dedicatedSubpageTypes );
		$edges = $nodesAndEdges['edges'];
		$invalidSet = $nodesAndEdges['nodes'];

		$graph = [];
		$resultSet = [];

		foreach ( $edges as [$from, $to] ) {
			$graph[ $from ][] = $to;
		}

		foreach ( $edges as [$from, $to] ) {
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
				'type' => 'bpmn:SequenceFlow',
				'sourceRef' => $edge[0],
				'targetRef' => $edge[1]
			];
		}, array_values( $resultSet ) );
	}

	/**
	 * Creates nodes and edges for the process.
	 *
	 * @param array $elementsData
	 * @param array $dedicatedSubpageTypes
	 *
	 * @return array
	 */
	private static function createNodesAndEdges( array $elementsData, array $dedicatedSubpageTypes ): array {
		$edges = [];
		$invalidNodes = [];

		foreach ( $elementsData as $elementData ) {
			if ( empty( $elementData['type'] ) ) {
				continue;
			}

			$type = $elementData['type'];

			if ( $type === 'bpmn:SequenceFlow' ) {
				$edges[] = [
					$elementData['sourceRef'],
					$elementData['targetRef']
				];
				continue;
			}

			if ( empty( $elementData['id'] ) ) {
				continue;
			}

			if ( in_array( $type, $dedicatedSubpageTypes ) ) {
				continue;
			}

			$invalidNodes[] = $elementData['id'];
		}

		return [
			'edges' => $edges,
			'nodes' => array_flip( $invalidNodes )
		];
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
