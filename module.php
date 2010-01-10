<?php

/*
 * Copyright 2010 Mo McRoberts.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The names of the author(s) of this software may not be used to endorse
 *    or promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL
 * AUTHORS OF THIS SOFTWARE BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

uses('module');

class HeartbeatModule extends Module
{
	public $latestVersion = 1;
	public $moduleId = 'com.nexgenta.heartbeat';
	
	public static function getInstance($args = null, $className = null, $defaultDbIri = null)
	{
		return Model::getInstance($args, ($className ? $className : 'HeartbeatModule'), ($defaultDbIri ? $defaultDbIri : HEARTBEAT_IRI));
	}

	public function updateSchema($targetVersion)
	{
		if($targetVersion == 1)
		{
			$t = $this->db->schema->tableWithOptions('heartbeat_pulse', DBTable::CREATE_ALWAYS);
			$t->columnWithSpec('pulse_hostname', DBTable::VARCHAR, 64, DBTable::NOT_NULL, null, 'Source of this heartbeat');
			$t->columnWithSpec('pulse_timestamp', DBTable::DATETIME, null, DBTable::NOT_NULL, null, 'Timestamp of the heartbeat');
			$t->columnWithSpec('pulse_info', DBTable::TEXT, null, DBTable::NULLS, null, 'JSON-encoded heartbeat data');
			$t->indexWithSpec(null, DBIndex::PRIMARY, 'pulse_hostname');
			return $t->apply();
		}
	}
}
