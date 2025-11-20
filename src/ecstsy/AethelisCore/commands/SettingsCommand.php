<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AethelisCore\utils\screens\SettingsScreen;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class SettingsCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $screen = new SettingsScreen($sender);
        
        $screen->display($sender);
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}