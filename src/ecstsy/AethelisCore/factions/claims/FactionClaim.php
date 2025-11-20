<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\claims;

use ecstsy\AethelisCore\factions\Faction;
use ecstsy\AethelisCore\factions\FactionManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\QueryStmts;
use pocketmine\Server;
use pocketmine\world\World;

final class FactionClaim {


    /**
     * @param string $claimId Unique claim identifier
     * @param string $factionId The faction's ID that owns this claim
     * @param string $worldName The world where the claim is located
     * @param int $chunkX The chunk X coordinate (derived from block coordinates)
     * @param int $chunkZ The chunk Z coordinate (derived from block coordinates)
     * @param int $y The Y coordinate (if needed, for multi-level claims)
     * @param string $settings Additional JSON settings for the claim (e.g. claim size, flags)
     */
    public function __construct(private string $faction, private int $chunkX, private int $chunkZ, private string $world, private string $zoneType = "faction") {
    }

    public function getFaction(): ?Faction {
        return FactionManager::getInstance()->getFaction($this->faction);
    }

    public function setFaction(Faction $faction): void {
        $this->faction = $faction->getFactionId();
        Loader::getInstance()->getDatabase()->executeChange(QueryStmts::CLAIMS_UPDATE, ["chunkX" => $this->chunkX, "chunkZ" => $this->chunkZ, "world_name" => $this->world, "faction_id" => $this->faction]);
    }

    public function getWorld(): ?World {
        return Server::getInstance()->getWorldManager()->getWorldByName($this->world);
    }

    public function getChunkX(): int {
        return $this->chunkX;
    }

    public function getChunkZ(): int {
        return $this->chunkZ;
    }

    public function getZoneType(): string {
        return $this->zoneType;
    }

    /**
     * Check if a given block position (x, z) is within this claim's chunk.
     */
    public function containsPosition(float $x, float $z): bool {
        $posChunkX = (int)floor($x) >> 4;
        $posChunkZ = (int)floor($z) >> 4;
        return ($this->chunkX === $posChunkX) && ($this->chunkZ === $posChunkZ);
    }
}
