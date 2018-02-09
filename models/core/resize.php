<?php
/*
 * @Author: Slash Web Design
 */

class Resize
{
	protected $image;
    protected $width;
    protected $height;
	protected $imageResized;

	function __construct($fileName)
	{
		$this->image = $this->openImage($fileName);

	    $this->width  = imagesx($this->image);
	    $this->height = imagesy($this->image);
	}

	protected function openImage($file)
	{
		return @imagecreatefromstring(file_get_contents($file));
	}

	public function resizeImage($newWidth, $newHeight, $option="auto")
	{
		$optionArray = $this->getDimensions($newWidth, $newHeight, $option);

		$optimalWidth  = $optionArray['optimalWidth'];
		$optimalHeight = $optionArray['optimalHeight'];


		$this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
		imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

		if ($option == 'crop')
		{
			$this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
		}
	}

	protected function getDimensions($newWidth, $newHeight, $option)
	{
	   switch ($option)
		{
			case 'exact':
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
				break;
			case 'portrait':
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
				break;
			case 'landscape':
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
				break;
			case 'auto':
				$optionArray = $this->getSizeByAuto($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
			case 'crop':
				$optionArray = $this->getOptimalCrop($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
		}
		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	protected function getSizeByFixedHeight($newHeight)
	{
		$ratio = $this->width / $this->height;
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}

	protected function getSizeByFixedWidth($newWidth)
	{
		$ratio = $this->height / $this->width;
		$newHeight = $newWidth * $ratio;
		return $newHeight;
	}

	protected function getSizeByAuto($newWidth, $newHeight)
	{
		if ($this->height < $this->width)
		{
			$optimalWidth = $newWidth;
			$optimalHeight= $this->getSizeByFixedWidth($newWidth);
		}
		elseif ($this->height > $this->width)
		{
			$optimalWidth = $this->getSizeByFixedHeight($newHeight);
			$optimalHeight= $newHeight;
		}
		else
		{
			if ($newHeight < $newWidth)
			{
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
			}
			else if ($newHeight > $newWidth)
			{
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
			}
			else
			{
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
			}
		}

		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	protected function getOptimalCrop($newWidth, $newHeight)
	{

		$heightRatio = $this->height / $newHeight;
		$widthRatio  = $this->width /  $newWidth;

		if ($heightRatio < $widthRatio)
		{
			$optimalRatio = $heightRatio;
		}
		else
		{
			$optimalRatio = $widthRatio;
		}

		$optimalHeight = $this->height / $optimalRatio;
		$optimalWidth  = $this->width  / $optimalRatio;

		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	protected function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
	{
		$cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
		$cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

		$crop = $this->imageResized;

		$this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
		imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
	}

	public function saveImage($savePath, $imageQuality="100")
	{
		$extension = strrchr($savePath, '.');
		$extension = strtolower($extension);
		$extension = ".jpg";

		switch($extension)
		{
			case '.jpg':
			case '.jpeg':
				if (imagetypes() & IMG_JPG)
				{
					imagejpeg($this->imageResized, $savePath, $imageQuality);
				}
				break;

			case '.gif':
				if (imagetypes() & IMG_GIF)
				{
					imagegif($this->imageResized, $savePath);
				}
				break;

			case '.png':
				$scaleQuality = round(($imageQuality/100) * 9);

				$invertScaleQuality = 9 - $scaleQuality;

				if (imagetypes() & IMG_PNG)
				{
					 imagepng($this->imageResized, $savePath, $invertScaleQuality);
				}
				break;

			default:
				break;
		}
	}

	public function printImage($type)
	{
		if ($type == "jpeg") imagejpeg($this->imageResized);
		if ($type == "png") imagepng($this->imageResized);
	}
}
?>