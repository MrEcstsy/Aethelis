<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\flags;

class FlagFactory {
    /** @var FactionFlag[] */
    private static array $flags = [];

    public static function init(): void {
        self::registerFlag(new FactionFlag(FactionFlag::OPEN, false));
        self::registerFlag(new FactionFlag(FactionFlag::SAFEZONE, false));
        self::registerFlag(new FactionFlag(FactionFlag::WARZONE, false));
    }

    public static function getFlags(): array {
        return self::$flags;
    }

    public static function registerFlag(FactionFlag $flag): void {
        self::$flags[$flag->getName()] = $flag;
    }
}