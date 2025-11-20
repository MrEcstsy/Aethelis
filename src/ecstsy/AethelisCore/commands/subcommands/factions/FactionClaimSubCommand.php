<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\factions\claims\ClaimManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\Roles;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class FactionClaimSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&cThis command can only be used in-game!"));
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            $sender->sendMessage(C::colorize("&r&cPlayer session not found."));
            return;
        }

        $factionId = $session->getFaction();
        if ($factionId === null) {
            $sender->sendMessage(C::colorize("&r&cYou are not in a faction!"));
            return;
        }

        $role = $session->getFactionRole();
        if ($role === null || $role < Roles::ALL[Roles::MEMBER]) { 
            $sender->sendMessage(C::colorize("&r&cYou do not have permission to claim!"));
            return;
        }

        $claimManager = ClaimManager::getInstance();
        $existingClaim = $claimManager->getClaimAtPosition($sender->getPosition());
        if ($existingClaim !== null) {
            $sender->sendMessage(C::colorize("&r&cThis chunk is already claimed by another faction!"));
            return;
        }
        
        $pos = $sender->getPosition();
        $chunkX = $pos->getFloorX() >> 4;
        $chunkZ = $pos->getFloorZ() >> 4;

        $claimManager->createClaim($session->getFaction(), $sender->getWorld(), $chunkX, $chunkZ);
        $sender->sendMessage(C::colorize("&r&aYou have successfully claimed this chunk!"));
    }

    public function getPermission(): string {
        return "aethelis.faction.claim";
    }
}
