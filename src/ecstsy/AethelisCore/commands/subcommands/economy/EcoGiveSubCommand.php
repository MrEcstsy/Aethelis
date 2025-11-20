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

final class EcoGiveSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", false));
        $this->registerArgument(1, new IntegerArgument("amount", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $player = isset($args["name"]) ? PlayerUtils::getPlayerByPrefix($args["name"]) : null;
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");

        if ($player === null) {
            $sender->sendMessage(C::RED . "Error: " . C::DARK_RED . "Player not found.");
            return;
        }
        
        $amount = $args["amount"] ?? null;
        if($amount === null) {
            $sender->sendMessage(C::RED . "Error: " . C::DARK_RED . "Invalid amount.");
            return;
        }
        
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $maxMoney = (int)$config->getNested("settings.economy.max-money");

        $recipientSession = Loader::getPlayerManager()->getSession($player);
        if(($recipientSession->getBalance() + $amount) > $maxMoney) {
            $sender->sendMessage(C::RED . "Error: " . C::DARK_RED . "Adding this amount would exceed the recipient's balance limit.");
            return;
        }
        
        $recipientSession->addBalance($amount);
        $currency = $config->getNested("settings.economy.currency-symbol");
        $formattedAmount = number_format($amount);
        $newBalance = number_format($recipientSession->getBalance());
        $sender->sendMessage(C::colorize("&r&a{$currency}{$formattedAmount} has been added to " . $player->getName() . "'s account. New Balance: {$currency}{$newBalance}"));
}

    public function getPermission(): string {
        return "aethelis.eco.give";
    }
}