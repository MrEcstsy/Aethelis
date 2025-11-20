<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\factions\managers\RelationRequestManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionAllyAcceptSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("faction", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $ownFaction = $session->getFaction();
        if ($ownFaction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $argFaction = strtolower($args["faction"] ?? "");
        $request = RelationRequestManager::getRequest($sender, "ally");
        if ($request === null) {
            $sender->sendMessage("§cYou have no pending alliance requests.");
            return;
        }
        
        $requestingFactionId = $request["factionId"];
        if ($argFaction !== "" && strtolower($requestingFactionId) !== $argFaction) {
            $sender->sendMessage("§cYour pending request is not for faction '$argFaction'.");
            return;
        }
        
        $requestingFaction = Loader::getFactionManager()->getFaction($requestingFactionId);
        if ($requestingFaction === null) {
            $sender->sendMessage("§cThe faction that invited you no longer exists.");
            RelationRequestManager::removeRequest($sender, "ally");
            return;
        }
        
        $ownFaction->setRelation($requestingFaction, "ally");
        $requestingFaction->setRelation($ownFaction, "ally");
        RelationRequestManager::removeRequest($sender, "ally");
        
        $sender->sendMessage("§aAlliance with faction " . $requestingFaction->getName() . " accepted!");

        Utils::broadcastRelationChange($ownFaction, $requestingFaction, "&r&eYour faction is now &dallied &ewith &d{faction}&e.");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
