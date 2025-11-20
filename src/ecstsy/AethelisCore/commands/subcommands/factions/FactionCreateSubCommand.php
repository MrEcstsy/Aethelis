<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands\subcommands\factions;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AethelisCore\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class FactionCreateSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);

        $factionName = trim($args['name'] ?? null);

        if ($factionName === null) {
            $sender->sendMessage(C::RED . 'Faction name cannot be empty!');
            return;
        }

        $factionId = strtolower($factionName);

        $existingFaction = Loader::getFactionManager()->getFaction($factionId);

        if ($existingFaction !== null) {
            $sender->sendMessage(C::RED . "A faction with the name '{$factionName}' already exists");
            return;
        }

        if ($session->getFaction() !== null) {
            $sender->sendMessage(C::colorize("&r&cYou already have a faction!"));
            return;
        }

        $description = "default description :(";
        $motd = "Welcome to " . $factionName . "!";

        Loader::getFactionManager()->createFaction($factionId, $factionName, $description, $motd, $sender->getUniqueId());

        $session->setFaction($factionId);

        $sender->sendMessage(C::colorize("&r&aFaction '{$factionName}' created!"));
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}