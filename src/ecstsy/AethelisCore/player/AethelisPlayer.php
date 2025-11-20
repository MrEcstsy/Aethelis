<?php

declare(strict_types=1);

namespace ecstsy\Aetheliscore\player;

use ecstsy\AethelisCore\factions\Faction;
use ecstsy\AethelisCore\factions\FactionManager;
use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\utils\ChatTypes;
use ecstsy\AethelisCore\utils\QueryStmts;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\UuidInterface;

final class AethelisPlayer
{
    private bool $isConnected = false;
    private bool $adminMode = false;
    private string $chat = ChatTypes::ALL;

    public function __construct(
        private UuidInterface $uuid,
        private string        $username,
        private int           $balance,
        private string        $cooldowns,
        private ?string        $factionId,
        private ?string        $factionRole,
        private int           $power,
        private string        $settings
    )
    {
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function setConnected(bool $connected): void
    {
        $this->isConnected = $connected;
    }

    /**
     * Get UUID of the player
     *
     * @return UuidInterface
     */
    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * This function gets the PocketMine player
     *
     * @return Player|null
     */
    public function getPocketminePlayer(): ?Player
    {
        return Server::getInstance()->getPlayerByUUID($this->uuid);
    }

    /**
     * Get username of the session
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username of the session
     *
     * @param string $username
     * @return void
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
        $this->updateDb(); 
    }

        /**
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balance;
    }

    /**
     * @param int $amount
     * @return void
     */
    public function addBalance(int $amount): void 
    {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $maxAmount = $config->getNested("settings.economy.max-money");
        $lang = Loader::getLanguageManager();

        $remainingAmount = $maxAmount - $this->balance;
        $amountToAdd = min($amount, $remainingAmount);

        if ($amountToAdd <= 0) {
            $this->getPocketminePlayer()->sendMessage(TextFormat::colorize($lang->getNested("economy.max-money")));
            return;
        }

        $this->balance += $amountToAdd;
        $this->getPocketminePlayer()->sendMessage(TextFormat::colorize(str_replace(["{amount}", "{currency_symbol"], [number_format($amountToAdd), $config->getNested("settings.economy.currency-symbol")], $lang->getNested("economy.add-balance"))));
        $this->updateDb();
        
    }

    /**
     * @param int $amount
     * @return void
     */
    public function subtractBalance(int $amount): void
    {
        $this->balance -= $amount;
        $this->updateDb();
    }

    /**
     * @param int $amount
     * @return void
     */
    public function setBalance(int $amount): void
    {
        $this->balance = $amount;
        $this->updateDb();
    }

    public function addCooldown(string $cooldownName, int $duration): void
    {
        $cooldowns = json_decode($this->cooldowns, true) ?? [];

        $cooldowns[$this->getUuid()->toString()][$cooldownName] = time() + $duration;

        $this->cooldowns = json_encode($cooldowns);

        $this->updateDb();
    }

    public function getCooldown(string $cooldownName): ?int
    {
        $cooldowns = json_decode($this->cooldowns, true);

        if ($cooldowns !== null && isset($cooldowns[$this->getUuid()->toString()][$cooldownName])) {
            $cooldownExpireTime = $cooldowns[$this->getUuid()->toString()][$cooldownName];
            $remainingCooldown = $cooldownExpireTime - time();
            return max(0, $remainingCooldown);
        }

        return null;
    }

    public function getFaction(): ?Faction {
        if ($this->factionId === null) {
            return null;
        }
        return FactionManager::getInstance()->getFaction($this->factionId);
    }
    
    public function setFaction(?string $factionId): void {
        $this->factionId = $factionId;
        $this->updateDb();
    }

    public function isInAdminMode(): bool
    {
        return $this->adminMode;
    }

    public function setInAdminMode(bool $value): void
    {
        $this->adminMode = $value;
    }

    public function getCurrentChat(): string
    {
        return $this->chat;
    }

    public function setCurrentChat(string $chat): void
    {
        $this->chat = $chat;
    }
    
    public function getFactionRole(): ?string {
        return $this->factionRole;
    }

    public function setFactionRole(?string $factionRole): void {
        $this->factionRole = $factionRole;
        $this->updateDb();
    }

    public function getFactionPower(): int {
        return $this->power;
    }
    
    public function setFactionPower(int $power): void {
        $this->power = $power;
        $this->updateDb();
    }

    public function addFactionPower(int $amount): void {
        $this->power += $amount;
        $this->power = min($this->power, 100);
        $this->updateDb();
    }
    

    public function subtractFactionPower(int $amount): void {
        $this->power -= $amount;
        if ($this->power < 0) {
            $this->power = 0;
        }
        $this->updateDb();
    }

        /**
     * Get a specific setting value by key
     *
     * @param string $key
     * @return mixed|null The value of the setting if found, or null if the key doesn't exist
     */
    public function getSetting(string $key): mixed
    {
        $decodedSettings = json_decode($this->settings, true);
        return $decodedSettings[$key] ?? null;
    }

    /**
     * Set a specific setting value by key
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSetting(string $key, mixed $value): void
    {
        $decodedSettings = json_decode($this->settings, true);
        $decodedSettings[$key] = $value;
        $this->settings = json_encode($decodedSettings);
        $this->updateDb();
    }

    /**
     * Toggle a setting
     *
     * @param string $key
     * @return void
     */
    public function toggleSetting(string $key): void
    {
        $settings = json_decode($this->settings, true);
        $settings[$key] = !($settings[$key] ?? false);
        $this->settings = json_encode($settings);
        $this->updateDb();
    }

    public function getSettingToggle(string $key): string {
        $value = $this->getSetting($key);
        if ($value === null) {
            return "§7Setting not found";
        }
        return $value ? "§aEnabled" : "§cDisabled";
    }
    
    /**
     * Update player information in the database
     *
     * @return void
     */
    private function updateDb(): void
    {

        Loader::getDatabase()->executeChange(QueryStmts::PLAYERS_UPDATE, [
            "uuid" => $this->uuid->toString(),
            "username" => $this->username,
            "balance" => $this->balance,
            "cooldowns" => $this->cooldowns,
            "faction_id" => $this->factionId,
            "faction_role" => $this->factionRole,
            "power" => $this->power,
            "settings" => $this->settings
        ]);
    }

}