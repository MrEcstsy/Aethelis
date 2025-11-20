<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions;

use ecstsy\AethelisCore\factions\flags\FactionFlag;
use ecstsy\AethelisCore\factions\flags\FlagFactory;
use ecstsy\AethelisCore\factions\permissions\FactionPermission;
use ecstsy\AethelisCore\factions\permissions\PermissionFactory;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\QueryStmts;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class FactionManager {

    use SingletonTrait;

    /** @var Faction[] */
    private array $factions = [];

    public function __construct()
    {
        self::setInstance($this);
        $this->loadFactions();
    }

    /**
     * Load all faction data from the database into the $factions array.
     */
    private function loadFactions(): void
    {
        Loader::getDatabase()->executeSelect(QueryStmts::FACTIONS_SELECT, [], function (array $rows): void {
            foreach ($rows as $row) {
                $membersData = json_decode($row["members"], true) ?: [];
                $members = array_map(fn($member) => Uuid::fromString($member), $membersData);
                $memberNames = json_decode($row["member_names"], true) ?: [];

                $faction = new Faction(
                    $row["faction_id"],
                    $row["name"],
                    $row["description"],
                    $row["motd"],
                    (int)$row["bank"],
                    (int)$row["value"],
                    $row["upgrades"],
                    $row["leader"] !== null ? Uuid::fromString($row["leader"]) : null,
                    $row['leader_name'],
                    $members,
                    $memberNames,
                    $row["relations"],
                    json_decode($row["permissions"], true),
                    json_decode($row["flags"], true)
                );
                $this->factions[$row["faction_id"]] = $faction;
            }
        });
    }
    
    /**
     * Create a new faction.
     *
     * @param string $factionId
     * @param string $name
     * @param string $description
     * @param string $motd
     * @param UuidInterface|null $leader
     * @return Faction
     */
    public function createFaction(
        string $factionId,
        string $name,
        string $description,
        string $motd,
        ?UuidInterface $leader
    ): Faction {
        $initialMembers = $leader ? [$leader] : [];
        $memberNames = [];
        $leaderName = '';

        if ($leader !== null) {
            $player = Server::getInstance()->getPlayerByUUID($leader);
            $leaderName = $player ? $player->getName() : 'Error getting leader name';
            $memberNames[$leader->toString()] = $leaderName;
        }

        $permissionsArray = array_map(
            fn($p) => $p->getAllowedRoles(),
            PermissionFactory::getPermissions()
        );

        $flagsArray = array_map(
            fn($f) => $f->getDefaultValue(),
            FlagFactory::getFlags()
        );

        $faction = new Faction(
            $factionId,
            $name,
            $description,
            $motd,
            0,      
            0,      
            "{}",   
            $leader,
            $leaderName,
            $initialMembers,
            $memberNames,
            "{}",
            $permissionsArray,
            $flagsArray
        );
        $this->factions[$factionId] = $faction;

        Loader::getDatabase()->executeInsert(QueryStmts::FACTIONS_CREATE, [
            "faction_id" => $factionId,
            "name" => $name,
            "description" => $description,
            "motd" => $motd,
            "bank" => 0,
            "value" => 0,
            "upgrades" => "{}",
            "leader" => $leader ? $leader->toString() : null,
            "leader_name" => $leaderName,
            "members" => json_encode(array_map(fn($member) => $member->toString(), $initialMembers)),
            "member_names" => json_encode($memberNames),
            "relations" => "{}",
            "permissions" => json_encode($permissionsArray),
            "flags" => json_encode($flagsArray)
        ]);

        $leaderSession = Loader::getPlayerManager()->getSessionByUuid($leader);
        $leaderSession->setFactionRole("leader");

        return $faction;
    }

    /**
     * Get a faction by its ID.
     */
    public function getFaction(string $factionId): ?Faction
    {
        return $this->factions[$factionId] ?? null;
    }

    /**
     * Delete a faction.
     */
    public function deleteFaction(string $factionId): void
    {
        unset($this->factions[$factionId]);
        Loader::getDatabase()->executeChange(QueryStmts::FACTIONS_DELETE, ["faction_id" => $factionId]);
    }

    /**
     * Get all factions.
     *
     * @return Faction[]
     */
    public function getAllFactions(): array
    {
        return $this->factions;
    }

    public function calculateFactionPower(string $factionId): int {
        $faction = $this->getFaction($factionId);
        if ($faction === null) {
            return 0;
        }
        $totalPower = 0;
        foreach ($faction->getMembers() as $memberUuid) {
            $session = Loader::getPlayerManager()->getSessionByUuid($memberUuid);
            if ($session !== null) {
                $totalPower += $session->getFactionPower();
            }
        }
        return $totalPower;
    }
    
}
