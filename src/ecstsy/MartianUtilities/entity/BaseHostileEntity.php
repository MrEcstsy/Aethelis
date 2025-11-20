<?php

namespace ecstsy\MartianUtilities\entity;

use pocketmine\block\Block;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class BaseHostileEntity extends Living {
    
    protected ?Player $target = null;
    protected bool $persistent = false;
    protected int $aiUpdateInterval = 5; 
    protected int $currentTick = 0;

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $this->currentTick += $tickDiff;
    
        if ($this->currentTick >= $this->aiUpdateInterval) {
            $this->currentTick = 0;
            $this->updateAI();
        }
    
        return parent::entityBaseTick($tickDiff);
    }
    
    public function findClosestPlayer(): ?Player {
        $closestPlayer = null;
        $minDistanceSq = PHP_INT_MAX;
    
        foreach ($this->getWorld()->getPlayers() as $player) {
            if ($player->isOnline() && !$player->isSpectator()) {
                $distanceSq = $player->getPosition()->distanceSquared($this->getPosition());
                if ($distanceSq < $minDistanceSq) {
                    $minDistanceSq = $distanceSq;
                    $closestPlayer = $player;
                }
            }
        }
    
        return $minDistanceSq <= 15 ** 2 ? $closestPlayer : null;
    }    

    protected function updateAI(): void {
        // Acquire a target if none exists
        if (!$this->hasTarget()) {
            $this->target = $this->findClosestPlayer();
        }
    
        // If a target exists, follow and interact
        if ($this->hasTarget() && $this->target !== null) {
            $this->followTarget();
    
            // Continuously look at the target for better responsiveness
            $this->lookAt($this->target->getPosition()->add(0, $this->getEyeHeight(), 0));
    
            // Check for nearby players to attack
            foreach ($this->getPlayersInRange(1) as $player) {
                $this->attackPlayer($player);
            }
    
            // Handle collisions and obstacles
            $this->handleCollision();
    
            // Jump if necessary (e.g., to overcome obstacles)
            if ($this->shouldJump()) {
                $this->jump();
            }
        } else {
            // No target; default behavior
            $this->lookAt($this->getPosition()->add($this->getMotion()->x, 0, $this->getMotion()->z));
            $this->clearTarget();
        }
    }
    

    public function attackPlayer(Player $player): void {
        $player->attack(new EntityDamageEvent($this, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 6));
    }

    public function followTarget(): void {
        if ($this->target instanceof Player) {
            $direction = $this->target->getPosition()->subtract($this->getPosition()->getX(), $this->getPosition()->getY(), $this->getPosition()->getZ())->normalize();
            $this->motion->x = $direction->x * 0.2;
            $this->motion->z = $direction->z * 0.2;
        }
    }

    public function clearTarget(): void {
        $this->target = null;
    }

    public function hasTarget(): bool {
        return $this->target instanceof Player;
    }

    public function getPlayersInRange(float $radius): array {
        $players = [];
        $bb = $this->getBoundingBox()->expandedCopy($radius, $radius, $radius);

        foreach ($this->getWorld()->getNearbyEntities($bb) as $entity) {
            if ($entity instanceof Player && $entity->isOnline()) {
                $players[] = $entity;
            }
        }

        return $players;
    }

    protected function handleCollision(): void {
        $entities = $this->getWorld()->getNearbyEntities($this->getBoundingBox()->expandedCopy(0.5, 0.5, 0.5));

        foreach ($entities as $entity) {
            if ($entity instanceof BaseHostileEntity && $entity !== $this) {
                $difference = $this->getPosition()->subtract($entity->getPosition()->getX(), $entity->getPosition()->getY(), $entity->getPosition()->getZ());
                if ($difference->lengthSquared() < 0.5 ** 2) {
                    $push = $difference->normalize()->multiply(0.1); 
                    $this->motion->x += $push->x;
                    $this->motion->z += $push->z;
                }
            }
        }
    }


    public function jump(): void {
        if ($this->onGround) { // Jump only if grounded
            $this->motion->y = 0.5; // Adjust jump height
        }
    }

    public function shouldJump(): bool {
        $frontBlock = $this->getFrontBlock();
        return $frontBlock->isSolid() || $frontBlock instanceof Stair || $frontBlock instanceof Slab;
    }

    public function getFrontBlock($y = 0): Block {
        $direction = $this->getDirectionVector();
        $pos = $this->getPosition()->add($direction->x, $y, $direction->z)->floor();
        return $this->getWorld()->getBlockAt($pos->x, $pos->y, $pos->z);
    }

    public function getJumpMultiplier(): int {
        $frontBlock = $this->getFrontBlock();
        $belowBlock = $this->getFrontBlock(-0.5);
        $belowFrontBlock = $this->getFrontBlock(-1);

        if ($frontBlock->isSolid()) {
            return 3;
        }

        if ($frontBlock instanceof Slab || $belowBlock instanceof Slab || $belowFrontBlock instanceof Slab) {
            return 10;
        }

        if ($frontBlock instanceof Stair || $belowBlock instanceof Stair || $belowFrontBlock instanceof Stair) {
            return 10;
        }

        return 5;
    }

    public function setPersistence(bool $persistent): self {
        $this->persistent = $persistent;
        return $this;
    }

}