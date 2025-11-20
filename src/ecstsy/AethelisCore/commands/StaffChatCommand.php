<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\ChatTypes;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class StaffChatCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }
        
        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            return;
        }
        
        if ($session->getCurrentChat() === ChatTypes::STAFF) {
            $session->setCurrentChat(ChatTypes::ALL);
            $sender->sendMessage(C::colorize("&r&aStaff chat disabled. Switched to public chat."));
        } else {
            $session->setCurrentChat(ChatTypes::STAFF);
            $sender->sendMessage(C::colorize("&r&aStaff chat enabled."));
        }
    }

    public function getPermission(): string {
        return "aethelis.staff-chat";
    }
}
