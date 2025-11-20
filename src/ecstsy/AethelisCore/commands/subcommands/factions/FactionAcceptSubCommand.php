<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\factions\managers\InvitationManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionAcceptSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new RawStringArgument("faction", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $invite = InvitationManager::getInvitation($sender);
        if($invite === null) {
            $sender->sendMessage("§cYou have no pending faction invitations.");
            return;
        }
        
        $invitedFactionId = $invite["factionId"];
        $argFaction = strtolower($args["faction"] ?? "");
        if($argFaction !== "" && strtolower($invitedFactionId) !== $argFaction) {
            $sender->sendMessage("§cThe invitation you have is not for faction '$argFaction'.");
            return;
        }
        
        $faction = Loader::getFactionManager()->getFaction($invitedFactionId);
        if($faction === null) {
            $sender->sendMessage("§cThe faction you were invited to no longer exists.");
            InvitationManager::removeInvitation($sender);
            return;
        }
        
        $faction->addMember($sender->getUniqueId());
        $faction->updateMemberName($sender->getUniqueId(), $sender->getName());
        $session->setFaction($invitedFactionId);
        $session->setFactionRole("recruit");
        
        InvitationManager::removeInvitation($sender);
        
        $sender->sendMessage("§aYou have successfully joined faction " . $faction->getName() . " as a recruit.");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
