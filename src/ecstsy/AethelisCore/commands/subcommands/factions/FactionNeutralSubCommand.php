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

final class FactionNeutralSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
       
        $this->registerArgument(0, new RawStringArgument("target", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $ownFaction = $session->getFaction();
        if ($ownFaction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $targetInput = $args["target"] ?? null;
        if ($targetInput === null) {
            $sender->sendMessage("§cUsage: /f neutral <player|faction>");
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
        
        $relation = $ownFaction->getRelation($targetFaction);
        if ($relation === "neutral") {
            $sender->sendMessage("§cYour faction is already neutral with " . $targetFaction->getName() . ".");
            return;
        }
        
        $ownFaction->setRelation($targetFaction, "neutral");
        $targetFaction->setRelation($ownFaction, "neutral");
        $sender->sendMessage("§aYou have removed any ally or enemy status with faction " . $targetFaction->getName() . ".");
        Utils::broadcastRelationChange($ownFaction, $targetFaction, "&r&eYour faction is &fneutral &ewith &f{faction}&e.");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
