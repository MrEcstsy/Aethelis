<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionDisbandSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;

        $session = Loader::getPlayerManager()->getSession($sender);
        $faction = $session->getFaction();
        if($faction === null) {
            $sender->sendMessage("§cYou are not in a faction.");
            return;
        }
        
        $role = $session->getFactionRole();
        if($role !== "leader") {
            $sender->sendMessage("§cOnly the faction leader can disband the faction.");
            return;
        }
        
        foreach ($faction->getMembers() as $memberUuid) {
            $memberSession = Loader::getPlayerManager()->getSessionByUuid($memberUuid);
            if ($memberSession !== null) {
                $memberSession->setFaction(null);
                $memberSession->setFactionRole(null);
                $playerObj = Loader::getInstance()->getServer()->getPlayerByUUID($memberUuid);
                if ($playerObj !== null) {
                    $playerObj->sendMessage("§cYour faction has been disbanded by the leader.");
                }
            }
        }
        
        Loader::getFactionManager()->deleteFaction($faction->getFactionId());
        
        $sender->sendMessage("§aYou have disbanded your faction.");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
