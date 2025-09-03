<?php

namespace CognitiveProcessDesigner\Util;

use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PermissionsError;

class CpdProcessUtil {

	/**
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( private readonly PermissionManager $permissionManager ) {
	}

	/**
	 * @param User $user
	 * @param string $permission
	 *
	 * @return bool
	 */
	public function hasPermission( User $user, string $permission = 'read' ): bool {
		return $this->permissionManager->userCan( $permission, $user, self::getProcessDummyPage() );
	}

	/**
	 * @throws PermissionsError
	 */
	public function throwPermissionErrors( User $user, string $permission = 'read' ): void {
		$this->permissionManager->throwPermissionErrors(
			$permission,
			$user,
			self::getProcessDummyPage()
		);
	}

	/**
	 * @return Title
	 */
	public static function getProcessDummyPage(): Title {
		return Title::newFromText( 'Dummy', NS_PROCESS );
	}
}
