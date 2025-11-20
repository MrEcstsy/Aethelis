<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\player;

use ecstsy\AethelisCore\factions\Faction;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\QueryStmts;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class PlayerManager
{
    use SingletonTrait;

    /** @var AethelisPlayer[] */
    private array $sessions; 

    public function __construct(
        public Loader $plugin
    ){
        self::setInstance($this);

        $this->loadSessions();
    }

    /**
     * Store all player data in $sessions property
     *
     * @return void
     */
    private function loadSessions(): void
    {
        Loader::getDatabase()->executeSelect(QueryStmts::PLAYERS_SELECT, [], function (array $rows): void {
            foreach ($rows as $row) {
                $this->sessions[$row["uuid"]] = new AethelisPlayer(
                    Uuid::fromString($row["uuid"]),
                    $row["username"],
                    $row["balance"],
                    $row["cooldowns"],
                    $row["faction_id"],
                    $row["faction_role"],
                    $row["power"],
                    $row["settings"]
                );
            }
        });
    }

    /**
     * Create a session
     *
     * @param Player $player
     * @return AethelisPlayer
     * @throws \JsonException
     */
    public function createSession(Player $player): AethelisPlayer
    {
        $args = [
            "uuid" => $player->getUniqueId()->toString(),
            "username" => $player->getName(),
            "balance" => GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml")->getNested("settings.economy.starting-balance"),
            "cooldowns" => "{}",
            "faction_id" => null,
            "faction_role" => null,
            "power" => 100,
            "settings" => json_encode([
                'chest_inventories' => true, 'broadcasts' => true, 'loot_announcer' => true
            ]),
        ];

        Loader::getDatabase()->executeInsert(QueryStmts::PLAYERS_CREATE, $args);

        $this->sessions[$player->getUniqueId()->toString()] = new AethelisPlayer(
            $player->getUniqueId(),
            $args["username"],
            $args["balance"],
            $args["cooldowns"],
            $args["faction_id"],
            $args["faction_role"],
            $args["power"],
            $args["settings"]

        );
        return $this->sessions[$player->getUniqueId()->toString()];
    }

    /**
     * Get session by player object
     *
     * @param Player $player
     * @return AethelisPlayer|null
     */
    public function getSession(Player $player) : ?AethelisPlayer
    {
        return $this->getSessionByUuid($player->getUniqueId());
    }

    /**
     * Get session by player name
     *
     * @param string $name
     * @return AethelisPlayer|null
     */
    public function getSessionByName(string $name) : ?AethelisPlayer
    {
        foreach ($this->sessions as $session) {
            if (strtolower($session->getUsername()) === strtolower($name)) {
                return $session;
            }
        }
        return null;
    }

    /**
     * Get session by UuidInterface
     *
     * @param UuidInterface $uuid
     * @return AethelisPlayer|null
     */
    public function getSessionByUuid(UuidInterface $uuid) : ?AethelisPlayer
    {
        return $this->sessions[$uuid->toString()] ?? null;
    }

    public function destroySession(AethelisPlayer $session) : void
    {
        Loader::getDatabase()->executeChange(QueryStmts::PLAYERS_DELETE, ["uuid", $session->getUuid()->toString()]);

        # Remove session from the array
        unset($this->sessions[$session->getUuid()->toString()]);
    }

    public function getSessions() : array
    {
        return $this->sessions;
    }

    public function areAlliedOrTruced(Player $p1, Player $p2): bool {
        $session1 = $this->getSession($p1);
        $session2 = $this->getSession($p2);
        $faction1 = $session1->getFaction();
        $faction2 = $session2->getFaction();
        if ($faction1 === null || $faction2 === null) return false;
        
        if ($faction1->getFactionId() === $faction2->getFactionId()) {
            return true;
        }
        
        $relation = $faction1->getRelation($faction2);
        return in_array($relation, ["ally", "truce"], true);
    }
}