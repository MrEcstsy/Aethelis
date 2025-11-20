<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\utils;

final class Roles {
    public const LEADER = "leader";
    public const OFFICER = "officer";
    public const MEMBER = "member";
    public const RECRUIT = "recruit";

    public const ALL = [
        self::RECRUIT => 1,
        self::MEMBER => 2,
        self::OFFICER => 3,
        self::LEADER => 4
    ];
}
