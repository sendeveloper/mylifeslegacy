<?php
/*
 * @Author: Slash Web Design
 */

class qqUploadedFileXhr
{
    public function save($path)
	{
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()) return false;

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }

    public function getName()
	{
        return $_GET['qqfile'];
    }

    public function getSize()
	{
        if (isset($_SERVER["CONTENT_LENGTH"]))
		{
            return (int)$_SERVER["CONTENT_LENGTH"];
        }
		else
		{
            throw new Exception('Getting content length is not supported.');
        }
    }
}

class qqUploadedFileForm
{
    public function save($path)
	{
        return move_uploaded_file($_FILES['qqfile']['tmp_name'], $path);
    }

    public function getName()
	{
        return $_FILES['qqfile']['name'];
    }

    public function getSize()
	{
        return $_FILES['qqfile']['size'];
    }
}

class Uploader {
	
    protected $allowedExtensions;
    protected $sizeLimit;
    protected $file;
	protected $uploadName;

    function __construct(array $allowedExtensions = null, $sizeLimit = null)
	{
    	if($allowedExtensions === null)
		{
    		$allowedExtensions = array();
    	}
    	
		if($sizeLimit === null)
		{
    		$sizeLimit = $this->toBytes(ini_get('post_max_size'));
    	}

        $allowedExtensions = array_map("strtolower", $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        $this->checkServerSettings();

        if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') === 0)
		{
            $this->file = new qqUploadedFileForm();
        }
		else
		{
            $this->file = new qqUploadedFileXhr();
        }
    }

	public function getUploadName()
	{
		if (isset($this->uploadName)) return $this->uploadName;
	}

	public function getName()
	{
		if ($this->file) return $this->file->getName();
	}

    protected function checkServerSettings()
	{
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit)
		{
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
			header("Content-type: application/json; charset=utf-8");
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
        }
    }

    protected function toBytes($str)
	{
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        
		switch($last)
		{
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    public function handleUpload($uploadDirectory, $replaceOldFile = true, $anyFile = false)
	{
        if (!is_writable($uploadDirectory)) return array('error' => "Server error. Upload directory isn't writable.");

        if (!$this->file) return array('error' => 'No files were uploaded.');

        $size = $this->file->getSize();

        if ($size == 0) return array('error' => 'File is empty');

        if ($size > $this->sizeLimit) return array('error' => 'File is too large');

        $pathinfo = pathinfo($this->file->getName());
        //$filename = $pathinfo['filename'];
        $filename = md5(uniqid());
        $ext = @$pathinfo['extension'];

        if ($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions))
		{
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
        }

        $ext = ($ext == '') ? $ext : '.' . $ext;

		$this->uploadName = $filename . $ext;
		
		if ($this->file->save($uploadDirectory . $filename . $ext))
		{
			$status = $this->__processFile($uploadDirectory . $filename . $ext, strtolower($ext));
			return array(
				'success'	=>	true,
				'status'	=>	$status,
				'path'		=>	$uploadDirectory . $filename . $ext
			);
		}

		return array('error'=> 'Could not save uploaded file. The upload was cancelled, or server error encountered.');
    }
	
	protected function __processFile($path, $ext)
	{
		if ($ext === '.jpeg' || $ext === '.jpg' || $ext === '.tiff')
		{
			$image = new OFImage($path); 
			$image->fix(); 
			return $image->save($path);
		}
		
		return 'unprocessed';
	}
}
?>