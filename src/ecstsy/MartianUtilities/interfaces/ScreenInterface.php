<?php

declare(strict_types=1);

namespace ecstsy\MartianUtilities\interfaces;

use pocketmine\player\Player;

interface ScreenInterface {

    /**
     * Displays the screen to the given player
     */
    public function display(Player $player): void;
}