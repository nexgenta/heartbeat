<?php

uses('execute');

require_once(dirname(__FILE__) . '/model.php');

if(!defined('HEARTBEAT_PULSE_INTERVAL')) define('HEARTBEAT_PULSE_INTERVAL', 45);

class HeartbeatCLI extends CommandLine
{
	protected $modelClass = 'HeartbeatModel';
	
	protected function perform___CLI__()
	{
		$hostinfo = array(
			'uname' => array(
				'opsys' => php_uname('s'),
				'release' => php_uname('r'),
				'version' => php_uname('v'),
				'nodename' => php_uname('n'),
				'machine' => php_uname('m'),
			),
		);
		while(true)
		{
			try
			{
				$info = $hostinfo;
				$this->getDiskUsage($info);
				$this->getUptime($info);
				$this->model->pulse($info);
				sleep(HEARTBEAT_PULSE_INTERVAL);
			}
			catch(DBSystemException $e)
			{
				echo "Database system exception occurred: " . $e->getMessage() . "\n";
				echo "Retrying in 20 seconds...\n";
				$this->model->resetPulse();
				sleep(20);
			}
		}
	}
	
	protected function getDiskUsage(&$info)
	{
		if(!defined('POSIX_DF_CMDLINE')) return;
		$result = execute(POSIX_DF_CMDLINE);
		if(!$result || empty($result['status']['pid']) || (!empty($result['status']['exitcode']) && $result['status']['exitcode'] > 0))
		{
			return;
		}
		$lines = explode("\n", $result['stdout']);
		$header = array_shift($lines);
		$info['fs'] = array();
		foreach($lines as $line)
		{
			$line = trim($line);
			$matches = array();
			preg_match('/^(.*)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+(.*)$/', $line, $matches);
			if(isset($matches[6]))
			{
				$info['fs'][] = array(
					'device' => $matches[1],
					'total' => $matches[2],
					'used' => $matches[3],
					'available' => $matches[4],
					'capacity' => $matches[5],
					'mountpoint' => $matches[6],
				);
			}
		}
	}
	
	protected function getUptime(&$info)
	{
		if(!defined('UPTIME_PATH')) return;
		$result = execute(UPTIME_PATH);
		if(!$result || empty($result['status']['pid']) || (!empty($result['status']['exitcode']) && $result['status']['exitcode'] > 0))
		{
			return;
		}
		$lines = explode("\n", $result['stdout']);
		$line = trim(array_shift($lines));
		if(!strlen($line)) return;
		$matches = array();
		preg_match('/([0-9]+) user\(?s?\)?,\s+load averages?:?\s+([0-9]+\.[0-9]+),?\s+([0-9]+\.[0-9]+),?\s+([0-9]+\.[0-9]+)$/', $line, $matches);
		if(isset($matches[4]))
		{
			$info['users'] = $matches[1];
			$info['loadavg'] = array($matches[2], $matches[3], $matches[4]);
		}
	}
}
