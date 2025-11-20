<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\permissions;

use ecstsy\AethelisCore\utils\Roles;

class PermissionFactory {
    /** @var FactionPermission[] */
    private static array $permissions = [];

    public static function init(): void {
        self::registerPermission(new FactionPermission(
            FactionPermission::CLAIM, 
            [Roles::LEADER, Roles::OFFICER]
        ));
        
        self::registerPermission(new FactionPermission(
            FactionPermission::BUILD,
            [Roles::LEADER, Roles::OFFICER, Roles::MEMBER]
        ));

        self::registerPermission(new FactionPermission(
            FactionPermission::BREAK,
            [Roles::LEADER, Roles::OFFICER, Roles::MEMBER]
        ));
    }

    public static function getPermissions(): array {
        return self::$permissions;
    }

    public static function registerPermission(FactionPermission $permission): void {
        self::$permissions[$permission->getName()] = $permission;
    }
}