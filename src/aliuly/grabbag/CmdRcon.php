<?php
/**
 ** OVERVIEW:Server Management
 **
 ** COMMANDS
 **
 ** * rcon : rcon client
 **   usage: **rcon** **[--add|--rm|--ls|id]** _<command>_
 **
 **   This is an rcon client that you can used to send commands to other
 **   remote servers.  Options:
 **   - **rcon --add** _&lt;id&gt;_ _&lt;address&gt;_ _&lt;port&gt;_ _&lt;password&gt;_ _[comments]_
 **     - adds a `rcon` connection with `id`.
 **   - **rcon --rm** _&lt;id&gt;_
 **     - Removes `rcon` connection `id`.
 **   - **rcon --ls**
 **     - List configured rcon connections.
 **   - **rcon** _&lt;id&gt;_ _&lt;command&gt;_
 **     - Sends the `command` to the connection `id`.  You can specify multiple
 **			  by separating with commas (,).  Otherwise, you can use **--all**
 **       for the _id_ if you want to send the commands to all configured
 **       servers.
 **
 ** CONFIG:rcon-client
 **
 ** This section configures the rcon client connections.  You can configure
 ** this section through the *rcon* command.
 **/
namespace aliuly\grabbag;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;

use aliuly\grabbag\common\BasicCli;
use aliuly\grabbag\common\mc;
use aliuly\grabbag\common\MPMU;
use aliuly\grabbag\common\RconTask;

class CmdRcon extends BasicCli implements CommandExecutor {
	protected $servers;

	public function __construct($owner,$cfg) {
		parent::__construct($owner);
		$this->servers = $cfg;
		$this->enableCmd("rcon",
							  ["description" => mc::_("RCON client"),
								"usage" => mc::_("/rcon [--add|--rm|--ls|id] <command>"),
								"permission" => "gb.cmd.rcon"]);
	}
	public function onCommand(CommandSender $sender,Command $cmd,$label, array $args) {
		if (count($args) == 0) return false;
		switch($cmd->getName()) {
			case "rcon":
				switch (strtolower($args[0])) {
					case "--add":
						array_shift($args);
						return $this->cmdAdd($sender,$args);
					case "--rm":
						array_shift($args);
						return $this->cmdRm($sender,$args);
					case "--ls":
						array_shift($args);
						return $this->cmdList($sender,$args);
					default:
						return $this->cmdRcon($sender,$args);
				}
		}
		return false;
	}
	private function cmdAdd(CommandSender $c,$args) {
		if (!MPMU::access($c,"gb.cmd.rcon.config")) return true;

		if (count($args) < 4) {
			$c->sendMessage(mc::_("Usage: --add <id> <host> <port> <auth> [comments]"));
			return false;
		}
		$id = array_shift($args);
		if (substr($id,0,1) == "-") {
			$c->sendMessage(mc::_("RCON id can not start with a dash (-)"));
			return false;
		}
		if (strpos($id,",") !== false) {
			$c->sendMessage(mc::_("RCON id can not contain commas (,)"));
			return false;
		}
		if (isset($this->servers[$id])) {
			$c->sendMessage(mc::_("%1% is an id that is already in use.",$id));
			$c->sendMessage(mc::_("Use --rm first"));
			return false;
		}
		$this->servers[$id] = implode(" ",$args);
		$this->owner->cfgSave("rcon-client",$this->servers);
		$c->sendMessage(mc::_("Rcon id %1% configured",$id));
		return true;
	}
	private function cmdRm(CommandSender $c,$args) {
		if (!MPMU::access($c,"gb.cmd.rcon.config")) return true;
		if (count($args) != 1) {
			$c->sendMessage(mc::_("Usage: --rm <id>"));
			return false;
		}
		$id = array_shift($args);
		if (!isset($this->servers[$id])) {
			$c->sendMessage(mc::_("%1% does not exist",$id));
			return false;
		}
		unset($this->servers[$id]);
		$this->owner->cfgSave("rcon-client",$this->servers);
		$c->sendMessage(mc::_("Rcon id %1% deleted",$id));
		return true;
	}
	private function cmdList(CommandSender $c,$args) {
		$pageNumber = $this->getPageNumber($args);
		$txt = ["Rcon connections"];
		foreach ($this->servers as $id => $dat) {
			$dat = preg_split('/\s+/',$dat,4);
			$host = array_shift($dat);
			$port = array_shift($dat);
			array_shift($dat);
			$ln = count($dat) ? " #".$dat[0] : "";
			$txt[] = "$id: $host:$port$ln";
		}
		return $this->paginateText($c,$pageNumber,$txt);
	}
	private function cmdRcon(CommandSender $c,$args) {
		if (count($args) < 2) return false;
		$id = array_shift($args);
		if ($id == "--all") {
			$grp = array_keys($this->servers);
		} else {
			$grp = [];
			foreach (explode(",",$id) as $i) {
		  	if (!isset($this->servers[$i])) {
			  	$c->sendMessage(mc::_("%1% does not exist",$id));
			  	continue;
		  	}
				$grp[$i] = $i;
			}
			if (count($grp) == 0) return false;
		}
		$cmd = implode(" ",$args);
		foreach ($grp as $id) {
			$this->owner->getServer()->getScheduler()->scheduleAsyncTask(
				new RconTask($this->owner,"rconDone",
											preg_split('/\s+/',$this->servers[$id],3),
											implode(" ",$args),
											[($c instanceof Player) ? $c->getName() : serialize($c)])
		  );
		}
		return true;
	}
	public function taskDone($res,$sn) {
		if (($player = $this->owner->getServer()->getPlayer($sn)) == null) {
			$player = unserialize($sn);
		}
		if (!is_array($res)) {
			$player->sendMessage($res);
			return;
		}
		$player->sendMessage($res[0]);
	}
}
