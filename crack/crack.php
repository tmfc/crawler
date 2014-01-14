<?php
include("crack/Valite.php");

$filename = $argv[1];
ImageToJPG($filename,$filename);

$valite = new valite();

//$valite->png2jpeg($filename);
$valite->setImage($filename);//"0173.jpeg");
//$valite->setImage('9827.jpeg');
$valite->getHec();
$valite->filterInfo();
$valite->dealwithData();
//$valite->DrawDealData();
//$valite->Draw();
//echo "\n 结果是：";
$data = $valite->run();
echo implode("",$data);


function ImageToJPG($srcFile,$dstFile)
{
	$quality=80;
	$data = @GetImageSize($srcFile);
	switch ($data['2'])
	{
		case 1:
			$im = imagecreatefromgif($srcFile);
			break;
		case 2:
			return;
			//$im = imagecreatefromjpeg($srcFile);
			//break;
		case 3:
			$im = imagecreatefrompng($srcFile);
			break;
		case 6:
			//$im = ImageCreateFromBMP( $srcFile );
			break;
	}

	// $dstX=$srcW=@ImageSX($im);

	// $dstY=$srcH=@ImageSY($im);

	$srcW=@ImageSX($im);
	$srcH=@ImageSY($im);
	$dstX=$srcW;
	$dstY=$srcH;

	$ni=@imageCreateTrueColor($dstX,$dstY);

	@ImageCopyResampled($ni,$im,0,0,0,0,$dstX,$dstY,$srcW,$srcH);
	@ImageJpeg($ni,$dstFile,$quality);
	@imagedestroy($im);
	@imagedestroy($ni);

}
?>