<?php
/**
 ** OVERVIEW:Player Management
 **
 ** COMMANDS
 **
 ** * * invis : player invisible
 **   usage: **invis**
 **
 **   This will toggle your invisibility status.
 **
 **/
namespace aliuly\grabbag;

use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;

use aliuly\grabbag\common\BasicCli;
use aliuly\grabbag\common\mc;
use aliuly\grabbag\common\MPMU;

class CmdInvisible extends BasicCli implements Listener,CommandExecutor {
	public function __construct($owner) {
		parent::__construct($owner);
		$this->enableCmd("invis",
							  ["description" => mc::_("makes player invisible"),
								"usage" => mc::_("/invis"),
								"permission" => "gb.cmd.invisible"]);
		$this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
	}
	private function activate(Player $pl) {
		$pl->sendMessage(mc::_("You are now invisible"));
		$this->setState($pl,true);
		foreach($this->owner->getServer()->getOnlinePlayers() as $online){
			if($online->hasPermission("gb.cmd.invisible.inmune")) continue;
			$online->hidePlayer($pl);
		}
	}
	private function deactivate(Player $pl) {
		$pl->sendMessage(mc::_("You are no longer invisible"));
		$this->setState($pl,false);
		foreach($this->owner->getServer()->getOnlinePlayers() as $online){
			$online->showPlayer($pl);
		}
	}
	public function onPlayerJoin(PlayerJoinEvent $e) {
		$pl = $e->getPlayer();
		if($pl->hasPermission("gb.cmd.invisible.inmune")) return;
		foreach($this->owner->getServer()->getOnlinePlayers() as $online){
			if ($this->getState($online,false)) {
				$pl->hidePlayer($online);
			}
		}
	}
	public function onCommand(CommandSender $sender,Command $cmd,$label, array $args) {
		if (count($args) !== 0) return false;
		if ($cmd->getName() != "invis") return false;
		if (!MPMU::inGame($sender)) return true;
		$state = $this->getState($sender,false);
		if ($state) {
			$this->deactivate($sender);
		} else {
			$this->activate($sender);
		}
		return true;
	}
}
