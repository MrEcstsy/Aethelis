<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore;

use ecstsy\AethelisCore\commands\EcoCommand;
use ecstsy\AethelisCore\commands\FactionCommand;
use ecstsy\AethelisCore\commands\SettingsCommand;
use ecstsy\AethelisCore\commands\StaffChatCommand;
use ecstsy\AethelisCore\factions\claims\ClaimManager;
use ecstsy\AethelisCore\factions\FactionManager;
use ecstsy\AethelisCore\factions\flags\FlagFactory;
use ecstsy\AethelisCore\factions\permissions\PermissionFactory;
use ecstsy\AethelisCore\listeners\ClaimsListener;
use ecstsy\AethelisCore\listeners\EventListener;
use ecstsy\AethelisCore\player\PlayerManager;
use ecstsy\AethelisCore\utils\QueryStmts;
use ecstsy\AethelisCore\utils\Utils;
use ecstsy\MartianUtilities\managers\LanguageManager;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use JackMD\ConfigUpdater\ConfigUpdater;
use Jibix\Forms\Forms;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class Loader extends PluginBase {
    use SingletonTrait;

    public const SETTINGS_FORM_ID = 6969;

    public const SERVER_TITLE = "&r&6✦ Ethereal Hub &6✦";

    public static LanguageManager $languageManager;

    public static DataConnector $connector;

    public static PlayerManager $playerManager;

    public static FactionManager $factionManager;

    public static ClaimManager $claimsManager;

    public function onLoad(): void {
        self::setInstance($this);
    }

    public function onEnable(): void {
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "version", 1);
        $config = GeneralUtils::getConfiguration($this, "config.yml");
        $this->saveAllFilesInDirectory('locale');

        $listeners = [
            new EventListener(),
            new ClaimsListener(),
        ];
    
        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }

        $this->getServer()->getCommandMap()->registerAll("Aethelis", [
            new EcoCommand($this, "economy", "Economy command", ['eco']),
            new FactionCommand($this, "faction", "Faction  command", ['f']),
            new SettingsCommand($this, "settings", "Manage Server Settings"),
            new StaffChatCommand($this, "staffchat", "Toggle Staff Chat", ["sc"]),
        ]);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        self::$connector = libasynql::create($this, ["type" => "sqlite", "sqlite" => ["file" => "sqlite.sql"], "worker-limit" => 2], ["sqlite" => "sqlite.sql"]);
        self::$connector->executeGeneric(QueryStmts::PLAYERS_INIT);
        self::$connector->executeGeneric(QueryStmts::FACTIONS_INIT);
        self::$connector->executeGeneric(QueryStmts::CLAIMS_INIT);
        self::$connector->waitAll();

        self::$languageManager = new LanguageManager($this, $config->getNested('settings.language'));
        self::$playerManager = new PlayerManager($this);
        self::$factionManager = new FactionManager();
        self::$claimsManager = new ClaimManager();

        Utils::registerCustomRankTags();
        PermissionFactory::init();
        FlagFactory::init();

        Forms::register($this);
    }

    public function onDisable(): void {
        if (isset(self::$connector)) {
            self::$connector->close();
        }
    }

    public static function getDatabase(): DataConnector {
        return self::$connector;
    }

    public static function getLanguageManager(): LanguageManager {
        return self::$languageManager;
    }

    public static function getPlayerManager(): PlayerManager {
        return self::$playerManager;
    }

    public static function getFactionManager(): FactionManager {
        return self::$factionManager;
    }

    public static function getClaimsManager(): ClaimManager {
        return self::$claimsManager;
    }


    private function saveAllFilesInDirectory(string $directory): void {
        $resourcePath = $this->getFile() . "resources/$directory/";
        if (!is_dir($resourcePath)) {
            $this->getLogger()->warning("Directory $directory does not exist.");
            return;
        }

        $files = scandir($resourcePath);
        if ($files === false) {
            $this->getLogger()->warning("Failed to read directory $directory.");
            return;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $this->saveResource("$directory/$file");
        }
    }
}