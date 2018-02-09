<?php
/*
 * @Author: Slash Web Design						
 */

class dashboard
{
	var $db;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
	}
	
	public function getList(&$ld)
	{
		global $helper;
		
		$p = new Parser("dashboard.html");

		$user = $this->db->run("SELECT COUNT(member_id) AS total FROM member");
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Dashboard',
			
			'DATA_USER'		=>	json_encode($this->getMemberStats()),
			'TOTAL_USER'	=>	$user[0]['total'],
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	protected function getMemberStats()
	{
		global $helper;
		
		$datasets = array();
		
		$res = $this->db->run("SELECT date FROM member ORDER BY date ASC");
		foreach ($res as $r)
		{
			$datasets[0][] = $r['date'];
		}
		
		$this->formatDatasets($datasets);
		return $datasets;
	}
	
	protected function formatDatasets(&$datasets)
	{
		$arr = array();
		$colors = array('#0090ff', '#10cd41', '#265b83', '#11b03a');
		
		$ci = 0;
		foreach ($datasets as $label => $ds)
		{
			$data = array();
			
			foreach ($ds as $date)
			{
				$key = date('Y-m-d', $date);
				if (!isset($data[$key])) $data[$key] = 0;
				$data[$key]++;
			}
			
			$arr2 = array();
			foreach ($data as $key => $count)
			{
				$arr2[] = array('x' => $key, 'y' => $count);
			}
			$data = $arr2;
			unset($arr2);
			
			$arr[] = array(
				'label'			=>	$label,
				'strokeColor'	=>	$colors[$ci],
				'data'			=>	$data
			);
			$ci++;
		}
		
		$datasets = $arr;
		unset($arr);
	}
}
?>