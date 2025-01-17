<?php

namespace CognitiveProcessDesigner\Rest;

use MediaHandler;
use MediaWiki\Rest\Handler;
use MediaWiki\Title\Title;
use MimeAnalyzer;
use MWFileProps;
use RepoGroup;
use RequestContext;
use SpecialUpload;
use TempFSFile;
use Wikimedia\AtEase\AtEase;
use Wikimedia\ParamValidator\ParamValidator;

class SaveSvgHandler extends Handler {

	/**
	 * @var MimeAnalyzer
	 */
	private $mimeAnalyzer;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @param MimeAnalyzer $mimeAnalyzer
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( MimeAnalyzer $mimeAnalyzer, RepoGroup $repoGroup ) {
		$this->mimeAnalyzer = $mimeAnalyzer;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->getValidatedParams();
		$body = $this->getValidatedBody();

		$user = RequestContext::getMain()->getUser();

		$tempFilePath = TempFSFile::getUsableTempDirectory() . '/' . $params['filename'];
		if ( !file_put_contents( $tempFilePath, $body['svgContent'] ) ) {
			return $this->getResponseFactory()->createJson( [
				'success' => false,
				'error' => "Could not create temporary file: '$tempFilePath'"
			] );
		}

		// MediaWiki normalizes multiple spaces/undescores into one single score/underscore
		$title = str_replace( ' ', '_', $params['filename'] );
		$title = preg_replace( '#(_)+#si', '_', $title );

		$targetTitle = Title::makeTitle( NS_FILE, $title );
		$repo = $this->repoGroup->getLocalRepo();

		$repoFile = $repo->newFile( $targetTitle );

		/*
		 * The following code is almost a direct copy of
		 * <mediawiki>/maintenance/importImages.php
		 */

		$mwProps = new MWFileProps( $this->mimeAnalyzer );
		$props = $mwProps->getPropsFromPath( $tempFilePath, true );

		$flags = 0;
		$publishOptions = [];
		$handler = MediaHandler::getHandler( $props['mime'] );
		if ( $handler ) {
			if ( is_array( $props['metadata'] ) ) {
				$metadata = $props['metadata'];
			} else {
				$metadata = AtEase::quietCall( 'unserialize', $props['metadata'] );
			}

			$publishOptions['headers'] = $handler->getContentHeaders( $metadata );
		} else {
			$publishOptions['headers'] = [];
		}
		$archive = $repoFile->publish( $tempFilePath, $flags, $publishOptions );
		if ( !$archive->isGood() ) {
			return $this->getResponseFactory()->createJson( [
				'success' => false,
				'error' => 'Error when publishing file'
			] );
		}

		$commentText = SpecialUpload::getInitialPageText();

		$status = $repoFile->recordUpload3( $archive->value, '', $commentText, $user, $props );
		if ( !$status->isGood() ) {
			// Check for case if file already exists. Then no error here
			if ( $status->getErrors()[0]['message'] !== 'fileexists-no-change' ) {
				return $this->getResponseFactory()->createJson( [
					'success' => false,
					'error' => 'Error when recording file upload'
				] );
			}
		}

		// Get and return to frontend information about image
		$file = $repo->findBySha1( $props['sha1'] )[0];

		$imageInfo = [
			'height' => $file->getHeight(),
			'width' => $file->getWidth(),
			'url' => $file->getCanonicalUrl(),
			'timestamp' => $file->getTimestamp()
		];

		return $this->getResponseFactory()->createJson( [
			'success' => true,
			'imageInfo' => $imageInfo
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'filename' => [
				static::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyParamSettings(): array {
		return [
			'svgContent' => [
				static::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}

	public function getSupportedRequestTypes(): array {
		return [
			'application/x-www-form-urlencoded',
			'multipart/form-data',
		];
	}
}
