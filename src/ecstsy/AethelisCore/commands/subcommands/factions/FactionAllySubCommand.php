<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\factions\managers\RelationRequestManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionAllySubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new RawStringArgument("player", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $senderFaction = $session->getFaction();
        if($senderFaction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $role = $session->getFactionRole();
        if (!in_array($role, ["leader", "officer"])) {
            $sender->sendMessage("§cYour faction role does not allow you to request alliances.");
            return;
        }
        
        if ($senderFaction->getAllyCount() >= 2) {
            $sender->sendMessage("§cYour faction already has the maximum number of allies (2).");
            return;
        }
        
        $targetInput = $args["player"] ?? null;
        if ($targetInput === null) {
            $sender->sendMessage("§cUsage: /f ally <player|faction>");
            return;
        }
        
        $targetFaction = Loader::getFactionManager()->getFaction(strtolower($targetInput));
        if ($targetFaction === null) {
            $targetPlayer = PlayerUtils::getPlayerByPrefix($targetInput);
            if ($targetPlayer === null) {
                $sender->sendMessage("§cNo faction or online player found matching '$targetInput'.");
                return;
            }
            $targetSession = Loader::getPlayerManager()->getSession($targetPlayer);
            $targetFaction = $targetSession->getFaction();
            if ($targetFaction === null) {
                $sender->sendMessage("§cPlayer $targetInput is not in any faction.");
                return;
            }
        }
        
        if ($targetFaction->getFactionId() === $senderFaction->getFactionId()) {
            $sender->sendMessage("§cYou cannot request an alliance with your own faction.");
            return;
        }
        
        if (!isset($targetPlayer) || $targetPlayer === null) {
            $targetPlayer = Loader::getInstance()->getServer()->getPlayerExact($targetFaction->getLeaderName());
            if ($targetPlayer === null) {
                $members = $targetFaction->getMembers();
                if (count($members) > 0) {
                    $targetPlayer = Loader::getInstance()->getServer()->getPlayerByRawUUID($members[0]->toString());
                }
            }
        }
        
        if ($targetPlayer === null) {
            $sender->sendMessage("§cNo online representative found for the target faction.");
            return;
        }
        
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $duration = (int)$config->getNested("settings.factions.invite-timer", 30);
        
        RelationRequestManager::sendRequest($sender, $targetPlayer, $senderFaction->getFactionId(), $duration, "ally");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
