<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\listeners;

use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\Utils; 
use ecstsy\AethelisCore\factions\claims\ClaimManager;
use ecstsy\AethelisCore\factions\claims\FactionClaim;
use ecstsy\AethelisCore\factions\permissions\FactionPermission;
use pocketmine\block\tile\Container;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\Position;

final class ClaimsListener implements Listener {

    /** @var array<string, string> Maps player's UUID to their last known claim ID */
    private static array $claimStates = [];

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $uuid = $player->getUniqueId()->toString();

        $fromChunkX = $event->getFrom()->getFloorX() >> 4;
        $fromChunkZ = $event->getFrom()->getFloorZ() >> 4;
        $toChunkX = $event->getTo()->getFloorX() >> 4;
        $toChunkZ = $event->getTo()->getFloorZ() >> 4;
        if ($fromChunkX === $toChunkX && $fromChunkZ === $toChunkZ) {
            return; 
        }

        $claim = ClaimManager::getInstance()->getClaimAtPosition($player->getPosition());
        $currentFactionId = $claim !== null ? $claim->getFaction()->getFactionId() : null;

        if ((self::$claimStates[$uuid] ?? null) === $currentFactionId) {
            return;
        }
        self::$claimStates[$uuid] = $currentFactionId;

        Utils::handleClaimTitle($player, $claim);
    }

    public function onBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $position = $event->getBlock()->getPosition();
        
        if (!Utils::canAffectArea($player, $position, FactionPermission::BREAK)) {
            $event->cancel();
            return;
        }
    }

    public function onPlace(BlockPlaceEvent $event): void {
        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            if (!Utils::canAffectArea($event->getPlayer(), new Position($x, $y, $z, $event->getPlayer()->getWorld()), FactionPermission::BUILD)) {
                $event->cancel();
                return;
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $tile = $event->getBlock()->getPosition()->getWorld()->getTile($event->getBlock()->getPosition());
        $permission = $tile instanceof Container ? FactionPermission::CONTAINERS : FactionPermission::INTERACT;
        
        if (!Utils::canAffectArea($event->getPlayer(), $event->getBlock()->getPosition(), $permission)) {
            $event->cancel();
        }
    }

    public function onCommand(CommandEvent $event): void {
        $player = $event->getSender();
        if (!$player instanceof Player) return;

        $session = Loader::getPlayerManager()->getSession($player);
        $claim = ClaimManager::getInstance()->getClaimAtPosition($player->getPosition());
        
        if ($claim && $session && $session->getFaction() !== $claim->getFaction()) {
            $command = strtolower(explode(" ", $event->getCommand())[0]);
            $relation = $session->getFaction()?->getRelation($claim->getFaction()) ?? 'neutral';
            
            $blockedCommands = Loader::getInstance()->getConfig()->getNested("factions.blocked-commands.$relation", []);
            if (in_array($command, $blockedCommands)) {
                $event->cancel();
                $player->sendMessage(C::RED . "You can't use this command in " . $relation . " territory!");
            }
        }
    }

}
