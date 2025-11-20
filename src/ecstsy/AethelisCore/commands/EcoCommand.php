<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands;

use CortexPE\Commando\args\BaseArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AethelisCore\commands\subcommands\economy\EcoGiveSubCommand;
use ecstsy\AethelisCore\commands\subcommands\economy\EcoResetSubCommand;
use ecstsy\AethelisCore\commands\subcommands\economy\EcoSetSubCommand;
use ecstsy\AethelisCore\commands\subcommands\economy\EcoTakeSubCommand;
use ecstsy\AethelisCore\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class EcoCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new EcoGiveSubCommand(Loader::getInstance(), "give", "Gives the specified player the specified amount of money"));
        $this->registerSubCommand(new EcoTakeSubCommand(Loader::getInstance(), "take", "Takes the specified amount of money from the specified player"));
        $this->registerSubCommand(new EcoSetSubCommand(Loader::getInstance(), "set", "Sets the specified player's balance to the specified amount of money"));
        $this->registerSubCommand(new EcoResetSubCommand(Loader::getInstance(), "reset", "Resets the specified player's balance to the server's starting balance"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $sender->sendMessage(C::colorize("&r&6Description: &f" . $this->getDescription()));
        $sender->sendMessage(C::colorize("&r&6Usages(s):"));

        foreach ($this->getSubCommands() as $subCommand) {
            $usage = "&r&f/eco " . $subCommand->getName();
            
            foreach ($subCommand->getArgumentList() as $param) {
                if ($param instanceof BaseArgument) {
                    $usage .= " &e" . $param->getName();
                }
            }
            
            $usage .= " &6- " . $subCommand->getDescription();
            $sender->sendMessage(C::colorize($usage));
        }

    }

    public function getPermission(): string {
        return "aethelis.admin";
    }
}