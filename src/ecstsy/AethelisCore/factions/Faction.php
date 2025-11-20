<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions;

use ecstsy\AethelisCore\factions\flags\FactionFlag;
use ecstsy\AethelisCore\factions\flags\FlagFactory;
use ecstsy\AethelisCore\factions\permissions\FactionPermission;
use ecstsy\AethelisCore\factions\permissions\PermissionFactory;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\QueryStmts;
use Ramsey\Uuid\UuidInterface;
use pocketmine\utils\TextFormat;

final class Faction {

    /** @var UuidInterface[] */
    private array $members = [];

    /** @var array<string, string> */
    private array $relations = [];

    private ?string $leaderName;

    private array $memberNames = [];

    /**
     * @param string $factionId Unique identifier for the faction (could be a name or generated ID)
     * @param string $name The faction's display name
     * @param string $description A brief description of the faction
     * @param string $motd Message of the day for the faction
     * @param int $bank The faction's bank balance
     * @param int $value Overall faction value (e.g., for upgrades or ranking)
     * @param string $upgrades JSON string for faction upgrades
     * @param UuidInterface|null $leader UUID of the faction leader (nullable)
     * @param UuidInterface[] $members Array of member UUIDs
     * @param string $relations JSON string for faction relations
     * @param array $permissions Array of allowed roles for each permission
     * @param array $flags Array of default values for each flag
     */
    public function __construct(
        private string $factionId,
        private string $name,
        private string $description,
        private string $motd,
        private int $bank,
        private int $value,
        private string $upgrades,
        private ?UuidInterface $leader,
        ?string $leaderName,
        array $members,
        array $memberNames,
        string $relations,
        private array $permissions,
        private array $flags
    ) {
        $this->members = $members;
        $this->memberNames = $memberNames;
        $this->relations = json_decode($relations, true) ?: [];
        $this->leaderName = $leaderName;

    }

    public function getFactionId(): string {
        return $this->factionId;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getMotd(): string {
        return $this->motd;
    }

    public function getBank(): int {
        return $this->bank;
    }

    public function addToBank(int $amount): void {
        $this->bank += $amount;
        $this->updateDb();
    }

    public function subtractFromBank(int $amount): void {
        $this->bank -= $amount;
        $this->updateDb();
    }

    public function getValue(): int {
        return $this->value;
    }

    public function setValue(int $value): void {
        $this->value = $value;
        $this->updateDb();
    }

    public function getUpgrades(): string {
        return $this->upgrades;
    }

    public function setUpgrades(string $upgrades): void {
        $this->upgrades = $upgrades;
        $this->updateDb();
    }

    public function getLeader(): ?UuidInterface {
        return $this->leader;
    }

    public function getLeaderName(): ?string {
        return $this->leaderName;
    }

    public function setLeader(?UuidInterface $leader): void {
        $this->leader = $leader;
        $this->updateDb();
    }

    public function setLeaderName(?string $leaderName): void {
        $this->leaderName = $leaderName;
        $this->updateDb();
    }

    /**
     * @return UuidInterface[]
     */
    public function getMembers(): array {
        return $this->members;
    }

    public function addMember(UuidInterface $uuid): void {
        if (!in_array($uuid, $this->members, true)) {
            $this->members[] = $uuid;
            $this->updateDb();
        }
    }

    public function removeMember(UuidInterface $uuid): void {
        $this->members = array_filter($this->members, fn($member) => $member !== $uuid);
        $this->updateDb();
    }

    /**
     * @return array<string, string>
     */
    public function getMemberNames(): array {
        return $this->memberNames;
    }

    /**
     * Update (or add) a member's stored name.
     */
    public function updateMemberName(UuidInterface $uuid, string $name): void {
        $this->memberNames[$uuid->toString()] = $name;
        $this->updateDb();
    }

    public function getRelation(Faction $otherFaction): string {
        return $this->relations[$otherFaction->getFactionId()] ?? 'neutral';
    }
    
    public function setRelation(Faction $otherFaction, string $relation): void {
        $this->relations[$otherFaction->getFactionId()] = $relation;
        $this->updateDb();
    }

    public function revokeRelation(Faction $otherFaction): void {
        if (isset($this->relations[$otherFaction->getFactionId()])) {
            unset($this->relations[$otherFaction->getFactionId()]);
            $this->updateDb();
        }
    }

    public function hasPermission(string $permission, string $role): bool {
        $role = strtolower($role);
        $allowedRoles = $this->permissions[$permission] ?? [];
        foreach ($allowedRoles as $allowedRole) {
            if (strtolower($allowedRole) === $role) {
                return true;
            }
        }
        return false;
    }
    
    public function getFlag(string $flag): bool {
        return $this->flags[$flag] ?? false;
    }
    
    public function setFlag(string $flag, bool $value): void {
        $this->flags[$flag] = $value;
        $this->updateDb();
    }

    public function isEnemy(Faction $otherFaction): bool {
        return (isset($this->relations[$otherFaction->getFactionId()]) 
                && $this->relations[$otherFaction->getFactionId()] === "enemy");
    }    
    
    public function getAllyCount(): int {
        $count = 0;
        foreach ($this->relations as $relation) {
            if ($relation === "ally") {
                $count++;
            }
        }
        return $count;
    }

    public function updateDb(): void {
        Loader::getDatabase()->executeChange(QueryStmts::FACTIONS_UPDATE, [
            "faction_id"    => $this->factionId,
            "name"          => $this->name,
            "description"   => $this->description,
            "motd"          => $this->motd,
            "bank"          => $this->bank,
            "value"         => $this->value,
            "upgrades"      => $this->upgrades,
            "leader"        => $this->leader !== null ? $this->leader->toString() : null,
            "leader_name"   => $this->leaderName,
            "members"       => json_encode($this->members),
            "member_names"  => json_encode($this->memberNames),
            "relations"     => json_encode($this->relations),
            "permissions"   => json_encode($this->permissions),
            "flags"         => json_encode($this->flags),
        ]);
    }    
}
