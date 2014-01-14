<?php
// author email: ugg.xchj@gmail.com
// 本代码仅供学习参考，不提供任何技术保证。
// 切勿使用本代码用于非法用处，违者后果自负。


include_once("files.php");

class valite
{
	public function setImage($Image)
	{
		$this->ImagePath = $Image;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setKeyValue($key,$value)
	{
		if(isset($this->Keys[$key]))
			return $this->Keys[$key];
		else 
			$this->Keys[$key]=$value;
		return $value;
	}

	public function getResult()
	{
		return $this->DataArray;
	}

	public function getHec()
	{
		$res = imagecreatefromjpeg($this->ImagePath);
		$size = getimagesize($this->ImagePath);
		$data = array();
		for($i=0; $i < $size[1]; ++$i)
		{
			for($j=0; $j < $size[0]; ++$j)
			{
				$rgb = imagecolorat($res,$j,$i);
				$rgbarray = imagecolorsforindex($res, $rgb);
				//echo $i . ' ' . $j . ' ' . $rgbarray['red']  . '|' . $rgbarray['green'] . '|' . $rgbarray['blue'] . "\r\n";
				// =========================================================
				// 任何验证码的数字和字母部分为了和验证码图片背景有所区别
				// 都必须对文字和背景图片的RGB进行区分，下面的值是我根据
				// 验证码的图片进行区分的，您可以分析您的图片，找到如下规律
				// =========================================================
				if($rgbarray['red'] < 125 || $rgbarray['green'] < 125 || $rgbarray['blue'] < 125)
				//if($rgbarray['red'] < 200 || $rgbarray['green'] < 200 || $rgbarray['blue'] < 200)
				{
					$data[$i][$j]=1;
				}else{
					$data[$i][$j]=0;
				}
			}
		}

		// 首列1
		for($j=0; $j < $size[1]; ++$j)
		{
			$data[$j][0]=0;
		}
		//var_dump($data);
		$this->DataArray = $data;
		$this->ImageSize = $size;
	}

	public function run()
	{
		// 做成字符串
		// 做成字符串
		$data = array();
		$i = 0;

		foreach($this->dealData as $key => $value)
		{
			$data[$i] = "";
			foreach($value as $skey => $svalue)
			{
				$data[$i] .= implode("",$svalue);
			}
			++$i;
		}

		// 进行关键字匹配
		foreach($data as $numKey => $numString)
		{
			$max=0.0;
			$num = 0;
			if(isset($this->Keys[$numString]))
			{
				$this->result[$numKey] = $this->Keys[$numString];
			}else{
				foreach($this->Keys as $key => $value)
				{
					$FindOk = false;
					$percent=0.0;
//					echo "keys:".$key."\n";
//					echo "value:".$value."\n";
					similar_text($key, $numString,$percent);
//					print_r($percent);
//					echo " ";
					if(intval($percent) > $max)
					{
						$max = $percent;
						$num = $value;
						if(intval($percent) > 98){
							$FindOk = true;
							break;
						}
					}
				}
				$this->result[$numKey]=$num;
			}
		}

		// 查找最佳匹配数字
		return $this->result;
	}

	public function bmp2jpeg($file){
		$res = $this->imagecreatefrombmp($file);
		imagejpeg($res,$file.".jpeg");
	}
	
	public function png2jpeg($file){
		$source = @imagecreatefrompng($file);
		$size=getimagesize($file);
		$newwidth=$size[0];//新图像的宽
		$newheight=$size[1];//新图像的高
		//本函数转换后的图像宽高跟原图一样，这里只是说明，如果你需要改变新图像的宽高如何设置
		$im = imagecreatetruecolor($newwidth, $newheight);
		imagecopyresized($im, $source, 0, 0, 0, 0, $newwidth, $newheight,$size[0],$size[1]);
		imagejpeg($im,$file);//test.jpg是转换后的新图像名 
	}

	public function filterInfo()
	{
		$data=array();
		$num = 0;
		$b = false;
		$Continue = 0;
		$XStart = 0;

		for($y=0; $y<$this->ImageSize[1]; ++$y)
		{

			if($y < 9 || $y > 16)
			{
				$xstart = -1;
				$num = 0;
				for($x=1; $x<$this->ImageSize[0]; ++$x)
				{
					if($this->DataArray[$y][$x] == 1)
					{
						if($xstart == -1){
							$xstart = $x;
						}
					}
					if($num > 8)
					{
						for($xt=$xstart; $xt<$this->ImageSize[0]; ++$xt){
							if($this->DataArray[$y][$xt] == 1)
							{
								$this->DataArray[$y][$xt] = 0;
							}else{
								$x = $xt-1;
								break;
							}
						}
					}
					if($this->DataArray[$y][$x-1] == 1 && $this->DataArray[$y][$x] == 1){
						++$num;
					}else{
						$xstart = -1;
						$num = 0;
					}
				}
			}

		}

		// 如果1的周围数字不为1，修改为了0
		for($i=0; $i < $this->ImageSize[1]; ++$i)
		{
			for($j=0; $j < $this->ImageSize[0]; ++$j)
			{
				$num = 0;
				if($this->DataArray[$i][$j] == 1)
				{
					// 上
					if(isset($this->DataArray[$i-1][$j])){
						$num = $num + $this->DataArray[$i-1][$j];
					}
					// 下
					if(isset($this->DataArray[$i+1][$j])){
						$num = $num + $this->DataArray[$i+1][$j];
					}
					// 左
					if(isset($this->DataArray[$i][$j-1])){
						$num = $num + $this->DataArray[$i][$j-1];
					}
					// 右
					if(isset($this->DataArray[$i][$j+1])){
						$num = $num + $this->DataArray[$i][$j+1];
					}
					// 上左
					if(isset($this->DataArray[$i-1][$j-1])){
						$num = $num + $this->DataArray[$i-1][$j-1];
					}
					// 上右
					if(isset($this->DataArray[$i-1][$j+1])){
						$num = $num + $this->DataArray[$i-1][$j+1];
					}
					// 下左
					if(isset($this->DataArray[$i+1][$j-1])){
						$num = $num + $this->DataArray[$i+1][$j-1];
					}
					// 下右
					if(isset($this->DataArray[$i+1][$j+1])){
						$num = $num + $this->DataArray[$i+1][$j+1];
					}
				}
				if($num < 3){
					$this->DataArray[$i][$j] = 0;
				}
			}
		}
/*
		// 末尾部分处理

		for($j=17; $j < $this->ImageSize[1]; ++$j)
		{
			for($i = 51; $i <$this->ImageSize[0]; ++$i)
			{
				$this->DataArray[$j][$i]=0;
			}
		}
		for($j=0; $j < 5; ++$j)
		{
			for($i = 51; $i <$this->ImageSize[0]; ++$i)
			{
				$this->DataArray[$j][$i]=0;
			}
		}
*/
		// X 坐标
		for($i=0; $i<$this->ImageSize[0]; ++$i)
		{
			// Y 坐标
			for($j=0; $j<$this->ImageSize[1]; ++$j)
			{
				if($this->DataArray[$j][$i] == 1 || ($Continue > 0 && $Continue < 5))
				{
					$b = true;
					++$Continue;	
					break;
				}else{
					$b = false;
				}
			}
			if($b == true)
			{
				for($jj = 0; $jj < $this->ImageSize[1]; ++$jj)
				{
					$data[$num][$jj][$XStart] = $this->DataArray[$jj][$i];
				}
				++$XStart;

			}else{
				if($Continue > 0){
					$XStart = 0;
					$Continue = 0;
					++$num;
				}
			}
		}

		// 粘连字符分割
		$inum = 0;
		for($num =0; $num < count($data); ++$num)
		{
			$itemp = 5;
			$str = implode("",$data[$num][$itemp]);
			// 超过标准长度
			if(strlen($str) > $this->maxfontwith)
			{
				$len = (strlen($str)+1)/2;
				$flen = strlen($str);
				$ih = 0;
				//				$iih = 0;
				foreach($data[$num] as $key => $value)
				{
					$ix = 0;
					$ixx = 0;
					foreach($value as $skey=>$svalue)
					{
						if($skey < $len)
						{
							$this->data[$inum][$ih][$ix] = $svalue;
							++$ix;
						}
						if($skey > ($flen-$len-1))
						{
							$this->data[$inum+1][$ih][$ixx] = $svalue;
							++$ixx;
						}
					}
					++$ih;
				}
				++$inum;
			}else{
				$i = 0;
				foreach($data[$num] as $key => $value){
					$this->data[$inum][$i] = $value;
					++$i;
				}

			}
			++$inum;
		}


		// 去掉0数据
		for($num = 0; $num < count($this->data); ++$num)
		{
			if(count($this->data[$num]) != $this->ImageSize[1])
			{
				foreach($this->data[$num] as $key=>$value)
				{
					$str = implode("",$value);
					echo $str;
					echo "\n";
				}
				return false;
			}

			for($i=0; $i < $this->ImageSize[1]; ++$i)
			{
				$str = implode("",$this->data[$num][$i]);
				$pos = strpos($str, "1");
				if($pos === false)
				{
					unset($this->data[$num][$i]);
				}
			}
		}
		return true;
	}

	public function dealwithData()
	{
		foreach($this->data as $key => $value)
		{
			$rand_keys = array_rand($value);
			$with = count($value[$rand_keys]);
			$hight = count($value);
			$miniwith = array(3,3,3);
			$minihight = array(3,3,3,3,3);
			$bwithd = false;
			// 获取第一个key
			$tmpkey = array_keys($value);
			$arrykey = $tmpkey[0];

			switch($with)
			{
				case 10;
				$miniwith[2]=4;
				case 9;
				break;
				case 8;
				$miniwith[0]=2;
				break;
				case 7:
				$miniwith[0]=2;
				$miniwith[2]=2;
				break;
				case 6:
				$miniwith[0]=2;
				$miniwith[1]=2;
				$miniwith[2]=2;
				break;
				case 5:
				$miniwith[0]=1;
				$miniwith[1]=2;
				$miniwith[2]=2;
				break;
				default:
				$bwithd=true;
				break;
			}

			if($bwithd)
			{
				if($bwithd < 4)
				{
					$this->result[$key]="l";
					if($value[$arrykey+2][0] == 0 && $value[$arrykey+2][1] == 0)
						$this->result[$key]="i";
				}else{
					$this->result[$key]="w";
					$num = 1;
					for($i = $arrykey; $i<$arrykey+$hight; ++$i)
					{
						$num+=$value[$i][1];
					}
					if($num == $hight)
						$this->result[$key]="m";

				}
				continue;
			}

			
			switch($hight)
			{
				case 18;
				$minihight[0]=4;
				$minihight[1]=4;
				$minihight[4]=4;
				case 17;
				$minihight[0]=4;
				$minihight[4]=4;
				case 16;
				$minihight[4]=4;
				break;
				case 15;
				break;
				case 14;
				$minihight[4]=2;
				break;
				case 13:
				$minihight[0]=2;
				$minihight[4]=2;
				break;
				case 12:
				$minihight[0]=2;
				$minihight[3]=2;
				$minihight[4]=2;
				break;
				case 11:
				$minihight[0]=2;
				$minihight[2]=2;
				$minihight[3]=2;
				$minihight[4]=2;
				break;
				case 10:
				$minihight[0]=2;
				$minihight[1]=2;
				$minihight[2]=2;
				$minihight[3]=2;
				$minihight[4]=2;
				break;
				case 9:
				$minihight[0]=2;
				$minihight[1]=2;
				$minihight[2]=2;
				$minihight[3]=2;
				$minihight[4]=1;
				break;
				case 8:
				$minihight[0]=1;
				$minihight[1]=2;
				$minihight[2]=2;
				$minihight[3]=2;
				$minihight[4]=1;
				break;
				case 7:
				$minihight[0]=1;
				$minihight[1]=2;
				$minihight[2]=2;
				$minihight[3]=1;
				$minihight[4]=1;
				break;
				default:
				echo "error hight:".$hight;
				break;
			}

			$hs = 0;
			$ws = 0;

			foreach($minihight as $hightkey => $hightvalue)
			{
				$ws = 0;
				foreach($miniwith as $withkey => $withvalue)
				{
					$this->dealData[$key][$hightkey][$withkey]=0;
					$num = 0;
					// y
					for($i =$arrykey+$hs; $i<$arrykey+$hs+$hightvalue; ++$i)
					{
						for($j=$ws; $j<$ws+$withvalue; ++$j)
						{
							if(isset($value[$i][$j])){
								$num += $value[$i][$j];
							}
						}
					}
					$ws += $withvalue;
					//echo "num:".$num."\n";
					$paret = intval($num/($hightvalue*$withvalue)*100);
					//$paret = intval($num/()*100);
					//echo "num:".$paret."\n";
					$good = 43;
					switch($hightvalue*$withvalue)
					{
						case 9:
							$good = 22;
							break;
						case 8:
							$good = 22;
							break;
						case 6:
							$good = 22;
							break;
					}
					if($paret > $good)
					{
						$this->dealData[$key][$hightkey][$withkey]=1;
					}

				}
				$hs += $hightvalue;
			}

		}
	}

	public function DrawDealData()
	{
		foreach($this->dealData as $key => $value )
		{
			foreach($value as $skey=>$svalue)
			{
				echo implode("",$svalue);
				echo "\n";
			}
			echo "\n";
		}
	}

	public function Draw()
	{
		for($i=0; $i<$this->ImageSize[1]; ++$i)
		{
			echo implode("",$this->DataArray[$i]);
			echo "\n";
		}
	}
	public function clear()
	{
		unset($this->DataArray);
		unset($this->ImageSize);
		unset($this->data);
		unset($this->dealData);
	}
	public function imagecreatefrombmp($file)
	{
		global  $CurrentBit, $echoMode;

		$f=fopen($file,"r");
		$Header=fread($f,2);

		if($Header=="BM")
		{
			$Size=$this->freaddword($f);
			$Reserved1=$this->freadword($f);
			$Reserved2=$this->freadword($f);
			$FirstByteOfImage=$this->freaddword($f);

			$SizeBITMAPINFOHEADER=$this->freaddword($f);
			$Width=$this->freaddword($f);
			$Height=$this->freaddword($f);
			$biPlanes=$this->freadword($f);
			$biBitCount=$this->freadword($f);
			$RLECompression=$this->freaddword($f);
			$WidthxHeight=$this->freaddword($f);
			$biXPelsPerMeter=$this->freaddword($f);
			$biYPelsPerMeter=$this->freaddword($f);
			$NumberOfPalettesUsed=$this->freaddword($f);
			$NumberOfImportantColors=$this->freaddword($f);

			if($biBitCount<24)
			{
				$img=imagecreate($Width,$Height);
				$Colors=pow(2,$biBitCount);
				for($p=0;$p<$Colors;$p++)
				{
					$B=$this->freadbyte($f);
					$G=$this->freadbyte($f);
					$R=$this->freadbyte($f);
					$Reserved=$this->freadbyte($f);
					$Palette[]=imagecolorallocate($img,$R,$G,$B);
				};




				if($RLECompression==0)
				{
					$Zbytek=(4-ceil(($Width/(8/$biBitCount)))%4)%4;

					for($y=$Height-1;$y>=0;$y--)
					{
						$CurrentBit=0;
						for($x=0;$x<$Width;$x++)
						{
							$C=freadbits($f,$biBitCount);
							imagesetpixel($img,$x,$y,$Palette[$C]);
						};
						if($CurrentBit!=0) {$this->freadbyte($f);};
						for($g=0;$g<$Zbytek;$g++)
							$this->freadbyte($f);
					};

				};
			};


			if($RLECompression==1) //$BI_RLE8
			{
				$y=$Height;

				$pocetb=0;

				while(true)
				{
					$y--;
					$prefix=$this->freadbyte($f);
					$suffix=$this->freadbyte($f);
					$pocetb+=2;

					$echoit=false;

					if($echoit)echo "Prefix: $prefix Suffix: $suffix<BR>";
					if(($prefix==0)and($suffix==1)) break;
					if(feof($f)) break;

					while(!(($prefix==0)and($suffix==0)))
					{
						if($prefix==0)
						{
							$pocet=$suffix;
							$Data.=fread($f,$pocet);
							$pocetb+=$pocet;
							if($pocetb%2==1) {$this->freadbyte($f); $pocetb++;};
						};
						if($prefix>0)
						{
							$pocet=$prefix;
							for($r=0;$r<$pocet;$r++)
								$Data.=chr($suffix);
						};
						$prefix=$this->freadbyte($f);
						$suffix=$this->freadbyte($f);
						$pocetb+=2;
						if($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
					};

					for($x=0;$x<strlen($Data);$x++)
					{
						imagesetpixel($img,$x,$y,$Palette[ord($Data[$x])]);
					};
					$Data="";

				};

			};


			if($RLECompression==2) //$BI_RLE4
			{
				$y=$Height;
				$pocetb=0;

				/*while(!feof($f))
				  echo $this->freadbyte($f)."_".$this->freadbyte($f)."<BR>";*/
				while(true)
				{
					//break;
					$y--;
					$prefix=$this->freadbyte($f);
					$suffix=$this->freadbyte($f);
					$pocetb+=2;

					$echoit=false;

					if($echoit)echo "Prefix: $prefix Suffix: $suffix<BR>";
					if(($prefix==0)and($suffix==1)) break;
					if(feof($f)) break;

					while(!(($prefix==0)and($suffix==0)))
					{
						if($prefix==0)
						{
							$pocet=$suffix;

							$CurrentBit=0;
							for($h=0;$h<$pocet;$h++)
								$Data.=chr(freadbits($f,4));
							if($CurrentBit!=0) freadbits($f,4);
							$pocetb+=ceil(($pocet/2));
							if($pocetb%2==1) {$this->freadbyte($f); $pocetb++;};
						};
						if($prefix>0)
						{
							$pocet=$prefix;
							$i=0;
							for($r=0;$r<$pocet;$r++)
							{
								if($i%2==0)
								{
									$Data.=chr($suffix%16);
								}
								else
								{
									$Data.=chr(floor($suffix/16));
								};
								$i++;
							};
						};
						$prefix=$this->freadbyte($f);
						$suffix=$this->freadbyte($f);
						$pocetb+=2;
						if($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
					};

					for($x=0;$x<strlen($Data);$x++)
					{
						imagesetpixel($img,$x,$y,$Palette[ord($Data[$x])]);
					};
					$Data="";

				};

			};


			if($biBitCount==24)
			{
				$img=imagecreatetruecolor($Width,$Height);
				$Zbytek=$Width%4;

				for($y=$Height-1;$y>=0;$y--)
				{
					for($x=0;$x<$Width;$x++)
					{
						$B=$this->freadbyte($f);
						$G=$this->freadbyte($f);
						$R=$this->freadbyte($f);
						$color=imagecolorexact($img,$R,$G,$B);
						if($color==-1) $color=imagecolorallocate($img,$R,$G,$B);
						imagesetpixel($img,$x,$y,$color);
					}
					for($z=0;$z<$Zbytek;$z++)
						$this->freadbyte($f);
				};
			};
			return $img;

		};


		fclose($f);
	}

	public function freadbyte($f)
	{
		return ord(fread($f,1));
	}

	public function freadword($f)
	{
		$b1=$this->freadbyte($f);
		$b2=$this->freadbyte($f);
		return $b2*256+$b1;
	}

	public function freaddword($f)
	{
		$b1=$this->freadword($f);
		$b2=$this->freadword($f);
		return $b2*65536+$b1;
	}

	public function __construct()
	{
		$keysfiles = new files;
		$this->Keys = $keysfiles->funserialize();
		if($this->Keys == false)
			$this->Keys = array();
		unset($keysfiles);
	}
	public function __destruct()
	{
		$keysfiles = new files;
		$keysfiles->fserialize($this->Keys);
		//		print_r($this->Keys);
	}
	protected $ImagePath;
	protected $DataArray;
	protected $ImageSize;
	protected $data;
	protected $dealData;
	protected $Keys;
	protected $NumStringArray;
	public $maxfontwith = 16;
	public $result;

}
?>
