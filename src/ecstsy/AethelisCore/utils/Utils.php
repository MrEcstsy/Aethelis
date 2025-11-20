<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\utils;

use ecstsy\AethelisCore\factions\claims\ClaimManager;
use ecstsy\AethelisCore\factions\claims\FactionClaim;
use ecstsy\AethelisCore\factions\Faction;
use ecstsy\AethelisCore\factions\permissions\FactionPermission;
use ecstsy\AethelisCore\listeners\EventListener;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\player\PlayerManager;
use ecstsy\Glacia\server\factions\claims\FactionClaim as ClaimsFactionClaim;
use IvanCraft623\RankSystem\RankSystem;
use IvanCraft623\RankSystem\session\Session;
use IvanCraft623\RankSystem\tag\Tag;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\Position;

final class Utils {

    public static function getFactionRoleSymbol(?string $role): string {
        if ($role === null) {
            return "";
        }

        switch ($role) {
            case "leader":
                return "***"; 
            case "officer":
                return "**"; 
            case "member":
                return ""; 
            case "recruit":
                return "-"; 
            default:
                return ""; 
        }
    } 

    /**
     * Returns a color code based on a relation string.
     *
     * @param string $relation Expected values: "own", "ally", "enemy", "neutral"
     * @return string The TextFormat color code.
     */
    public static function getRelationColor(string $relation): string {
        switch (strtolower($relation)) {
            case "neutral":
                return C::WHITE;
            case "warzone":
                return C::DARK_RED;
            case "safezone":
                return C::GREEN;
            case "ally":
                return C::LIGHT_PURPLE;
            case "enemy":
                return C::RED;
            case "own":
                return C::GREEN;
            default:
                return C::WHITE;
        }
    }

    public static function handleClaimTitle(Player $player, ?ClaimsFactionClaim $claim): void {
        if ($claim === null) {
            self::sendWildernessTitle($player);
        } else {
            self::sendFactionTitle($player, $claim);
        }
    }

    public static function sendFactionTitle(Player $player, FactionClaim $claim): void {
        $faction = $claim->getFaction();

        if ($faction === null) {
            self::sendWildernessTitle($player);
            return;
        }

        $session = Loader::getPlayerManager()->getSession($player);

        if ($session === null) {
            return;
        }

        $playerFaction = $session->getFaction();

        if ($playerFaction === null) {
            self::sendWildernessTitle($player);
            return;
        }

        $relation = $playerFaction->getRelation($faction);

        if ($playerFaction->getFactionId() === $faction->getFactionId()) {
            $relation = "own";
        }

        $color = self::getRelationColor($relation);
        $title = $color . $faction->getName();
        $subtitle = C::WHITE . $faction->getDescription();
        $player->sendTitle(C::colorize($title), C::colorize($subtitle), 10, 40, 10);
    }

    public static function sendWildernessTitle(Player $player): void {
        $player->sendTitle(C::colorize("&l&2WILDERNESS"), C::colorize("&7PvP: &aEnabled"), 10, 40, 10);
    }

    public static function handleSpecialZone(Player $player, string $zoneType): void {
        $titles = [
            "safezone" => ["&l&aSAFE ZONE", "&7PvP: &cDisabled"],
            "warzone" => ["&l&4WAR ZONE", "&7PvP: &4Enforced"],
        ];
    
        if (isset($titles[$zoneType])) {
            $player->sendTitle(
                C::colorize($titles[$zoneType][0]),
                C::colorize($titles[$zoneType][1]),
                10, 40, 10
            );
        }
    }

    public static function getClaimKey(FactionClaim $claim): string {
        return $claim->getChunkX() . ":" . $claim->getChunkZ() . ":" . $claim->getWorld()->getFolderName();
    }

    public static function registerCustomRankTags(): void {
        $rankSystem = RankSystem::getInstance();
        $tagManager = $rankSystem->getTagManager();

        $tagManager->registerTag(new Tag("faction_name", static function(Session $session): string {
            $aethelisSession = Loader::getPlayerManager()->getSession($session->getPlayer());
            $faction = $aethelisSession->getFaction();

            if ($faction === null) {
                return "";
            }

            $factionName = $faction->getName();

            if ($factionName === null) {
                return "";
            }

            return $factionName;
        }));

        $tagManager->registerTag(new Tag("faction_rank", static function(Session $session): string {
            $aethelisSession = Loader::getPlayerManager()->getSession($session->getPlayer());
            return Utils::getFactionRoleSymbol($aethelisSession->getFactionRole());
        }));
    }

    public static function canBuildHere(Player $player, Position $pos, string $permissionType = "build"): bool {
        $session = Loader::getPlayerManager()->getSession($player);
        if ($session === null || $session->getFaction() === null) {
            return false;
        }

        $claim = ClaimManager::getInstance()->getClaimAtPosition($pos);

        if ($claim === null) {
            return true;
        }

        if ($claim->getFaction() !== null && $claim->getFaction()->getFactionId() === $session->getFaction()->getFactionId()) {
            return $session->getFaction()->hasPermission($permissionType, $session->getFactionRole());
        }

        return false;
    }

    public static function canAffectArea(Player $player, Position $position, string $permissionType = FactionPermission::BUILD): bool {
        $session = Loader::getPlayerManager()->getSession($player);
        if ($session?->isInAdminMode()) return true;
    
        $claim = ClaimManager::getInstance()->getClaimAtPosition($position);
        if (!$claim) return true; 
    
        $faction = $claim->getFaction();
        $playerFaction = $session?->getFaction();
    
        Loader::getInstance()->getLogger()->debug("Claim flag safezone: " . var_export($faction->getFlag('safezone'), true));
        Loader::getInstance()->getLogger()->debug("Player faction role: " . strtolower($session->getFactionRole()));

        if ($faction->getFlag('safezone')) return false;
        if ($faction->getFlag('warzone')) return true;
    
        if ($playerFaction && $playerFaction->getFactionId() === $faction->getFactionId()) {
            $role = strtolower($session->getFactionRole());
            return $faction->hasPermission($permissionType, $role);
        }
    
        if ($playerFaction && $faction->getRelation($playerFaction) === 'ally') {
            return $faction->hasPermission($permissionType, 'ally');
        }
    
        return false;
    }

    public static function sendFactionInfo(Player $sender, Faction $faction): void
    {
        $leaderName = $faction->getLeaderName() ?? "Unknown";
        $description = $faction->getDescription();
        $value = number_format($faction->getValue());
        $bank = number_format($faction->getBank());
    
        $storedMemberNames = $faction->getMemberNames();
        $memberNamesDisplay = [];
        $onlineMembers = 0;
    
        foreach ($faction->getMembers() as $memberUuid) {
            $uuidStr = $memberUuid->toString();
            $storedName = $storedMemberNames[$uuidStr] ?? substr($uuidStr, 0, 8);
    
            $player = Server::getInstance()->getPlayerExact($storedName);
            $session = $player !== null
                ? Loader::getPlayerManager()->getSession($player)
                : Loader::getPlayerManager()->getSessionByName($storedName);
    
            $role = $session?->getFactionRole();
            $roleSymbol = self::getFactionRoleSymbol($role);
    
            if ($player !== null) {
                $realName = $player->getName();
                $faction->updateMemberName($memberUuid, $realName);
                $memberNamesDisplay[] = "&a" . $roleSymbol . $realName; 
                $onlineMembers++;
            } else {
                $memberNamesDisplay[] = "&c" . $roleSymbol . $storedName; 
            }
        }
    
        $memberCount = count($memberNamesDisplay);
        $memberList = implode(", ", $memberNamesDisplay);
    
        $messages = [
            "&r&l&6" . str_repeat("-", 10) . " &7[ &e" . $faction->getName() . " &7] &r&l&6" . str_repeat("-", 10),
            "  &r&7* &6Leader: &f" . $leaderName,
            "  &r&7* &6Description: &f" . $description,
            "  &r&7* &6Members &7[&f$onlineMembers&7/&f$memberCount&7]&6:",
            "&r&f" . $memberList,
            "  &r&7* &6Value: &f" . $value,
            "  &r&7* &6Bank: &f$" . $bank,
        ];
    
        foreach ($messages as $message) {
            $sender->sendMessage(C::colorize($message));
        }
    }

    public static function combatTask(): void {
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $lang = Loader::getLanguageManager();
                $now = microtime(true);
                $exitMessage = C::colorize($lang->getNested("combat.exit-combat"));

                foreach (EventListener::$combatPlayers as $player) {
                    if (EventListener::$combatPlayers[$player] <= $now) {
                        EventListener::$combatPlayers->detach($player);
                        $player->sendMessage($exitMessage);
                    }
                }
            }
        ), 20);
    }

    public static function broadcastRelationChange(Faction $factionA, Faction $factionB, string $relationText): void {
        $server = Loader::getInstance()->getServer();

        foreach ($factionA->getMembers() as $uuid) {
            $player = $server->getPlayerByUUID($uuid);

            if ($player !== null) {
                $player->sendMessage(C::colorize(str_replace(["{faction}"], [$factionB->getName()], $relationText)));
            }
        }

        foreach ($factionB->getMembers() as $uuid) {
            $player = $server->getPlayerByUUID($uuid);

            if ($player !== null) {
                $player->sendMessage(C::colorize(str_replace(["{faction}"], [$factionA->getName()], $relationText)));
            }
        }
    }
}