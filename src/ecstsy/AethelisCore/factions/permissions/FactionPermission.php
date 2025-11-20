<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\permissions;

use JsonSerializable;

class FactionPermission implements JsonSerializable {
    public const CLAIM = "claim";
    public const BUILD = "build";
    public const BREAK = "break";
    public const INTERACT = "interact";
    public const ALLY = "ally";
    public const BAN = "ban";
    public const CONTAINERS = "containers";
    public const DEMOTE = "demote";
    public const DESCRIPTION = "description";
    public const INVITE = "invite";
    public const ENEMY = "enemy";
    public const FLAG = "flag";
    public const FLY = "fly";
    public const KICK = "kick";
    public const MOTD = "motd";
    public const NAME = "name";
    public const NEUTRAL = "neutral";
    public const PROMOTE = "promote";
    public const SETHOME = "sethome";
    public const UNALLY = "unally";
    public const UNBAN = "unban";
    public const UNCLAIM = "unclaim";

    public function __construct(
        private string $name,
        private array $allowedRoles
    ) {}

    public function getName(): string {
        return $this->name;
    }

    public function getAllowedRoles(): array {
        return $this->allowedRoles;
    }

    public function jsonSerialize(): array {
        return $this->allowedRoles;
    }
}