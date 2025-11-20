<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\factions\managers\InvitationManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionInviteSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("faction", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) return;
    
        $session = Loader::getPlayerManager()->getSession($sender);
        $faction = $session->getFaction();
        if ($faction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $role = $session->getFactionRole();
        if (!in_array($role, ["leader", "officer"])) {
            $sender->sendMessage("§cYou do not have permission to invite players.");
            return;
        }
        
        $targetName = $args["faction"] ?? null;
        if ($targetName === null) {
            $sender->sendMessage("§cUsage: /f invite <player>");
            return;
        }

        $target = PlayerUtils::getPlayerByPrefix($targetName);
        if ($target === null) {
            $sender->sendMessage("§cPlayer $targetName is not online.");
            return;
        }
        
        $targetSession = Loader::getPlayerManager()->getSession($target);
        if ($targetSession->getFaction() !== null) {
            $sender->sendMessage("§cPlayer $targetName is already in a faction.");
            return;
        }
        
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $duration = (int)$config->getNested("settings.factions.invite-timer", 30);
        
        InvitationManager::sendInvitation($sender, $target, $faction->getFactionId(), $duration);  
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}