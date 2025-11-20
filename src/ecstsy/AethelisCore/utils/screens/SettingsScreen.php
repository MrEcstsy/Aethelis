<?php

declare(strict_types=1);

namespace ecstsy\AethelisCore\utils\screens;

use ecstsy\AethelisCore\Loader;
use ecstsy\AethelisCore\player\PlayerManager;
use ecstsy\MartianUtilities\interfaces\ScreenInterface;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\SimpleForm;

final class SettingsScreen implements ScreenInterface {

    public function __construct(private Player $player) {}

    private function createForm(): SimpleForm {
        $session = PlayerManager::getInstance()->getSession($this->player);
        if ($session === null) return new SimpleForm(null);

        $form = new SimpleForm(function (Player $player, ?int $data) use ($session) {
            if ($data === null) return;
            
            $settingMap = [
                0 => 'chest_inventories',
                1 => 'broadcasts',
                2 => 'loot_announcer'
            ];
            
            if (isset($settingMap[$data])) {
                $setting = $settingMap[$data];
                $newValue = !$session->getSetting($setting);
                $session->setSetting($setting, $newValue);
                
                $this->sendToast(
                    $player,
                    match($setting) {
                        'chest_inventories' => "Chest Inventories",
                        'broadcasts' => "Broadcast Messages",
                        'loot_announcer' => "Loot Announcements"
                    },
                    $newValue
                );
                
                $this->display($player);
            }
        });

        $form->setTitle(C::colorize("&r&l&8Ethereal Hub Settings"));
        $form->setContent(C::colorize("&r&fClick to toggle settings:"));

        $form->addButton($this->getButtonText("Chest Inventories", $session->getSetting('chest_inventories')));
        $form->addButton($this->getButtonText("Broadcast Messages", $session->getSetting('broadcasts')));
        $form->addButton($this->getButtonText("Loot Announcements", $session->getSetting('loot_announcer')));

        return $form;
    }

    private function getButtonText(string $label, bool $enabled): string {
        $color = $enabled ? "&a" : "&c";
        return C::colorize("&r&8{$label}\n{$color}" . ($enabled ? "Enabled" : "Disabled"));
    }

    private function sendToast(Player $player, string $settingName, bool $enabled): void {
        $player->sendToastNotification(
            C::colorize(Loader::SERVER_TITLE),
            C::colorize("&r&7{$settingName}: " . ($enabled ? "&aEnabled" : "&cDisabled"))
        );
    }

    public function display(Player $player): void {
        $player->sendForm($this->createForm());
    }
}