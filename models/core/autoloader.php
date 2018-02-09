<?php 
/*
 * @Author: Slash Web Design
 */

class Autoloader 
{
    static public function loader($className)
	{
		$path = array('models/core/', 'models/', '../models/core/', '../models/');
	
		foreach ($path as $p)
		{
			$file = $p . strtolower($className). ".php";
			
			if (file_exists($file))
			{
				require $file;
	            if (class_exists($className)) return true;
			}
		}
		
		echo "Unable to load object {$className}";
		die();
    }
}

spl_autoload_register('Autoloader::loader');
?>