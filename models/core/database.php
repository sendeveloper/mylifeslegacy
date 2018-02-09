<?php
/*
 * @Author: Slash Web Design
 */

class Database extends PDO {

	public $qs = array();

	protected $error;
	protected $sql;
	protected $bind;
	protected $errorCallbackFunction;
	protected $errorMsgFormat;
	
	public function __construct($dsn, $user="", $passwd="")
	{
		$options = array(
			PDO::ATTR_PERSISTENT		=> true,
			PDO::ATTR_ERRMODE			=> PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND=> "SET NAMES utf8"
		);

		try
		{
			parent::__construct($dsn, $user, $passwd, $options);
		}
		catch (PDOException $e)
		{
			$this->error = $e->getMessage();
			echo $this->error . "<br />";
			echo $this->sql . "<br />";
			echo $bt . "<br />";
			die();
		}
	}

	public function insert($table, $info)
	{
		$fields = $this->filter($table, $info);
		$sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
		$bind = array();
		foreach($fields as $field)
		{
			$bind[":$field"] = $info[$field];
		}
		
		return $this->run($sql, $bind);
	}

	public function update($table, $info, $where, $bind = '')
	{
		$fields = $this->filter($table, $info);
		$fieldSize = sizeof($fields);

		$sql = "UPDATE " . $table . " SET ";
		for ($f = 0; $f < $fieldSize; ++$f)
		{
			if ($f > 0)	$sql .= ", ";
			$sql .= $fields[$f] . " = :update_" . $fields[$f];
		}
		$sql .= " WHERE " . $where . ";";

		$bind = $this->cleanup($bind);
		
		foreach($fields as $field)
		{
			$bind[":update_$field"] = $info[$field];
		}

		return $this->run($sql, $bind);
	}

	public function run($sql, $bind = '', $d = 0)
	{
		array_push($this->qs, $sql);
		
		$this->sql = $this->cleanSQL($sql);
		$this->bind = $this->cleanup($bind);
		$this->error = "";

		try {
			$pdostmt = $this->prepare($this->sql);
			if ($pdostmt->execute($this->bind) !== false)
			{
				if (preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql))
				{
					return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
				}
				elseif (preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->sql))
				{
					return $pdostmt->rowCount();
				}
			}
		}
		catch (PDOException $e)
		{
			$this->error = $e->getMessage();
			$this->debug();
			return false;
		}
	}

	protected function filter($table, $info)
	{
		$driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driver == 'sqlite')
		{
			$sql = "PRAGMA table_info('" . $table . "');";
			$key = "name";
		}
		elseif ($driver == 'mysql')
		{
			$sql = "DESCRIBE " . $table . ";";
			$key = "Field";
		}
		else
		{
			$sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
			$key = "column_name";
		}

		if (false !== ($list = $this->run($sql)))
		{
			$fields = array();
			
			foreach($list as $record)
			{
				$fields[] = $record[$key];
			}
			
			return array_values(array_intersect($fields, array_keys($info)));
		}
		return array();
	}

	protected function cleanup($bind)
	{
		if (!is_array($bind))
		{
			if (!empty($bind))
			{
				$bind = array($bind);
			}
			else
			{
				$bind = array();
			}
		}
		
		return $bind;
	}
	
	protected function cleanSQL($sql)
	{
		$out = str_replace("\r\n", " ", trim($sql));
		$out = str_replace("\t", " ", $out);
		$out = str_replace("   ", " ", $out);
		$out = str_replace("  ", " ", $out);
		return $out;
	}

	public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat = 'html')
	{
		if (in_array(strtolower($errorCallbackFunction), array("echo", "print"))) $errorCallbackFunction = "print_r";

		if (function_exists($errorCallbackFunction))
		{
			$this->errorCallbackFunction = $errorCallbackFunction;
			
			if(!in_array(strtolower($errorMsgFormat), array("html", "text"))) $errorMsgFormat = "html";
			$this->errorMsgFormat = $errorMsgFormat;
		}
	}

	protected function debug() 
	{
		global $glob, $helper;
		
		$obj = array(
			'error'			=>	$this->error,
			'sql'			=>	$this->sql,
			'glob'			=>	$glob,
			'backtrace'		=>	'',
			'query-stack'	=>	$this->qs
		);
		$backtrace = debug_backtrace();
		
		if (!empty($backtrace))
		{
			foreach($backtrace as $info)
			{
				if ($info["file"] != __FILE__) $obj['backtrace'][] = $info["file"] . " at line " . $info["line"];
			}
		}
		
		$helper->p($obj, 1);
	}
}
?>