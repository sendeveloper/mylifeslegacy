<?php
/*
 * @Author: Slash Web Design
 */
ini_set("memory_limit", "256M");

class Timeline extends Core
{
	public		 $template = 'template-full.html';
	public   $loadExternal = array("https://cdn.webrtc-experiment.com/RecordRTC.js","https://tour.tourmyapp.com/widgets/widget/595163c5bc1fbe6e3d00301d/");
	public        $loadCSS = array('datepicker.css', 'fileuploader.css', 'timeline.css');
	public         $loadJS = array('modernizr.js', 'datepicker.js', 'fileuploader.js', 'slideshow.js', 'timeline.js');
	public		$hasAccess = false;
	protected $accessLevel = 3;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function rotate(&$ld)
	{
		$pos = stripos($ld['source'], '?');
		if ($pos !== false)	$ld['source'] = substr($ld['source'], 0, $pos);
		
		$img = imagecreatefromstring(file_get_contents($ld['source']));
		$img = imagerotate($img, $ld['angle'], 0);
		
		if ($img !== false)
		{
			switch (exif_imagetype($ld['source']))
			{
				case IMAGETYPE_JPEG:
					imagejpeg($img, $ld['source'], 100);
					break;
				
				case IMAGETYPE_GIF:
					imagegif($img, $ld['source']);
					break;
				
				case IMAGETYPE_PNG:
					imagepng($img, $ld['source'], 0);
					break;
			}

			imagedestroy($img);
			$this->helper->respond(array('status' => true, 'source' => $ld['source'] . "?v=" . time()));
		}
		
		$this->helper->respond(array('status' => false));
	}
	
	public function delete(&$ld)
	{
		$res = $this->db->run("SELECT files FROM event WHERE event_id = {$ld['event_id']}");
		$files = ($res[0]['files'] !== '') ? explode(';', $res[0]['files']) : array();
		foreach ($files as $f)
		{
			@unlink($f);
		}
		
		$this->db->run("DELETE FROM event WHERE event_id = {$ld['event_id']}");
		
		$this->helper->respond(array(
			'success'	=>	true, 
			'message'   =>	"Timeline event deleted",
			'categories' => $this->getCategories(),
			'events'	=>	$this->getEvents($ld)
		));
	}
	
	public function deleteFile(&$ld)
	{
		$res = $this->db->run("SELECT files FROM event WHERE event_id = {$ld['event_id']}");
		$files = explode(';', $res[0]['files']);
		$arr = array();
		
		foreach ($files as $f)
		{
			($f === $ld['file']) ? @unlink($f) : $arr[] = $f;
		}
		
		$this->db->update("event", array('files' => implode(';', $arr)), "event_id = {$ld['event_id']}");
		
		$this->helper->respond(array(
			'success'	=>	true, 
			'message'   =>	"Timeline event saved",
			'categories' => $this->getCategories(),
			'events'	=>	$this->getEvents($ld)
		));
	}

	public function save(&$ld)
	{
		$ld['data']['member_id'] = $this->user->id;
		$ld['data']['date'] = strtotime($ld['data']['date']);
		$today1 = strtotime(date("Y/m/d h:i:s"));
		$today2 = strtotime(date("Y/m/d 00:00:00"));
		$ld['data']['date'] += ($today1 - $today2);
		// var_dump($ld['data']['date']);
		// var_dump($today1-$today2);
		// get existing filesize(filename)
		if ($ld['event_id'] !== '0')
		{
			$res = $this->db->run("SELECT files FROM event WHERE event_id = {$ld['event_id']}");
			$arr = ($res[0]['files'] !== '') ? explode(";", $res[0]['files']) : array();
		}
		else
		{
			$arr = array();
		}
		
		if ($ld['data']['files'] !== '')
		{
			$files = explode(";", $ld['data']['files']);
			foreach ($files as $file)
			{
				$name = "uploads/event/" . microtime(true) . '.' . $this->user->id . '_' . str_replace("uploads/temp/", "", $file);
				rename($file, $name);
				$arr[] = $name;
			}
		}
		$ld['data']['files'] = implode(";", $arr);
		
		($ld['event_id'] === '0') ? $this->db->insert("event", $ld['data']) : $this->db->update("event", $ld['data'], "event_id = {$ld['event_id']}");
		
		$this->helper->respond(array(
			'success'	=>	true, 
			'message'   =>	"Timeline event saved",
			'events'	=>	$this->getEvents($ld),
			'categories' => $this->getCategories(),
			'arr'		=>	$arr
		));
	}
	public function fetchMoreEvents(&$ld)
	{
		$this->helper->respond(array('success' => true, 'events' => $this->getMoreEvents($ld)));
	}
	public function fetchEvents(&$ld)
	{
		$this->helper->respond(array('success' => true, 'events' => $this->getEvents($ld), 'categories' => $this->getCategories()));
	}
	private function getCategories()
	{
		$res = $this->db->run("SELECT category_id, name, dc FROM category ORDER BY sort_order ASC");
		$res[] = array('category_id' => -1, 'name' => 'All', 'dc' => 0);
		$total = 0;

		$cates = [];
		foreach ($res as $c)
		{
			$res2 = $this->db->run("SELECT COUNT(event_id) AS total FROM event WHERE member_id = {$this->user->id} AND category_id = {$c['category_id']}");
			$total += $res2[0]['total'];
			
			$counter = 0;
			if ($c['category_id'] === -1)
			{
				$counter = ($total > 99) ? '99+' : $total;
			}
			else
			{
				$counter = ($res2[0]['total'] > 99) ? '99+' : $res2[0]['total'];
			}

			$cates[$c['category_id']] = $counter;
		}
		return $cates;
	}
	private function getMoreEvents($ld)
	{
		$id = isset($ld['id']) ? $ld['id'] : $this->user->id;
		$categories = array();
		
		if ($ld['category_id'] !== '-1')
		{
			$categories[] = $ld['category_id'];
		}
		else
		{
			$res = $this->db->run("SELECT category_id FROM category");
			foreach ($res as $r)
			{
				$categories[] = $r['category_id'];
			}
		}
		$categories = implode(",", $categories);
		
		if ($ld['app_length'] > 0)
		{
			$sort_order = "DESC";
			if (isset($ld['sort_order']) && $ld['sort_order'] == 2)
				$sort_order = "ASC";

			$start_at = $ld['app_length'];
			$sql = "SELECT * FROM event WHERE member_id = {$id} AND category_id IN ({$categories}) ORDER BY date $sort_order limit 10 OFFSET $start_at";
			$res = $this->db->run($sql);
			foreach ($res as $key => $r)
			{
				$res[$key]['files'] = ($r['files'] !== '') ? explode(';', $r['files']) : array();
			}
			
			return $res;
		}
		else
			return array();
	}
	private function getEvents($ld)
	{
		$id = isset($ld['id']) ? $ld['id'] : $this->user->id;
		$categories = array();
		
		if ($ld['category_id'] !== '-1')
		{
			$categories[] = $ld['category_id'];
		}
		else
		{
			$res = $this->db->run("SELECT category_id FROM category");
			foreach ($res as $r)
			{
				$categories[] = $r['category_id'];
			}
		}
		$categories = implode(",", $categories);
		
		$sort_order = "DESC";
		if (isset($ld['sort_order']) && $ld['sort_order'] == 2)
			$sort_order = "ASC";

		$current_length = 10;
		if (isset($ld['current_length']) && (int)$ld['current_length'] > 0)
			$current_length = $ld['current_length'];

		$sql = "SELECT * FROM event WHERE member_id = {$id} AND category_id IN ({$categories}) ORDER BY date $sort_order limit $current_length";
		// var_dump($ld);
		$res = $this->db->run($sql);
		foreach ($res as $key => $r)
		{
			$res[$key]['files'] = ($r['files'] !== '') ? explode(';', $r['files']) : array();
		}
		
		return $res;
	}

	public function fetch()
	{
		$p = new Parser("timeline.html");
		$p->defineBlock('item');
		$p->defineBlock('item_menu');
		$p->defineBlock('category');
		
		$defaultId = 0;
		$total = 0;

		$res = $this->db->run("SELECT category_id, name, dc FROM category ORDER BY sort_order ASC");
		
		// $res[] = array('category_id' => 0, 'name' => 'Uncategorized', 'dc' => 0);
		$res[] = array('category_id' => -1, 'name' => 'All', 'dc' => 0);
		foreach ($res as $c)
		{
			$res2 = $this->db->run("SELECT COUNT(event_id) AS total FROM event WHERE member_id = {$this->user->id} AND category_id = {$c['category_id']}");
			$total += $res2[0]['total'];
			
			$counter = 0;
			if ($c['category_id'] === -1)
			{
				$counter = ($total > 99) ? '99+' : $total;
			}
			else
			{
				$counter = ($res2[0]['total'] > 99) ? '99+' : $res2[0]['total'];
			}
			
			$p->parseBlock(array(
				'ITEM_ID'	=>	$c['category_id'],
				'ITEM_NAME'	=>	$c['name'],
				'ITEM_COUNT'=>	$counter
			), 'item');
			
			$p->parseBlock(array(
				'ITEM_ID'	=>	$c['category_id'],
				'ITEM_NAME'	=>	$c['name'],
				'ITEM_COUNT'=>	$counter
			), 'item_menu');
			if ($c['dc'] === '1') $defaultId = $c['category_id'];
		}
		foreach ($res as $c)
		{
			if ($c['category_id'] == -1) continue;
			$p->parseBlock(array(
				'CATEGORY_ID'	=>	$c['category_id'],
				'CATEGORY_NAME'	=>	$c['name'],
			), 'category');
		}
		
		$p->parseValue(array(
			'DEFAULT_ID'	=>	$defaultId
		));
		return $p->fetch();
	}
}
?>