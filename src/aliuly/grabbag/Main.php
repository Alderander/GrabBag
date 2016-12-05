<?php
//= cfg:features
//:
//: This section you can enable/disable commands and listener modules.
//: You do this in order to avoid conflicts between different
//: PocketMine-MP plugins.  It has one line per feature:
//:
//:    feature: true|false
//:
//: If **true** the feature is enabled.  if **false** the feature is disabled.
namespace aliuly\grabbag;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use aliuly\common\mc;
use aliuly\common\MPMU;
use aliuly\common\BasicPlugin;
use aliuly\grabbag\api\GrabBag as GrabBagAPI;

class Main extends BasicPlugin {
	public $api;
	public function onEnable(){
		$this->api = new GrabBagAPI($this);
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		mc::plugin_init($this,$this->getFile());
		$this->getLogger()->info(mc::_("LibCommon v%1%", MPMU::version()));
		$features = [
			"players" => [ "CmdPlayers", true ],
			"ops" => [ "CmdOps", true ],
			"gm?" => [ "CmdGmx", true ],
			"as" => [ "CmdAs", true ],
			"slay" => [ "CmdSlay", true ],
			"heal" => [ "CmdHeal", true ],
			"whois" => [ "CmdWhois", true ],
			"mute-unmute" => [ "CmdMuteMgr", true ],
			"freeze-thaw" => [ "CmdFreezeMgr", true ],
			"showtimings" => [ "CmdTimings", true ],
			"seeinv-seearmor" => [ "CmdShowInv", true ],
			"clearinv" => [ "CmdClearInv", true ],
			"get" => [ "CmdGet", true ],
			"shield" => [ "CmdShieldMgr", true ],
			"srvmode" => [ "CmdSrvModeMgr", true ],
			"opms-rpt" => [ "CmdOpMsg", true ],
			"entities" => [ "CmdEntities", true ],
			"after-at" => [ "CmdAfterAt", true ],
			"summon-dismiss" => [ "CmdSummon", true ],
			"pushtp-poptp" => [ "CmdTpStack", true ],
			"prefix" => [ "CmdPrefixMgr", true ],
			"spawn" => [ "CmdSpawn", true ],
			"burn" => [ "CmdBurn", true ],
			"blowup" => [ "CmdBlowUp", true ],
			"setarmor" => [ "CmdSetArmor", true ],
			"spectator"=> [ "CmdSpectator", false ],
			"followers"=> [ "CmdFollowMgr", true ],
			"rcon-client" => [ ["ServerList","CmdRcon"], true ],
			"join-mgr" => [ "JoinMgr", true ],
			"custom-death" => [ "CustomDeath", true ],
			"repeater" => [ "RepeatMgr", true ],
			"broadcast-tp" => [ "BcTpMgr", true ],
			"crash" => ["CmdCrash", true],
			"pluginmgr" => ["CmdPluginMgr", true],
			"permmgr" => ["CmdPermMgr", true],
			"throw" => ["CmdThrow", true],
			"regmgr" => ["CmdRegMgr",true],
			"invisible" => ["CmdInvisible",true],
			"chat-utils" => ["CmdChatMgr",true],
			"query-hosts" => [ ["ServerList","CmdQuery"], true],
			"cmd-selector" => ["CmdSelMgr", true],
			"cmd-alias" => ["CmdAlias", true],
			"reop" => ["CmdReOp" , true],
			"tprequest" => [ "CmdTpRequest", false],
			"homes" => [ "CmdHomes", false],
			"warps" => [ "CmdWarp", false],
			"motd-task" => [ ["ServerList","MotdDaemon"], false],
			"query-task" => [ ["ServerList","QueryDaemon"], false],
			"merge-slots" => [ ["ServerList","MegaSlots"], false],
			"echo" => ["CmdEcho", true],
			"onevent-cmd" => ["CmdOnEvent", false],
			"pmscripts" => ["CmdRc", true],
			"event-tracer" => ["CmdTrace", false],
			"iteminfo" => ["CmdItemInfo", true],
			"plenty" => ["CmdPlenty", true],
			"wall" => [["ServerList","CmdWall"], true],
			"afk" => ["CmdAFK", true],
			"xyz" => ["CmdXyz", true],
			"tptop" => ["CmdTpTop", true],
			"tpback"=> ["CmdTpBack", true],
			"near" => ["CmdNear", true],
			"chat-scribe" => [ "CmdSpy", true ],
		];
		if (MPMU::apiVersion("1.12.0") || MPMU::apiVersion("2.0.0")) {
			$features["fly"] = [ "CmdFly", true ];
			$features["skinner"] = [ "CmdSkinner", true ];
			$features["blood-particles"] = [ "BloodMgr", true ];
			$ft = $this->getServer()->getPluginManager()->getPlugin("FastTransfer");
			if ($ft) {
				$features["broadcast-ft"] = [ "TransferMgr", true ];
				$features["ftservers"] = [ ["ServerList","CmdFTServers"] , true];
			}
		}

		$cfg = $this->modConfig(__NAMESPACE__,$features, [
			"version" => $this->getDescription()->getVersion(),
			"serverlist" => [],
			"join-mgr" => JoinMgr::defaults(),
			"broadcast-tp" => BcTpMgr::defaults(),
			"custom-death" => CustomDeath::defaults(),
			"freeze-thaw" => CmdFreezeMgr::defaults(),
			"cmd-selector" => CmdSelMgr::defaults(),
			"query-task" => QueryDaemon::defaults(),
			"motd-task" => MotdDaemon::defaults(),
			"chat-scribe" => CmdSpy::defaults(),
		]);
	}
	public function rconDone($res,$data) {
		if (!isset($this->modules["rcon-client"])) return;
		$this->modules["rcon-client"]->taskDone($res,$data);
	}
	public function asyncResults($res, $module, $cbname, ...$args) {
		if (!isset($this->modules[$module])) return;
		$cb = [ $this->modules[$module], $cbname ];
		$cb($res, ...$args);
	}
}
