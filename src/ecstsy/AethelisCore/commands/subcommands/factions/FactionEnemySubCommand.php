<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionEnemySubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("target", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $senderFaction = $session->getFaction();
        if ($senderFaction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $role = $session->getFactionRole();
        if (!in_array($role, ["leader", "officer"])) {
            $sender->sendMessage("§cYour faction role does not allow you to declare enemies.");
            return;
        }
        
        $targetInput = $args["target"] ?? null;
        if ($targetInput === null) {
            $sender->sendMessage("§cUsage: /f enemy <player|faction>");
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
            $sender->sendMessage("§cYou cannot declare your own faction as an enemy.");
            return;
        }
        
        $senderFaction->setRelation($targetFaction, "enemy");
        $targetFaction->setRelation($senderFaction, "enemy");
        $sender->sendMessage("§aYou have declared faction " . $targetFaction->getName() . " as an enemy.");
        Utils::broadcastRelationChange($senderFaction, $targetFaction, "&r&eYour faction is now &cenemied &ewith &c{faction}&e.");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
