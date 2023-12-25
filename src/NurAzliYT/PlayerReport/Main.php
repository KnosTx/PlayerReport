<?php
# namespace
namespace NurAzliYT\PlayerReport;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {

    private $reports = [];

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->registerCommands();
        $this->loadReports(); // Load existing reports from storage
        $this->getLogger()->info("PlayerReport has been enabled!");
    }

    public function onDisable(): void {
        $this->saveReports(); // Save reports to storage
        $this->getLogger()->info("PlayerReport has been disabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch ($command->getName()) {
            case "report":
                if ($sender instanceof Player) {
                    $this->openReportForm($sender);
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;
            case "viewreports":
                if ($sender instanceof Player) {
                    $this->viewReports($sender);
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;
            default:
                return false;
        }
    }

    private function openReportForm(Player $player): void {
        $form = new CustomForm(function (Player $player, $data) {
            if ($data !== null) {
                $this->processReport($player, $data);
            }
        });
# Form UI
        $form->setTitle("Player Report");
        $form->addInput("Player Name:", "Enter the name of the player you want to report");
        $form->addInput("Reason:", "Briefly describe the reason for your report");

        $player->sendForm($form);
    }

    private function processReport(Player $player, array $data): void {
        $reportedPlayerName = $data[0];
        $reason = $data[1];

        // Store report in-memory
        $this->reports[] = [
            'reporter' => $player->getName(),
            'reportedPlayer' => $reportedPlayerName,
            'reason' => $reason,
            'timestamp' => time(),
        ];

        $player->sendMessage(TextFormat::GREEN . "Report submitted successfully. Thank you!");
    }

    private function viewReports(Player $player): void {
        $player->sendMessage(TextFormat::YELLOW . "=== Player Reports ===");
        foreach ($this->reports as $report) {
            $player->sendMessage(TextFormat::WHITE . "Reporter: " . $report['reporter']);
            $player->sendMessage(TextFormat::WHITE . "Reported Player: " . $report['reportedPlayer']);
            $player->sendMessage(TextFormat::WHITE . "Reason: " . $report['reason']);
            $player->sendMessage(TextFormat::WHITE . "Timestamp: " . date('Y-m-d H:i:s', $report['timestamp']));
            $player->sendMessage(TextFormat::WHITE . "======================");
        }
    }

    private function registerCommands(): void {
        $reportCommand = new Command("report", $this);
        $reportCommand->setDescription("Report a player");
        $reportCommand->setUsage("/report");
        $this->getServer()->getCommandMap()->register("report", $reportCommand);

        $viewReportsCommand = new Command("viewreports", $this);
        $viewReportsCommand->setDescription("View player reports");
        $viewReportsCommand->setUsage("/viewreports");
        $this->getServer()->getCommandMap()->register("viewreports", $viewReportsCommand);
    }

    private function saveReports(): void {
        $config = new Config($this->getDataFolder() . "reports.yml", Config::YAML);
        $config->setAll(['reports' => $this->reports]);
        $config->save();
    }

    private function loadReports(): void {
        $config = new Config($this->getDataFolder() . "reports.yml", Config::YAML);
        $this->reports = $config->get('reports', []);
    }
}
