<?php

class OFImage
{
	protected $filePath;
	protected $resource;
	protected $exif;
	protected $dirty = FALSE;
	
	const IMG_FLIP_HORIZONTAL = 1;
	const IMG_FLIP_VERTICAL = 2;
	const IMG_FLIP_BOTH = 3;
	
	public function __construct($path)
	{
		$this->filePath = $path;
		
		if (!file_exists($this->filePath)) return 'Given file does not exists!';
		if (!is_file($this->filePath)) return 'Given path does not point to a file!';
		
		if ($this->readEXIF()) return 'EXIF data not found';
		
		if (!$this->loadImageResource()) return 'Unable to load image';
	}
	
	protected function readEXIF()
	{
		$exif = @read_exif_data($this->filePath, 'IFD0');
		
		if (!$exif || !is_array($exif)) return false;

		$this->exif = array_change_key_case( $exif, CASE_LOWER );
	}
	
	protected function loadImageResource()
	{
		ini_set("memory_limit", "256M");
		$img = @imagecreatefromstring(file_get_contents($this->filePath));
		
		if (is_null($img)) return false;

		$this->resource = $img;
	}
	
	protected function saveImageResource($dest)
	{
		imagejpeg($this->resource, $dest);
		return 'Image orientation fixed';
	}
	
	protected function flip( $mode ) {
		if($mode == self::IMG_FLIP_VERTICAL || $mode == self::IMG_FLIP_BOTH) {
			$this->resource = imagerotate($this->resource, 180, 0);
			$this->dirty = TRUE;
		}
		if($mode == self::IMG_FLIP_HORIZONTAL || $mode == self::IMG_FLIP_BOTH) {
			$this->dirty = TRUE;
			$this->resource = imagerotate($this->resource, 90, 0);
		}
		return $this;
	}
	
	protected function rotate( $angle ) {
		$this->dirty = TRUE;
		$this->resource = imagerotate( $this->resource, $angle, 0 );
		return $this;
	}
	
	public function fix() {
		
		if( !$this->exif || ($this->exif && !array_key_exists( 'orientation', $this->exif ) ) )
			return FALSE;
		$imageOrientation = $this->exif['orientation'];
		$resource = $this->resource;
		switch( $imageOrientation ) {
			case 1:
				return TRUE;
				break;
			case 2:
				$this->flip( self::IMG_FLIP_HORIZONTAL );
				break;
			case 3:
				$this->flip( self::IMG_FLIP_VERTICAL );
				break;
			case 4:
				$this->flip( self::IMG_FLIP_BOTH );
				break;
			case 5:
				$this->rotate( -90 )->flip( self::IMG_FLIP_HORIZONTAL );
				break;
			case 6:
				$this->rotate( -90 );
				break;
			case 7:
				$this->rotate( 90 )->flip( self::IMG_FLIP_HORIZONTAL );
				break;
			case 8:
				$this->rotate( 90 );
				break;
		}
		return TRUE;
	}
	
	public function save($dest)
	{
		if ($this->dirty) return $this->saveImageResource($dest);
		
		return 'Image orientation fixed';
	}
}