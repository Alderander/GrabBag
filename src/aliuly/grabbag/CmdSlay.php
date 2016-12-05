<?php
//= cmd:slay,Trolling
//: Kills the specified player
//> usage: **slay** _<player>_ _[messsage]_
//: Kills a player with an optional _message_.
namespace aliuly\grabbag;

use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\utils\TextFormat;

use aliuly\common\BasicCli;
use aliuly\common\mc;
use aliuly\common\PermUtils;
use aliuly\common\MPMU;

class CmdSlay extends BasicCli implements CommandExecutor,Listener {

	public function __construct($owner) {
		parent::__construct($owner);
		$this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);

		PermUtils::add($this->owner, "gb.cmd.slay", "Allow slaying players", "op");

		$this->enableCmd("slay",
							  ["description" => mc::_("kill a player with optional message"),
								"usage" => mc::_("/slay <player> [message]"),
								"permission" => "gb.cmd.slay"]);
	}
	public function slay($victim, $msg = "") {
		if ($msg == "") {
			$this->unsetState($victim);
		} else {
			$this->setState($victim,[time(),$msg]);
		}
		$victim->setHealth(0);
	}
	public function onCommand(CommandSender $sender,Command $cmd,$label, array $args) {
		if ($cmd->getName() != "slay") return false;
		if (!isset($args[0])) {
			$sender->sendMessage(mc::_("Must specify a player to slay"));
			return false;
		}
		if (($victim = MPMU::getPlayer($sender,array_shift($args))) === null) return true;
		$this->slay($victim,implode(" ",$args));
		$sender->sendMessage(TextFormat::RED.mc::_("%1% has been slain.",$victim->getName()));
		return true;
	}
	/**
	 * @priority LOW
	 */
	public function onPlayerDeath(PlayerDeathEvent $e) {
		list($timer,$msg) = $this->getState($e->getEntity(),[0,""]);
		if (time() - $timer > 1) return;
		$e->setDeathMessage($msg);
		$this->unsetState($e->getEntity());
	}
}
