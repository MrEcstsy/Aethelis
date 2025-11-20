<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\factions\claims;

use ecstsy\AethelisCore\factions\Faction;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\QueryStmts;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;
use pocketmine\world\World;

final class ClaimManager {
    use SingletonTrait;

    /** @var FactionClaim[] */
    private array $claims = [];

    public function __construct() {
        self::setInstance($this);
        $this->loadClaims();
    }

    /**
     * Load all claim data from the database into the $claims array.
     */
    public function loadClaims(): void {
        Loader::getDatabase()->executeSelect(QueryStmts::CLAIMS_SELECT, [], function(array $rows): void {
            foreach ($rows as $row) {
                $claim = new FactionClaim(
                    $row['faction_id'],
                    $row['chunkX'],
                    $row['chunkZ'],
                    $row['world_name']
                );
                $this->claims[$row['chunkX'] . ":" . $row['chunkZ'] . ":" . $row['world_name']] = $claim;
            }
        });
    }

    /**
     * Create a new claim for a faction at the given chunk.
     *
     * @param string $factionId The faction's ID claiming the land.
     * @param string $worldName The world where the claim is made.
     * @param int $blockX The player's block x coordinate.
     * @param int $blockY The player's block y coordinate.
     * @param int $blockZ The player's block z coordinate.
     * @param string $settings Additional JSON settings (e.g., claim size, flags)
     * @return FactionClaim
     */
    public function createClaim(Faction $faction, World $world, int $chunkX, int $chunkZ): FactionClaim {
        $worldName = $world->getFolderName();
        
        $chunkKey = $chunkX . ":" . $chunkZ . ":" . $worldName;
        if (isset($this->claims[$chunkKey])) {
            throw new \RuntimeException("This chunk is already claimed!");
        }
    
        Loader::getDatabase()->executeInsert(QueryStmts::CLAIMS_CREATE, [
            "world_name" => $worldName,
            "chunkX" => $chunkX,
            "chunkZ" => $chunkZ,
            "faction_id" => $faction->getFactionId()
        ]);
    
        $claim = new FactionClaim(
            $faction->getFactionId(),
            $chunkX,
            $chunkZ,
            $worldName
        );
        
        $this->claims[$chunkKey] = $claim;
        return $claim;
    }

    public function getClaim(int $chunkX, int $chunkZ, string $worldName): ?FactionClaim {
        return $this->claims[$chunkX . ":" . $chunkZ . ":" . $worldName] ?? null;
    } 

    /**
     * Get the claim at the given player's position (if any).
     */
    public function getClaimAtPosition(Position $position): ?FactionClaim {
        return $this->getClaim($position->getFloorX() >> 4, $position->getFloorZ() >> 4, $position->getWorld()->getFolderName());
    }

    /**
     * Get all claims for a given faction.
     *
     * @param string $factionId
     * @return FactionClaim[]
     */
    public function getClaimsForFaction(string $factionId): array {
        return array_filter($this->claims, function(FactionClaim $claim) use ($factionId) {
            return $claim->getFaction()->getFactionId() === $factionId;
        });
    }

    public function deleteClaim(FactionClaim $claim): void {
        $worldName = $claim->getWorld()->getFolderName();
        $chunkKey = $claim->getChunkX() . ":" . $claim->getChunkZ() . ":" . $worldName;
        if (isset($this->claims[$chunkKey])) {
            unset($this->claims[$chunkKey]);
        }
        Loader::getDatabase()->executeChange(QueryStmts::CLAIMS_DELETE, [
            "chunkX"    => $claim->getChunkX(),
            "chunkZ"    => $claim->getChunkZ(),
            "world_name"=> $worldName
        ]);
    }
    
}
