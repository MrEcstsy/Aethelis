<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionAcceptSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionAllyAcceptSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionAllySubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionChatSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionClaimSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionCreateSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionDisbandSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionEnemySubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionInfoSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionInviteSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionKickSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionNeutralSubCommand;
use ecstsy\AethelisCore\commands\subcommands\factions\FactionUnclaimSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FactionCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new FactionCreateSubCommand($this->plugin, "create", "Creates a new faction"));
        $this->registerSubCommand(new FactionInfoSubCommand($this->plugin, "info", "Displays information about a faction", ["whois", "who"]));
        $this->registerSubCommand(new FactionClaimSubCommand($this->plugin, "claim", "Claims a chunk"));
        $this->registerSubCommand(new FactionInviteSubCommand($this->plugin, "invite", "Invites a player to a faction"));
        $this->registerSubCommand(new FactionAcceptSubCommand($this->plugin, "accept", "Accepts an invitation", ['join']));
        $this->registerSubCommand(new FactionKickSubCommand($this->plugin, "kick", "Kicks a player from a faction"));
        $this->registerSubCommand(new FactionDisbandSubCommand($this->plugin, "disband", "Disbands a faction"));
        $this->registerSubCommand(new FactionAllySubCommand($this->plugin, "ally", "send an alliance request to another faction"));
        $this->registerSubCommand(new FactionAllyAcceptSubCommand($this->plugin, "allyaccept", "accept an alliance request"));
        $this->registerSubCommand(new FactionEnemySubCommand($this->plugin, "enemy", "enemy another faction"));
        $this->registerSubCommand(new FactionNeutralSubCommand($this->plugin, "neutral", "neutral another faction"));
        $this->registerSubCommand(new FactionChatSubCommand($this->plugin, "chat", "Chat with your faction or allies!", ["c"]));
        $this->registerSubCommand(new FactionUnclaimSubCommand($this->plugin, "unclaim", "Unclaims a chunk"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $sender->sendMessage("Faction Usage!");
    }

    public function getPermission(): string {
        return "aethelis.default";
    }
}