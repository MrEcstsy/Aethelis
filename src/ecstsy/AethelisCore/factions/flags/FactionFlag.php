<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\flags;

use JsonSerializable;

class FactionFlag implements JsonSerializable {
    public const OPEN = "open";
    public const SAFEZONE = "safezone";
    public const WARZONE = "warzone";

    public function __construct(
        private string $name,
        private bool $defaultValue
    ) {}

    public function getName(): string {
        return $this->name;
    }

    public function getDefaultValue(): bool {
        return $this->defaultValue;
    }

    public function jsonSerialize(): bool {
        return $this->defaultValue;
    }
}