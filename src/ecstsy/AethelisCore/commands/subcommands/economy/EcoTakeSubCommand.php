<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\economy;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;

final class EcoTakeSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", false));
        $this->registerArgument(1, new IntegerArgument("amount", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = isset($args["name"]) ? PlayerUtils::getPlayerByPrefix($args["name"]) : null;
        $amount = isset($args["amount"]) ? $args["amount"] : null;
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");

        if ($player === null) {
            $sender->sendMessage(C::RED . "Error: " . C::DARK_RED . "Player not found.");
            return;
        } 
        
        $session = Loader::getPlayerManager()->getSession($player);
        if($session === null) {
            $sender->sendMessage(C::RED . "Error: " . C::DARK_RED . "Player session not found.");
            return;
        }
        
        $amount = (int)$args["amount"];
        if($amount < 0) {
            $sender->sendMessage(C::RED . "Error: " . C::DARK_RED . "Invalid amount.");
            return;
        }
        
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $currency = $config->getNested("settings.economy.currency-symbol");

        $session->subtractBalance($amount);
        $formattedAmount = number_format($amount);
        $newBalance = number_format($session->getBalance());
        $sender->sendMessage(C::colorize("&r&e{$currency}{$formattedAmount} has been taken from " . $player->getName() . "'s account. New Balance: &e{$currency}{$newBalance}"));
        $player->sendMessage(C::colorize("&r&e{$currency}{$formattedAmount} has been taken from your account."));
    }

    public function getPermission(): string {
        return "aethelis.eco.take";
    }
}