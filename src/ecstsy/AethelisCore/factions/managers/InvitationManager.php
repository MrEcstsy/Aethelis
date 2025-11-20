<?php
declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\managers;

use ecstsy\AethelisCore\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

final class InvitationManager {

    /**
     * @var array<string, array{factionId: string, expiry: int, sender: string}>
     */
    public static array $invitations = [];

    /**
     * Send an invitation from $sender to $target for faction $factionId.
     * $duration is in seconds.
     */
    public static function sendInvitation(Player $sender, Player $target, string $factionId, int $duration): void {
        $expiry = time() + $duration;
        $targetUUID = $target->getUniqueId()->toString();
        self::$invitations[$targetUUID] = [
            "factionId" => $factionId,
            "expiry" => $expiry,
            "sender" => $sender->getName()
        ];

        $formattedTime = GeneralUtils::translateTime($duration);
        $sender->sendMessage("§aYou have invited {$target->getName()} to join your faction. They have $formattedTime to accept.");
        $target->sendMessage("§eYou have been invited to join faction '$factionId' by {$sender->getName()}. Type §b/f accept {$factionId}§e to join.");

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(
            new class($targetUUID) extends Task {
                private string $targetUUID;
                public function __construct(string $targetUUID) {
                    $this->targetUUID = $targetUUID;
                }
                public function onRun(): void {
                    if (isset(InvitationManager::$invitations[$this->targetUUID])) {
                        $invite = InvitationManager::$invitations[$this->targetUUID];
                        unset(InvitationManager::$invitations[$this->targetUUID]);
                        $player = Loader::getInstance()->getServer()->getPlayerByRawUUID($this->targetUUID);
                        if ($player !== null) {
                            $player->sendMessage("§cYour invitation to join faction '{$invite["factionId"]}' has expired.");
                        }
                    }
                }
            },
            $duration * 20
        );
    }

    /**
     * Get a pending invitation for the given player.
     * Returns an array with keys: factionId, expiry, sender, or null if none.
     */
    public static function getInvitation(Player $player): ?array {
        $uuid = $player->getUniqueId()->toString();
        if (isset(self::$invitations[$uuid]) && self::$invitations[$uuid]["expiry"] >= time()) {
            return self::$invitations[$uuid];
        }
        return null;
    }

    /**
     * Remove an invitation for the given player.
     */
    public static function removeInvitation(Player $player): void {
        $uuid = $player->getUniqueId()->toString();
        unset(self::$invitations[$uuid]);
    }
}
