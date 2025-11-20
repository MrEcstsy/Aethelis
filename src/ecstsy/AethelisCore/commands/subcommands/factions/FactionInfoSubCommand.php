<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class FactionInfoSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("target", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $target = $args['target'] ?? null;

        if ($target === null) {
            $session = Loader::getPlayerManager()->getSession($sender);
            $faction = $session?->getFaction();

            if ($faction === null) {
                $sender->sendMessage(C::colorize("&r&4Error: &cYou are not in a faction!"));
                return;
            }

            Utils::sendFactionInfo($sender, $faction);
            return;
        }

        $faction = Loader::getFactionManager()->getFaction(strtolower($target));

        if ($faction === null) {
            $player = PlayerUtils::getPlayerByPrefix($target);
            if ($player !== null) {
                $session = Loader::getPlayerManager()->getSession($player);
            } else {
                $session = Loader::getPlayerManager()->getSessionByName($target);
            }

            $faction = $session?->getFaction();
            if ($faction === null) {
                $sender->sendMessage(C::colorize("&r&4Error: &cNo faction found for '&7" . $target . "&c'"));
                return;
            }
        }

        Utils::sendFactionInfo($sender, $faction);
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}