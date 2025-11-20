<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\listeners;

use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\player\PlayerManager;
use ecstsy\AethelisCore\utils\ChatTypes;
use ecstsy\AethelisCore\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use SplObjectStorage;

final class EventListener implements Listener {

    public static SplObjectStorage $combatPlayers;
    private float $combatTime;
    private array $bannedCommandsMap;
    private array $bannedCommandsList;
    private bool $banAllCommands;
    private bool $killOnLog;

    public function __construct() {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        self::$combatPlayers = new SplObjectStorage();
        
        $this->combatTime = (float)$config->getNested("settings.combat.time", 30.0);
        $this->banAllCommands = (bool)$config->getNested("settings.combat.ban-all-commands", false);
        $this->killOnLog = (bool)$config->getNested("settings.combat.kill-on-log", false);
        
        $commands = $config->getNested("settings.combat.banned-commands", []);
        $this->bannedCommandsList = $commands;
        $this->bannedCommandsMap = array_flip($commands);
        Utils::combatTask();
        
    }
    
    public function onLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();

        if (PlayerManager::getInstance()->getSession($player) === null) {
            PlayerManager::getInstance()->createSession($player);
        }
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        PlayerManager::getInstance()->getSession($player)->setConnected(true);
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if (self::$combatPlayers->contains($player)) {
            if ($this->killOnLog) {
                $player->kill();
            }
            self::$combatPlayers->detach($player);
        }

        PlayerManager::getInstance()->getSession($player)->setConnected(false);
    }

    /**
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) return;

        $player = $event->getEntity();
        $damager = $event->getDamager();
        
        if (!($player instanceof Player) || !($damager instanceof Player)) return;

        $lang = Loader::getLanguageManager();
        $message = C::colorize($lang->getNested("combat.enter-combat"));

        foreach ([$player, $damager] as $combatant) {
            if (!self::$combatPlayers->contains($combatant)) {
                $combatant->sendMessage($message);
            }
            self::$combatPlayers[$combatant] = microtime(true) + $this->combatTime;
        }
    }

    public function onCommandPreprocess(CommandEvent $event): void {
        $sender = $event->getSender();
        if (!$sender instanceof Player) return;

        if (self::$combatPlayers->contains($sender)) {
            $command = strtolower(explode(' ', $event->getCommand(), 2)[0]);
            
            if ($this->banAllCommands || isset($this->bannedCommandsMap[$command])) {
                $sender->sendMessage(C::colorize(
                    Loader::getLanguageManager()->getNested("combat.banned-command")
                ));
                $event->cancel();
            }
        }
    }

    public function onDamageByEntity(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity instanceof Player && $damager instanceof Player) {
            $entitySession = Loader::getPlayerManager()->getSession($entity);
            $damagerSession = Loader::getPlayerManager()->getSession($damager);
            $entityFaction = $entitySession->getFaction();
            $damagerFaction = $damagerSession->getFaction();

            if ($entityFaction !== null && $damagerFaction !== null &&
                $entityFaction->getFactionId() === $damagerFaction->getFactionId()) {
                $event->cancel();
                $damager->sendMessage(C::colorize("&r&4Error: &cYou cannot attack your own faction!"));
                return;
            }

            if (Loader::getPlayerManager()->areAlliedOrTruced($entity, $damager)) {
                $event->cancel();
                $damager->sendMessage(C::colorize("&r&4Error: &cYou cannot attack an ally!"));
                return;
            }

            $claim = Loader::getClaimsManager()->getClaimAtPosition($entity->getPosition());

            if ($claim !== null) {
                if ($claim->getFaction()->getFlag("safezone")) {
                    $event->cancel();
                    $damager->sendMessage(C::colorize("&r&4Error: &cYou cannot do this here!"));
                    return;
                }

                if ($claim->getFaction() === $entityFaction) {
                    if ($damagerFaction === null || !$damagerFaction->isEnemy($entityFaction)) {
                        $event->cancel();
                        $damager->sendMessage(C::colorize("&r&4Error: &cYou cannot attack an ally!"));
                        return;
                    }

                    $modifier = -Loader::getInstance()->getConfig()->getNested("settings.factions.claims.shield-factor", 0.1);
                    $event->setModifier($modifier, 56789);
                }
            }
        }
    }

    /**
     * @priority HIGH
     * @ignoreCancelled
     */
    public function onChat(PlayerChatEvent $event): void {
        $sender = $event->getPlayer();
        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            return;
        }
        
        $sender = $event->getPlayer();
        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            return;
        }
        
        $message = $event->getMessage();
        $server = Loader::getInstance()->getServer();
        $formatted = "";
        
        if (strpos($message, "#") === 0 && $sender->hasPermission("aethelis.staff-chat")) {
            $message = ltrim(substr($message, 1));
            $formatted = "§l§6[STAFF] §r§6{$sender->getName()} » §6{$message}";
            $event->cancel();
            foreach ($server->getOnlinePlayers() as $player) {
                if ($player->hasPermission("aethelis.staff-chat")) {
                    $player->sendMessage(C::colorize($formatted));
                }
            }
            return;
        }
        
        $currentChat = $session->getCurrentChat();
        
        if ($currentChat === ChatTypes::STAFF) {
            $formatted = "§l§6[STAFF] §r§6{$sender->getName()} » §6{$message}";
            $event->cancel();
            foreach ($server->getOnlinePlayers() as $player) {
                if ($player->hasPermission("aethelis.staff-chat")) {
                    $player->sendMessage(C::colorize($formatted));
                }
            }
            return;
        }
        
        if ($currentChat === ChatTypes::FACTION || $currentChat === ChatTypes::ALLY) {
            $event->cancel();  
            $senderFaction = $session->getFaction();
            if ($senderFaction === null) {
                $sender->sendMessage("§cYou are not in a faction!");
                return;
            }
            $role = $session->getFactionRole();
            
            if ($currentChat === ChatTypes::FACTION) {
                $formatted = "§r§a" . Utils::getFactionRoleSymbol($role) . "{$sender->getName()} » §a{$message}";
            } else {
                $formatted = "§r§5{$senderFaction->getName()} §d" . Utils::getFactionRoleSymbol($role) . "{$sender->getName()} » §d{$message}";
            }
            
            foreach ($server->getOnlinePlayers() as $player) {
                $pSession = Loader::getPlayerManager()->getSession($player);
                if ($pSession === null) {
                    continue;
                }
                
                if ($currentChat === ChatTypes::FACTION) {
                    if ($pSession->getFaction() !== null && $pSession->getFaction()->getFactionId() === $senderFaction->getFactionId()) {
                        $player->sendMessage(C::colorize($formatted));
                    }
                } elseif ($currentChat === ChatTypes::ALLY) {
                    if ($pSession->getFaction() !== null) {
                        $recipientFaction = $pSession->getFaction();
                        if ($recipientFaction->getFactionId() === $senderFaction->getFactionId()) {
                            $player->sendMessage(C::colorize($formatted));
                        } else {
                            $relation = $senderFaction->getRelation($recipientFaction);
                            if (in_array($relation, ["ally", "truce"], true)) {
                                $player->sendMessage(C::colorize($formatted));
                            }
                        }
                    }
                }
            }
            return;
        }
    }
}