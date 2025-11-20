<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionKickSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);
        $faction = $session->getFaction();
        if($faction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $role = $session->getFactionRole();

        if (!in_array($role, ["leader", "officer"])) {
            $sender->sendMessage("§cYou do not have permission to invite players.");
            return;
        }
        
        $targetName = $args["name"] ?? null;
        if($targetName === null) {
            $sender->sendMessage("§cUsage: /f kick <player>");
            return;
        }
        
        $target = Loader::getInstance()->getServer()->getPlayerExact($targetName);
        if($target === null) {
            $sender->sendMessage("§cPlayer $targetName is not online.");
            return;
        }
        
        $targetSession = Loader::getPlayerManager()->getSession($target);
        $targetFaction = $targetSession->getFaction();
        if($targetFaction === null || $targetFaction->getFactionId() !== $faction->getFactionId()) {
            $sender->sendMessage("§cPlayer $targetName is not in your faction.");
            return;
        }
        
        if($sender->getUniqueId()->equals($target->getUniqueId())) {
            $sender->sendMessage("§cYou cannot kick yourself.");
            return;
        }
        
        $faction->removeMember($target->getUniqueId());
        $targetSession->setFaction(null);
        $targetSession->setFactionRole(null);
        
        $sender->sendMessage("§aYou have kicked $targetName from the faction.");
        $target->sendMessage("§cYou have been kicked from your faction by " . $sender->getName() . ".");
    }

    public function getPermission(): string {
        return 'aethelis.default';
    }
}