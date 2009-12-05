<?php

uses('model');

class HeartbeatModel extends Model
{
	protected $pulses;
	protected $beganPulsing;
	protected $hostname;
	
	public static function getInstance($args = null, $className = null)
	{
		if(null === $args) $args = array();
		if(!isset($args['db'])) $args['db'] = HEARTBEAT_IRI;
		if(null === $className) $className = 'HeartbeatModel';
		return Model::getInstance($args, $className);
	}

	public function __construct($args)
	{
		parent::__construct($args);
		if(defined('HOST_NAME'))
		{
			$this->hostname = HOST_NAME;
		}
		else if(defined('INSTANCE_NAME'))
		{
			$this->hostname = INSTANCE_NAME;
		}
		else
		{
			$n = explode('.', php_uname('n'));
			$this->hostname = $n[0];
		}
	}

	protected function begin()
	{
		$this->db->begin();
		do
		{
			if($this->db->row('SELECT "pulse_timestamp" FROM {heartbeat_pulse} WHERE "pulse_hostname" = ?', $this->hostname))
			{
				$this->db->rollback();
				break;
			}
			$this->db->insert('heartbeat_pulse', array(
				'pulse_hostname' => $this->hostname,
				'@pulse_timestamp' => $this->db->now(),
				'pulse_info' => null,
			));
		}
		while(!$this->db->commit());
		$this->beganPulsing = true;
	}

	public function resetPulse()
	{
		$this->beganPulsing = false;
	}

	public function pulse($data)
	{
		if(!$this->beganPulsing)
		{
			$this->begin();
			$this->beganPulsing = false;
		}
		if(is_object($data) || is_array($data))
		{
			$data = json_encode($data);
		}
		$this->db->exec('UPDATE {heartbeat_pulse} SET "pulse_timestamp" = ' . $this->db->now() . ', "pulse_info" = ? WHERE "pulse_hostname" = ?', $data, $this->hostname);
	}
	
	public function refresh()
	{
		$this->pulses = array();
		$rows = $this->db->rows('SELECT * FROM {heartbeat_pulse}');
		foreach($rows as $row)
		{
			$row['unixtime'] = strtotime($row['pulse_timestamp']);
			if(strlen($row['pulse_info']))
			{
				$row['info'] = @json_decode($row['pulse_info'], true);
			}
			else
			{
				$row['info'] = null;
			}
			$this->pulses[$row['pulse_hostname']] = $row;
		}
	}
	
	public function lastPulse($host = null)
	{
		if(!$host)
		{
			$host = $this->hostname;
		}
		if(!$this->pulses)
		{
			$this->refresh();
		}
		if(isset($this->pulses[$host]))
		{
			return $this->pulses[$host];
		}
		return null;
	}
}
