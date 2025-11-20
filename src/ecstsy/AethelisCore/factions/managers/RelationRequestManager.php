<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\managers;

use ecstsy\AethelisCore\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

final class RelationRequestManager {

    /**
     * @var array<string, array{factionId: string, expiry: int, sender: string, type: string}>
     */
    public static array $requests = [];

    /**
     * Send a relation request from $sender to $target.
     *
     * @param Player $sender
     * @param Player $target
     * @param string $factionId The ID of the sender’s faction.
     * @param int $duration Duration in seconds for the request to be valid.
     * @param string $type The type of relation request ("ally" or "enemy").
     */
    public static function sendRequest(Player $sender, Player $target, string $factionId, int $duration, string $type): void {
        $expiry = time() + $duration;
        $targetUUID = $target->getUniqueId()->toString();
        self::$requests[$targetUUID] = [
            "factionId" => $factionId,
            "expiry" => $expiry,
            "sender" => $sender->getName(),
            "type" => $type
        ];

        $formattedTime = GeneralUtils::translateTime($duration);
        $sender->sendMessage("§aYou have sent a {$type} request to {$target->getName()}. They have $formattedTime to accept.");
        $target->sendMessage("§eYou have received a {$type} request from faction '$factionId' by {$sender->getName()}. Type §d/f {$type}accept§e to respond.");

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(
            new class($targetUUID) extends Task {
                private string $targetUUID;
                public function __construct(string $targetUUID) {
                    $this->targetUUID = $targetUUID;
                }
                public function onRun(): void {
                    if (isset(RelationRequestManager::$requests[$this->targetUUID])) {
                        $request = RelationRequestManager::$requests[$this->targetUUID];
                        unset(RelationRequestManager::$requests[$this->targetUUID]);
                        $player = Loader::getInstance()->getServer()->getPlayerByRawUUID($this->targetUUID);
                        if ($player !== null) {
                            $player->sendMessage("§cYour {$request["type"]} request has expired.");
                        }
                    }
                }
            },
            $duration * 20
        );
    }

    /**
     * Get a pending relation request for the given player and type.
     *
     * @param Player $player
     * @param string $type
     * @return array{factionId: string, expiry: int, sender: string, type: string}|null
     */
    public static function getRequest(Player $player, string $type): ?array {
        $uuid = $player->getUniqueId()->toString();
        if (isset(self::$requests[$uuid]) && self::$requests[$uuid]["expiry"] >= time() && self::$requests[$uuid]["type"] === $type) {
            return self::$requests[$uuid];
        }
        return null;
    }

    /**
     * Remove a pending relation request for the given player and type.
     *
     * @param Player $player
     * @param string $type
     */
    public static function removeRequest(Player $player, string $type): void {
        $uuid = $player->getUniqueId()->toString();
        if (isset(self::$requests[$uuid]) && self::$requests[$uuid]["type"] === $type) {
            unset(self::$requests[$uuid]);
        }
    }
}
