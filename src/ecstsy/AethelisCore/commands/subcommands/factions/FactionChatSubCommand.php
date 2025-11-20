<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\ChatTypes;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionChatSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        
        $this->registerArgument(0, new RawStringArgument("mode", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $currentChat = $session->getCurrentChat();
        $input = strtolower($args["mode"] ?? "");
        
        $newChat = null;
        if ($input === "a" || $input === "ally") {
            $newChat = ChatTypes::ALLY;
        } elseif ($input === "f" || $input === "faction") {
            $newChat = ChatTypes::FACTION;
        } elseif ($input === "p" || $input === "public") {
            $newChat = ChatTypes::ALL;
        } else {
            if ($currentChat === ChatTypes::ALL) {
                $newChat = ChatTypes::FACTION;
            } elseif ($currentChat === ChatTypes::FACTION) {
                $newChat = ChatTypes::ALLY;
            } else {
                $newChat = ChatTypes::ALL;
            }
        }
        
        $session->setCurrentChat($newChat);
        
        switch($newChat) {
            case ChatTypes::ALLY:
                $sender->sendMessage("§aYour chat mode is now Ally Chat.");
                break;
            case ChatTypes::FACTION:
                $sender->sendMessage("§aYour chat mode is now Faction Chat.");
                break;
            default:
                $sender->sendMessage("§aYour chat mode is now Public Chat.");
        }
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}
