<?php
namespace aliuly\grabbag;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;

use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\item\Item;

abstract class BaseCommand implements CommandExecutor {
	protected $owner;

	public function __construct($owner) {
		$this->owner = $owner;
	}

	static $items = [];

	public function itemName(Item $item) {
		if (count(self::$items) == 0) {
			$constants = array_keys((new \ReflectionClass("pocketmine\\item\\Item"))->getConstants());
			foreach ($constants as $constant) {
				$id = constant("pocketmine\\item\\Item::$constant");
				$constant = str_replace("_", " ", $constant);
				self::$items[$id] = $constant;
			}
		}
		$n = $item->getName();
		if ($n != "Unknown") return $n;
		if (isset(self::$items[$item->getId()]))
			return self::$items[$item->getId()];
		return $n;
	}

	public function mwteleport($pl,$pos) {
		if (($pos instanceof Position) &&
			 ($mw = $this->owner->getServer()->getPluginManager()->getPlugin("ManyWorlds")) != null) {
			// Using ManyWorlds for teleporting...
			$mw->mwtp($pl,$pos);
		} else {
			$pl->teleport($pos);
		}
	}

	public function enableCmd($cmd,$yaml) {
		$newCmd = new PluginCommand($cmd,$this->owner);
		if (isset($yaml["description"]))
			$newCmd->setDescription($yaml["description"]);
		if (isset($yaml["usage"]))
			$newCmd->setUsage($yaml["usage"]);
		if(isset($yaml["aliases"]) and is_array($yaml["aliases"])) {
			$aliasList = [];
			foreach($yaml["aliases"] as $alias) {
				if(strpos($alias,":")!== false) {
					$this->owner->getLogger()->info("Unable to load alias $alias");
					continue;
				}
				$aliasList[] = $alias;
			}
			$newCmd->setAliases($aliasList);
		}
		if(isset($yaml["permission"]))
			$newCmd->setPermission($yaml["permission"]);
		if(isset($yaml["permission-message"]))
			$newCmd->setPermissionMessage($yaml["permission-message"]);
		$newCmd->setExecutor($this);
		$cmdMap = $this->owner->getServer()->getCommandMap();
		$cmdMap->register($this->owner->getDescription()->getName(),$newCmd);
	}

	public function inGame(CommandSender $sender,$msg = true) {
		if (!($sender instanceof Player)) {
			if ($msg) $sender->sendMessage("You can only do this in-game");
			return false;
		}
		return true;
	}

	// Access and other permission related checks
	protected function access(CommandSender $sender, $permission) {
		if($sender->hasPermission($permission)) return true;
		$sender->sendMessage("You do not have permission to do that.");
		return false;
	}

	public function iName($player) {
		if ($player instanceof Player) {
			$player = strtolower($player->getName());
		}
		return $player;
	}

	public function getState(CommandSender $player,$default) {
		return $this->owner->getState(get_class($this),$player,$default);
	}
	public function setState(CommandSender $player,$val) {
		$this->owner->setState(get_class($this),$player,$val);
	}
	public function unsetState(CommandSender $player) {
		$this->owner->unsetState(get_class($this),$player);
	}

	// Paginate output
	protected function getPageNumber(array &$args) {
		$pageNumber = 1;
		if (count($args) && is_numeric($args[count($args)-1])) {
			$pageNumber = (int)array_pop($args);
			if($pageNumber <= 0) $pageNumber = 1;
		}
		return $pageNumber;
	}
	protected function paginateText(CommandSender $sender,$pageNumber,array $txt) {
		$hdr = array_shift($txt);
		if($sender instanceof ConsoleCommandSender){
			$sender->sendMessage( TextFormat::GREEN.$hdr.TextFormat::RESET);
			foreach ($txt as $ln) $sender->sendMessage($ln);
			return true;
		}
		$pageHeight = 5;
		$lineCount = count($txt);
		$pageCount = intval($lineCount/$pageHeight) + ($lineCount % $pageHeight ? 1 : 0);
		$hdr = TextFormat::GREEN.$hdr. TextFormat::RESET;
		if ($pageNumber > $pageCount) {
			$sender->sendMessage($hdr);
			$sender->sendMessage("Only $pageCount pages available");
			return true;
		}
		$hdr .= TextFormat::RED." ($pageNumber of $pageCount)";
		$sender->sendMessage($hdr);
		for ($ln = ($pageNumber-1)*$pageHeight;$ln < $lineCount && $pageHeight--;++$ln) {
			$sender->sendMessage($txt[$ln]);
		}
		return true;
	}
	protected function paginateTable(CommandSender $sender,$pageNumber,array $tab) {
		$cols = [];
		for($i=0;$i < count($tab[0]);$i++) $cols[$i] = strlen($tab[0][$i]);
		foreach ($tab as $row) {
			for($i=0;$i < count($row);$i++) {
				if (($l=strlen($row[$i])) > $cols[$i]) $cols[$i] = $l;
			}
		}
		$txt = [];
		$fmt = "";
		foreach ($cols as $c) {
			if (strlen($fmt) > 0) $fmt .= " ";
			$fmt .= "%-".$c."s";
		}
		foreach ($tab as $row) {
			$txt[] = sprintf($fmt,...$row);
		}
		return $this->paginateText($sender,$pageNumber,$txt);
	}

	protected function cfgSave($key,$settings) {
		$cfg=new Config($this->owner->getDataFolder()."config.yml",Config::YAML);
		$dat = $cfg->getAll();
		$dat[$key] = $settings;
		$cfg->setAll($dat);
		$cfg->save();
	}

	//public function onCommand(CommandSender $sender,Command $command,$label, array $args);
}
