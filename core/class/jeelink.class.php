<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class jeelink extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function event() {
		$cmds = cmd::byLogicalId('remote::' . init('remote_cmd_id') . '::' . init('remote_apikey'), 'info');
		if (count($cmds) == 0) {
			return;
		}
		$cmd = $cmds[0];
		if (!is_object($cmd)) {
			return;
		}
		$cmd->event(init('remote_cmd_value'));
	}

	public static function createEqLogicFromDef($_params) {
		foreach ($_params['eqLogics'] as $eqLogic_info) {
			$map_id = array();
			$eqLogic = self::byLogicalId('remote::' . $eqLogic_info['id'] . '::' . $_params['remote_apikey'], 'jeelink');
			if (!is_object($eqLogic)) {
				$eqLogic = new jeelink();
				utils::a2o($eqLogic, $eqLogic_info);
				$eqLogic->setId('');
				$eqLogic->setObject_id('');
				if (isset($eqLogic_info['object_name']) && $eqLogic_info['object_name'] != '') {
					$object = object::byName($eqLogic_info['object_name']);
					if (is_object($object)) {
						$eqLogic->setObject_id($object->getId());
					}
				}
			}
			$eqLogic->setConfiguration('remote_id', $eqLogic_info['id']);
			$eqLogic->setConfiguration('remote_address', $_params['address']);
			$eqLogic->setConfiguration('remote_apikey', $_params['remote_apikey']);
			$eqLogic->setEqType_name('jeelink');
			try {
				$eqLogic->save();
			} catch (Exception $e) {
				$eqLogic->setName($eqLogic->getName() . ' remote ' . rand(0, 9999));
				$eqLogic->save();
			}

			foreach ($eqLogic_info['cmds'] as $cmd_info) {
				$cmd = $eqLogic->getCmd(null, 'remote::' . $cmd_info['id'] . '::' . $_params['remote_apikey']);
				if (!is_object($cmd)) {
					$cmd = new jeelinkCmd();
					utils::a2o($cmd, $cmd_info);
					$cmd->setId('');
					$cmd->setValue('');
				}
				$cmd->setEqType('jeelink');
				$cmd->setEqLogic_id($eqLogic->getId());
				$cmd->setConfiguration('remote_id', $cmd_info['id']);
				if ($cmd_info['logicalId'] == 'refresh') {
					$cmd->setConfiguration('isRefreshCmd', 1);
				} else {
					$cmd->setConfiguration('isRefreshCmd', 0);
				}
				$cmd->save();
				$map_id[$cmd_info['id']] = $cmd->getId();
			}

			foreach ($eqLogic_info['cmds'] as $cmd_info) {
				if (!isset($cmd_info['value']) || !isset($map_id[$cmd_info['value']])) {
					continue;
				}
				if (!isset($map_id[$cmd_info['id']])) {
					continue;
				}
				$cmd = cmd::byId($map_id[$cmd_info['id']]);
				if (!is_object($cmd)) {
					continue;
				}
				if ($cmd->getValue() != '') {
					continue;
				}
				$cmd->setValue($map_id[$cmd_info['value']]);
				$cmd->save();
			}
		}
		$eqLogic = self::byLogicalId('remote::core::' . $_params['remote_apikey'], 'jeelink');
		if (!is_object($eqLogic)) {
			$eqLogic = new jeelink();
			$eqLogic->setName(__('Controle', __FILE__) . ' ' . $_params['name']);
			$eqLogic->setIsEnable(1);
		}
		$eqLogic->setConfiguration('remote_id', 'core');
		$eqLogic->setConfiguration('remote_address', $_params['address']);
		$eqLogic->setConfiguration('remote_apikey', $_params['remote_apikey']);
		$eqLogic->setConfiguration('deamons', $_params['deamons']);
		$eqLogic->setEqType_name('jeelink');
		try {
			$eqLogic->save();
		} catch (Exception $e) {
			$eqLogic->setName($eqLogic->getName() . ' remote ' . rand(0, 9999));
			$eqLogic->save();
		}
		$i = 0;
		foreach ($_params['deamons'] as $info) {
			$cmd = $eqLogic->getCmd(null, 'deamonState::' . $info['id']);
			if (!is_object($cmd)) {
				$cmd = new jeelinkCmd();
				$cmd->setName(__('Démon', __FILE__) . ' ' . $info['name']);
				$cmd->setTemplate('mobile', 'line');
				$cmd->setTemplate('dashboard', 'line');
				$cmd->setOrder(100 + $i);
			}
			$cmd->setConfiguration('remote_plugin_id', $info['id']);
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('deamonState::' . $info['id']);
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->save();

			$cmd = $eqLogic->getCmd(null, 'deamonStart::' . $info['id']);
			if (!is_object($cmd)) {
				$cmd = new jeelinkCmd();
				$cmd->setName(__('Démarrer', __FILE__) . ' ' . $info['name']);
				$cmd->setOrder(101 + $i);
			}
			$cmd->setConfiguration('remote_plugin_id', $info['id']);
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('deamonStart::' . $info['id']);
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->save();

			$cmd = $eqLogic->getCmd(null, 'deamonStop::' . $info['id']);
			if (!is_object($cmd)) {
				$cmd = new jeelinkCmd();
				$cmd->setName(__('Arrêter', __FILE__) . ' ' . $info['name']);
				$cmd->setOrder(102 + $i);
			}
			$cmd->setConfiguration('remote_plugin_id', $info['id']);
			$cmd->setEqLogic_id($eqLogic->getId());
			$cmd->setLogicalId('deamonStop::' . $info['id']);
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->save();
			$i += 10;
		}
		try {
			$eqLogic->updateSysInfo();
		} catch (Exception $e) {

		}
	}

	public static function cron10($_eqLogic_id = null) {
		if ($_eqLogic_id == null) {
			$eqLogics = eqLogic::byType('jeelink');
		} else {
			$eqLogics = array(eqLogic::byId($_eqLogic_id));
		}
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('remote_id') != 'core') {
				continue;
			}
			$eqLogic->updateSysInfo();
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function getJsonRpc() {
		$params = array(
			'apikey' => $this->getConfiguration('remote_apikey'),
		);
		$jsonrpc = new jsonrpcClient($this->getConfiguration('remote_address') . '/core/api/jeeApi.php', '', $params);
		$jsonrpc->setNoSslCheck(true);
		return $jsonrpc;
	}

	public function updateSysInfo() {
		$jsonrpc = $this->getJsonRpc();

		$cmd = $this->getCmd(null, 'ping');
		if ($jsonrpc->sendRequest('ping')) {
			$cmd->event(1);
		} else {
			$cmd->event(0);
		}

		$cmd = $this->getCmd(null, 'state');
		if ($jsonrpc->sendRequest('jeedom::isOk')) {
			if ($jsonrpc->getResult()) {
				$cmd->event(1);
			} else {
				$cmd->event(0);
			}
		} else {
			$cmd->event(0);
		}

		$cmd = $this->getCmd(null, 'version');
		if ($jsonrpc->sendRequest('version')) {
			$cmd->event($jsonrpc->getResult());
		}

		foreach ($this->getConfiguration('deamons') as $info) {
			$cmd = $this->getCmd(null, 'deamonState::' . $info['id']);
			if ($jsonrpc->sendRequest('plugin::deamonInfo', array('plugin_id' => $info['id']))) {
				$result = $jsonrpc->getResult();
				if ($result['state'] == 'ok') {
					$cmd->event(1);
				} else {
					$cmd->event(0);
				}
			} else {
				$cmd->event(0);
			}
		}
	}

	public function preSave() {
		if ($this->getConfiguration('remote_id') == '') {
			throw new Exception(__('Le remote ID ne peut etre vide', __FILE__));
		}
		if ($this->getConfiguration('remote_address') == '') {
			throw new Exception(__('La remote addresse ne peut etre vide', __FILE__));
		}
		if ($this->getConfiguration('remote_apikey') == '') {
			throw new Exception(__('La remote apikey ne peut etre vide', __FILE__));
		}
		$this->setLogicalId('remote::' . $this->getConfiguration('remote_id') . '::' . $this->getConfiguration('remote_apikey'));
	}

	public function postSave() {
		if ($this->getConfiguration('remote_id') != 'core') {
			return;
		}

		$cmd = $this->getCmd(null, 'refresh');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Rafraichir', __FILE__));
			$cmd->setOrder(0);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('refresh');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();

		$cmd = $this->getCmd(null, 'ping');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Joignable', __FILE__));
			$cmd->setTemplate('mobile', 'line');
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(1);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('ping');
		$cmd->setType('info');
		$cmd->setSubType('binary');
		$cmd->save();

		$cmd = $this->getCmd(null, 'state');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Status', __FILE__));
			$cmd->setTemplate('mobile', 'line');
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(2);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('state');
		$cmd->setType('info');
		$cmd->setSubType('binary');
		$cmd->save();

		$cmd = $this->getCmd(null, 'version');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Version', __FILE__));
			$cmd->setTemplate('mobile', 'line');
			$cmd->setTemplate('dashboard', 'line');
			$cmd->setOrder(3);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('version');
		$cmd->setType('info');
		$cmd->setSubType('string');
		$cmd->save();

		$cmd = $this->getCmd(null, 'restart');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Redémarrer', __FILE__));
			$cmd->setOrder(4);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('restart');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();

		$cmd = $this->getCmd(null, 'halt');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Arrêter', __FILE__));
			$cmd->setOrder(5);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('halt');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();

		$cmd = $this->getCmd(null, 'update');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Mettre à jour', __FILE__));
			$cmd->setOrder(6);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('update');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();

		$cmd = $this->getCmd(null, 'backup');
		if (!is_object($cmd)) {
			$cmd = new jeelinkCmd();
			$cmd->setName(__('Lancer un backup', __FILE__));
			$cmd->setOrder(7);
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('backup');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();
	}

	/*     * **********************Getteur Setteur*************************** */
}

class jeelinkCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		$eqLogic = $this->getEqLogic();
		if ($eqLogic->getConfiguration('remote_id') != 'core' && $this->getConfiguration('remote_id') == '') {
			throw new Exception(__('Le remote ID ne peut etre vide', __FILE__));
		}
		if ($eqLogic->getConfiguration('remote_id') != 'core') {
			$this->setLogicalId('remote::' . $this->getConfiguration('remote_id') . '::' . $eqLogic->getConfiguration('remote_apikey'));
		}
	}

	public function execute($_options = array()) {
		$eqLogic = $this->getEqLogic();
		if ($eqLogic->getConfiguration('remote_id') != 'core') {
			$url = $eqLogic->getConfiguration('remote_address') . '/core/api/jeeApi.php?type=cmd&apikey=' . $eqLogic->getConfiguration('remote_apikey');
			$url .= '&id=' . $this->getConfiguration('remote_id');
			if (count($_options) > 0) {
				foreach ($_options as $key => $value) {
					$url .= '&' . $key . '=' . urlencode($value);
				}
			}
			$request_http = new com_http($url);
			$request_http->exec(60);
		}

		if ($this->getLogicalId() == 'refresh') {
			$eqLogic->updateSysInfo();
			return;
		}

		$jsonrpc = $eqLogic->getJsonRpc();
		if ($this->getLogicalId() == 'restart') {
			if (!$jsonrpc->sendRequest('jeeNetwork::reboot')) {
				throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
			}
		}

		if ($this->getLogicalId() == 'halt') {
			if (!$jsonrpc->sendRequest('jeeNetwork::halt')) {
				throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
			}
		}

		if ($this->getLogicalId() == 'update') {
			if (!$jsonrpc->sendRequest('update::update')) {
				throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
			}
		}

		if ($this->getLogicalId() == 'backup') {
			if (!$jsonrpc->sendRequest('jeeNetwork::backup')) {
				throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
			}
		}

		if (strpos($this->getLogicalId(), 'deamonStop') !== false) {
			if (!$jsonrpc->sendRequest('plugin::deamonStop', array('plugin_id' => $this->getConfiguration('remote_plugin_id')))) {
				throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
			}
		}

		if (strpos($this->getLogicalId(), 'deamonStart') !== false) {
			if (!$jsonrpc->sendRequest('plugin::deamonStart', array('plugin_id' => $this->getConfiguration('remote_plugin_id')))) {
				throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
			}
		}

		$eqLogic->updateSysInfo();

	}

	/*     * **********************Getteur Setteur*************************** */
}

class jeelink_master {
	/*     * *************************Attributs****************************** */
	private $id;
	private $name;
	private $address;
	private $apikey;
	private $configuration;

	/*     * ***********************Methode static*************************** */

	public static function byId($_id) {
		$values = array(
			'id' => $_id,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM jeelink_master
		WHERE id=:id';
		return DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function all() {
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
		FROM jeelink_master';
		return DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function sendEvent($_options) {
		$jeelink_master = self::byId($_options['master_id']);
		if (!is_object($jeelink_master)) {
			return;
		}
		$url = $jeelink_master->getAddress() . '/core/api/jeeApi.php?apikey=' . $jeelink_master->getApikey();
		$url .= '&type=jeelink';
		$url .= '&remote_cmd_id=' . $_options['event_id'];
		$url .= '&remote_cmd_value=' . urlencode($_options['value']);
		$url .= '&remote_apikey=' . config::byKey('api');
		$request_http = new com_http($url);
		$request_http->exec(60);
	}

	/*     * *********************Methode d'instance************************* */

	public function removeListener() {
		$listeners = listener::byClass(__CLASS__);
		foreach ($listeners as $listener) {
			if ($listener->getFunction() != 'sendEvent') {
				continue;
			}
			$options = $listener->getOption();
			if (!isset($options['master_id']) || $options['master_id'] != $this->getId()) {
				continue;
			}
			$listener->remove();
		}
	}

	public function save() {
		return DB::save($this);
	}

	public function postSave() {
		$this->removeListener();
		if (is_array($this->getConfiguration('eqLogics'))) {
			foreach ($this->getConfiguration('eqLogics') as $eqLogic_info) {
				$eqLogic = eqLogic::byId(str_replace('eqLogic', '', str_replace('#', '', $eqLogic_info['eqLogic'])));
				if (!is_object($eqLogic)) {
					continue;
				}
				$listener = new listener();
				$listener->setClass(__CLASS__);
				$listener->setFunction('sendEvent');
				$listener->setOption(array('background' => 0, 'master_id' => intval($this->getId()), 'eqLogic_id' => intval($eqLogic->getId())));
				$listener->emptyEvent();
				foreach ($eqLogic->getCmd('info') as $cmd) {
					$listener->addEvent('#' . $cmd->getId() . '#');
				}
				$listener->save();
			}
		}
		$this->sendEqlogicToMaster();
	}

	public function preRemove() {
		$this->removeListener();
	}

	public function remove() {
		return DB::remove($this);
	}

	public function sendEqlogicToMaster() {
		$toSend = array(
			'eqLogics' => array(),
			'address' => network::getNetworkAccess($this->getConfiguration('network::access')),
			'remote_apikey' => config::byKey('api'),
			'name' => config::byKey('name', 'core', 'Jeedom'),
		);
		if (is_array($this->getConfiguration('eqLogics'))) {
			foreach ($this->getConfiguration('eqLogics') as $eqLogic_info) {
				$eqLogic = eqLogic::byId(str_replace('eqLogic', '', str_replace('#', '', $eqLogic_info['eqLogic'])));
				if (!is_object($eqLogic)) {
					continue;
				}
				$toSend['eqLogics'][$eqLogic->getId()] = utils::o2a($eqLogic);
				$toSend['eqLogics'][$eqLogic->getId()]['object_name'] = '';
				$object = $eqLogic->getObject();
				if (is_object($object)) {
					$toSend['eqLogics'][$eqLogic->getId()]['object_name'] = $object->getName();
				}
				unset($toSend['eqLogics'][$eqLogic->getId()]['html']);
				$toSend['eqLogics'][$eqLogic->getId()]['cmds'] = array();
				foreach ($eqLogic->getCmd() as $cmd) {
					$toSend['eqLogics'][$eqLogic->getId()]['cmds'][$cmd->getId()] = utils::o2a($cmd);
				}
			}
		}
		$toSend['deamons'] = array();
		foreach (plugin::listPlugin(true) as $plugin) {
			if ($plugin->getHasOwnDeamon() != 1) {
				continue;
			}
			$toSend['deamons'][] = array('id' => $plugin->getId(), 'name' => $plugin->getName());
		}
		$params = array(
			'apikey' => $this->getApikey(),
			'plugin' => 'jeelink',
		);
		$jsonrpc = new jsonrpcClient($this->getAddress() . '/core/api/jeeApi.php', '', $params);
		$jsonrpc->setNoSslCheck(true);
		if (!$jsonrpc->sendRequest('createEqLogic', $toSend, 300)) {
			throw new Exception($jsonrpc->getError(), $jsonrpc->getErrorCode());
		}
	}

	/*     * **********************Getteur Setteur*************************** */

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getAddress() {
		return $this->address;
	}

	public function setAddress($address) {
		$this->address = $address;
	}

	public function getApikey() {
		return $this->apikey;
	}

	public function setApikey($apikey) {
		$this->apikey = $apikey;
	}

	public function getConfiguration($_key = '', $_default = '') {
		return utils::getJsonAttr($this->configuration, $_key, $_default);
	}

	public function setConfiguration($_key, $_value) {
		$this->configuration = utils::setJsonAttr($this->configuration, $_key, $_value);
	}

}

?>
