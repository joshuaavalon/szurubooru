<?php
namespace Szurubooru\Services\ImageManipulation;

class ImagickImageManipulator implements IImageManipulator
{
	public function loadFromBuffer($source)
	{
		try
		{
			$image = new \Imagick();
			$image->readImageBlob($source);
			$image = $image->coalesceImages();
			return $image;
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	public function getImageWidth($imageResource)
	{
		return $imageResource->getImageWidth();
	}

	public function getImageHeight($imageResource)
	{
		return $imageResource->getImageHeight();
	}

	public function resizeImage(&$imageResource, $width, $height)
	{
		$imageResource->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 0.9);
	}

	public function cropImage(&$imageResource, $width, $height, $originX, $originY)
	{
		$imageResource->cropImage($width, $height, $originX, $originY);
		$imageResource->setImagePage(0, 0, 0, 0);
	}

	public function saveToBuffer($imageResource, $format)
	{
		switch ($format)
		{
			case self::FORMAT_JPEG:
				$imageResource->setImageBackgroundColor('white');
				$imageResource = $imageResource->flattenImages();
				$imageResource->setImageFormat('jpeg');
				break;

			case self::FORMAT_PNG:
				$imageResource->setImageFormat('png');
				break;

			default:
				throw new \InvalidArgumentException('Not supported');
		}

		return $imageResource->getImageBlob();
	}
}
