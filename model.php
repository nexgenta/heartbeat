<?php

uses('model');

require_once(APPS_ROOT . 'cluster/model.php');

class HeartbeatModel extends Model
{
	protected $inst;
	protected $pulses;
	
	public static function getInstance($args = null, $className = null)
	{
		if(null === $args) $args = array();
		if(!isset($args['db'])) $args['db'] = HEARTBEAT_IRI;
		if(null === $className) $className = 'HeartbeatModel';
		return Model::getInstance($args, $className);
	}

	public function begin()
	{
		$cluster = ClusterModel::getInstance();
		$hostName = $cluster->hostName;
		$instances = $cluster->instancesOnHost($hostName);
		foreach($instances as $inst)
		{
			$clusterName = $cluster->clusterNameOfInstance($inst);
			$this->inst[$inst] = array('name' => $inst, 'cluster' => $clusterName);
		}
		foreach($this->inst as $inst)
		{
			do
			{
				$this->db->begin();
				if($this->db->row('SELECT "pulse_timestamp" FROM {heartbeat_pulse} WHERE "pulse_cluster" = ? AND "pulse_instance" = ?', $inst['cluster'], $inst['name']))
				{
					$this->db->rollback();
					break;
				}
				$this->db->insert('heartbeat_pulse', array(
					'pulse_cluster' => $inst['cluster'],
					'pulse_instance' => $inst['name'],
					'@pulse_timestamp' => $this->db->now(),
					'pulse_info' => null,
				));
			}
			while(!$this->db->commit());
		}		
	}

	public function pulse($data)
	{
		if(is_object($data) || is_array($data))
		{
			$data = json_encode($data);
		}
		foreach($this->inst as $inst)
		{
			$this->db->exec('UPDATE {heartbeat_pulse} SET "pulse_timestamp" = ' . $this->db->now() . ', "pulse_info" = ? WHERE "pulse_cluster" = ? AND "pulse_instance" = ?', $data, $inst['cluster'], $inst['name']);
		}
	}
	
	public function refresh()
	{
		$this->pulses = array();
		$rows = $this->db->rows('SELECT * FROM {heartbeat_pulse}');
		foreach($rows as $row)
		{
			if(!isset($this->pulses[$row['pulse_cluster']]))
			{
				$this->pulses[$row['pulse_cluster']] = array();
			}
			$row['unixtime'] = strtotime($row['pulse_timestamp']);
			if(strlen($row['pulse_info']))
			{
				$row['info'] = @json_decode($row['pulse_info'], true);
			}
			else
			{
				$row['info'] = null;
			}
			$this->pulses[$row['pulse_cluster']][$row['pulse_instance']] = $row;
		}
	}
	
	public function lastPulse($cluster, $instance)
	{
		if(!$this->pulses)
		{
			$this->refresh();
		}
		if(isset($this->pulses[$cluster][$instance]))
		{
			return $this->pulses[$cluster][$instance];
		}
		return null;
	}
}
