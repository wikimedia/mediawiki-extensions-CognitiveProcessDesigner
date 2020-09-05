<?php
namespace CognitiveProcessDesigner\Tag;

use BlueSpice\Tag\Handler;
use Html;

class BPMNHandler extends Handler {
	protected $defaultImgType = 'svg';
	protected $tagInput = '';
	protected $tagArgs = [];
	protected $parser = null;

	/**
	 * BPMNHandler constructor.
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param \Parser $parser
	 * @param \PPFrame $frame
	 */
	public function __construct( $processedInput, array $processedArgs,
		 \Parser $parser, \PPFrame $frame
	) {
		$this->tagInput = explode( ' ', trim( $processedInput ) );
		$this->tagArgs = $processedArgs;
		$this->parser = $parser;
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );
	}

	/**
	 * @return string
	 */
	public function handle() {
		$bpmnName = wfStripIllegalFilenameChars( $this->tagArgs['name'] );
		$imgUrlTs = '';
		$imgDescUrl = '';
		$imgName = $bpmnName . '.' . $this->defaultImgType;
		$img = wfFindFile( $imgName );
		if ( $img ) {
			$imgUrlTs = $img->getViewUrl() . '?ts=' . $img->getTimestamp();
			$imgDescUrl = $img->getDescriptionUrl();
			$imgHeight = $this->tagArgs['height'] ?? $img->getHeight();
			$imgWidth = $this->tagArgs['width'] ?? $img->getWidth();
		} else {
			$imgHeight = $this->tagArgs['height'] ?? 'auto';
			$imgWidth = $this->tagArgs['width'] ?? 'auto';
		}

		$id = mt_rand();
		$readonly = !in_array(
			'cognitiveprocessdesigner-editbpmn',
			$this->parser->getUser()->getRights()
		);

		$output = Html::openElement( 'div', [ 'id' => 'cpd-' . $id ] );

		if ( !$readonly ) {
			$output .= Html::openElement(
				'div',
				[
					'class' => 'cpd-toolbar',
					'align' => 'right'
				]
			);
			$output .= Html::element(
				'button',
				[
					'id' => 'cpd-btn-edit-bpmn-id-' . $id,
					'class' => 'cpd-edit-bpmn',
					'data-id' => $id,
					'data-bpmn-name' => $bpmnName
				],
				wfMessage( 'edit' )->text()
			);
			$output .= Html::closeElement( 'div' );
		}

		$imgClass = '';

		// output image and optionally a placeholder if the image does not exist yet
		if ( !$img ) {
			$imgClass = 'hidden';
			// show placeholder
			$output .= Html::openElement(
				'div',
				[
					'id' => 'cpd-placeholder-' . $id,
					'class' => 'cpd-editor-info-box'
				]
			);

			$output .= Html::element( 'b', [], $bpmnName );
			$output .= Html::element( 'br' );
			$output .= Html::element( 'span', [], wfMessage( 'cpd-empty-diagram' )->text() );

			$output .= Html::closeElement( 'div' );
		}

		// the image or object element must be there' in any case
		// it's hidden as long as there is no content.
		$output .= Html::openElement(
			'a',
			[
				'id' => 'cpd-img-href-' . $id,
				'href' => $imgDescUrl
			]
		);

		$output .= Html::element(
			'img',
			[
				'id' => 'cpd-img-' . $id,
				'src' => $imgUrlTs,
				'title' => 'bpmn: ' . $bpmnName,
				'height' => $imgHeight,
				'width' => $imgWidth,
				'alt' => $bpmnName,
				'class' => $imgClass
			]
		);
		$output .= Html::closeElement( 'a' );

		$output .= Html::element(
			'div',
			[ 'id' => 'cpd-wrapper-' . $id, 'class' => 'hidden cpd-js-drop-zone' ]
		);

		return $output;
	}

}
