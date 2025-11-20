<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class FactionUnclaimSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) {
            return;
        }
        
        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            $sender->sendMessage("§cAn error occurred.");
            return;
        }
        
        $faction = $session->getFaction();
        if ($faction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $role = $session->getFactionRole();
        if (!in_array($role, ["leader", "officer"])) {
            $sender->sendMessage("§cOnly leaders and officers can unclaim territory.");
            return;
        }
        
        $claim = Loader::getClaimsManager()->getClaimAtPosition($sender->getPosition());
        if ($claim === null) {
            $sender->sendMessage("§cThere is no claim in this area.");
            return;
        }
        
        $claimFaction = $claim->getFaction();
        if ($claimFaction === null || $claimFaction->getFactionId() !== $faction->getFactionId()) {
            $sender->sendMessage("§cThis chunk does not belong to your faction.");
            return;
        }
        
        Loader::getClaimsManager()->deleteClaim($claim);
        
        $sender->sendMessage("§aYou have unclaimed this chunk from your faction.");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
