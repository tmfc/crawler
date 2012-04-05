<?php
require_once dirname(__FILE__) . "/../../domain/SourceProductForMapping.php";
abstract class base_result_saver {
	abstract public function save_result($result);
}

class blackhole_saver extends base_result_saver {
	public function save_result($result) {
		return;
	}
}

class category_saver extends base_result_saver {
	public function save_result($result) {
		foreach ( $result as $category ) {
			$cate = new Category ();
			$cate->name = $category ['name'];
			$cate->pid = $category ['pid'];
			$cate->sort_order = $category ['sort_order'];
			$cate->url_aka = $category ['url_aka'];
			$cate->price_group= $category ['price_group'];
			$cate->is_show = $category ['is_show'];
			$pid = Category::addCate ( $cate );
			
			foreach ( $category ['children'] as $sub ) {
				$subcate = new Category ();
				$subcate->name = $sub ['name'];
				//echo $subcate->name."\r\n";
				$subcate->pid = $pid;
				$subcate->sort_order = $sub ['sort_order'];
				$subcate->url_aka = $sub ['url_aka'];
				$subcate->price_group= $category ['price_group'];
				$subcate->is_show = $sub ['is_show'];
				Category::addCate ( $subcate );
			}
		}
	}
}

class sp4m_saver extends base_result_saver {
	public $domain_id;
	public $save_history = false;
	
	public function __construct($domain_id) {
		$this->domain_id = $domain_id;
	}
	
	function get_cat_id($cat_url_aka = "") {
		$cat_url_aka = trim ( $cat_url_aka );
		$category = Category::get_cate_by_url_aka ( $cat_url_aka );
		if ($category) {
			return $category->id;
		} else {
			return 0;
		}
	}
	
	function get_brand_id($brand_name = "", $brand_url = "", $brand_en = "", $add_new = false) {
		$brand_name = trim ( $brand_name );
		$brand_en = trim ( $brand_en );
		if (empty ( $brand_name )) {
			$brand_name = $brand_en;
		}
		$brand = Brand::get_brand_by_name ( $brand_name );
		if ($brand) {
			return $brand [0]->id;
		} else {
			if (empty ( $brand_en )) {
				$brand_en = $brand_name;
			}
			$brand = Brand::get_brand_by_name ( $brand_en );
			if ($brand) {
				return $brand [0]->id;
			}
			if ($add_new) {
				$brand = new Brand ();
				$brand->name = $brand_name;
				$brand->name_en = $brand_en;
				$brand->brand_desc = $brand_name;
				$brand->mapping_keywords = $brand_name;
				$brand->site_url = $brand_url;
				$brand_id = Brand::add_brand ( $brand );
				unset($brand);
				return $brand_id;
			}
			return 0;
		}
	}
	
	public function save_result($products) {
		$this->html_change = 0;
		$this->html_change_product = array();
		foreach ( $products as $product ) {
			if(empty($product ['source_url']) || empty($product ['source_id']) || empty($product ['product_name_full']) )
			{
				//html可能改变，通知
				if($this->html_change > 10)
				{
					throw new Exception('html代码可能已更改' . var_export($this->html_change_product,true));
				}
				else
				{
					$this->html_change ++;
					$this->html_change_product[] = $product;
				}
				continue;
			}
			
			if( empty($product ['price']))
				continue;
			//try
			//{
				//以category为参数，设置SourceProductForMapping的分表信息
				if($product ['product_category_id']>0){
					$category_id = $product ['product_category_id'];
				}else{
					$category_id = $this->get_cat_id ( $product ['product_category_url_aka'] );
				}
				SourceProductForMappingNew::set_table_param($category_id);
				$sp4m = SourceProductForMappingNew::get_by_domain_sourceid($this->domain_id, $product ['source_id'] );
				$insert = false;
				//如果sp4m不存在，新建
				if(empty($sp4m)) {
					$insert = true;
					$sp4m = new SourceProductForMappingNew ();
				}
				$sp4m->domain_id = $this->domain_id;
				
				//source_id,source_url
				$sp4m->source_id = $product ['source_id'];
				$sp4m->source_url = $product ['source_url'];
				
				//product_name
				$sp4m->product_name_full = $product ['product_name_full'];
				if(!empty($product ['product_name'])) {
					$sp4m->product_name = $product ['product_name'];
				}
				
				//price
				$sp4m->old_price = $sp4m->price;
				$sp4m->price = $product ['price'];
				$sp4m->market_price = $product ['market_price'];
				
				//thumb_img
				$sp4m->thumb_url = $product ['thumb_url'];
				
				//summary
				if(!empty($product['product_summary'])) {
					$sp4m->product_summary = $product['product_summary'];
				}
				
				//category
				if($product ['product_category_id']>0){
					$sp4m->product_category_id = $product ['product_category_id'];
				}else{
					$sp4m->product_category_id = $this->get_cat_id ( $product ['product_category_url_aka'] );
				}
				
				if( empty($product ['product_category_name'])){
					$sp4m->product_category_name = "";
				}else{
					$sp4m->product_category_name = $product ['product_category_name'];
				}
				
				//brand
				if(!empty($product ['product_brand_name']))
				{
					$sp4m->product_brand_name = $product ['product_brand_name'];
					if(!empty($product['product_brand_id'])) {
						$sp4m->product_brand_id = $product['product_brand_id'];
					}
					elseif ($product ['product_brand_name'] != '') {
						$sp4m->product_brand_id = $this->get_brand_id ( $product ['product_brand_name'], "", "", false );
					} else {
						$sp4m->product_brand_id = 0;
					}
				}
				
				//update_time
				$sp4m->update_time = date ( "Y-m-d H:i:s", time () );

				//file_put_contents('sp4m', var_export($sp4m,true),FILE_APPEND);
				
				//update
				if (!$insert) {
					$sp4m->save();
					echo $sp4m->id . ',';
					echo "update success \r\n";
				} else {
					//status
					$sp4m->current_status = 0;
					
					//create_time
					$sp4m->create_time = date ( "Y-m-d H:i:s", time () );
					
					//create
					if($sp4m->create() < 0) {
						echo "insert fail \r\n";
						unset($sp4m);
						return false;
					}
					echo "insert success \r\n";
				}
				
				if($this->save_history === true)
				{
					if ($insert || ((float)$sp4m->old_price != (float)$sp4m->price && (float)$sp4m->price > 0 && (float)$sp4m->old_price > 0)) {
						//echo 'log_product_price_history' . PHP_EOL;
						$this->log_product_price_history ( $sp4m->source_id, $sp4m->domain_id, $sp4m->price, $sp4m->old_price, $sp4m->update_time  );
					}
				}
				unset($sp4m);
				unset($product);
			//}
			//catch (Exception $ex){
			//}
		}
		unset($products);
		return true;
	}
	
	private function log_product_price_history($source_id, $domain_id, $price, $old_price, $update_time) {
		if (empty ( $source_id ) || $domain_id < 1) {
			return;
		}
		
		$sourceProductPriceHistory=new SourceProductPriceHistory();
		$sourceProductPriceHistory->source_id = $source_id;
		$sourceProductPriceHistory->domain_id = $domain_id;
		$sourceProductPriceHistory->price = $price;
		$sourceProductPriceHistory->old_price = $old_price;
		$sourceProductPriceHistory->update_time = $update_time;
		$sourceProductPriceHistory->create();
		
		unset($sourceProductPriceHistory);
	}
}

class goods_saver
{
	//调用之前必须处理$product['url_aka'],可能需要处理$product['image']，$product['attr']等
	public function save_result($product) {
		//要判断Goods是否已经存在
		//sp4m
		SourceProductForMappingNew::set_table_param($product['product_category_id']);
		$sp4m = SourceProductForMappingNew::get_by_domain_sourceid( $this->domain_id, $product ['source_id'] );
		
		//通过source_id判断good是否存在
		$same_goods = Object_mapping::has_object_mapping(1, $this->domain_id, 0, $product['source_id']);

		$goods = new Goods ();
		$goods->name = $sp4m->product_name_full;
		if(empty($goods->name))
			continue;
		$goods->cat_id = $sp4m->product_category_id;
		
		$goods->url_aka = $product ['url_aka'];
		
		if ($sp4m->product_brand_id > 0) {
			$goods->brand_id = $sp4m->product_brand_id;
		} else {
			$goods->brand_id = $this->get_brand_id ( $product ['product_brand_name'], "", "", true );
		}
		$goods->update_time = date ( "Y-m-d H:i:s" );
		$goods->goods_desc = empty($product ['desc'])?'':$product ['desc'];
		$goods->price = empty($product ['price'])?'0.0':$product ['price'];
		$goods->good_sn = empty($product ['desc'])?'':$product ['good_sn'];
		$goods->status = empty($product ['status'])?1:$product ['status'];
		$goods->market_price = $product ['market_price'];
		$goods->goods_model = $product ['product_name'];
		$goods->launch_date = $product['launch_date'];
		$goods->pinyin = Pinyin($goods->name,-1,false,'utf-8',false);
		
		if (! $same_goods) {
			$goods->create_time = date ( "Y-m-d H:i:s" );
			//insert new goods
			$goods_id = Goods::add_goods ( $goods );
			if ($goods_id < 1) {
				return - 10;
			}
			$goods->id = $goods_id;
			
			//更新图片
			$this->update_goods_img ( $sp4m->source_id, $goods_id, $product );
			//insert object_mapping
			$om = new Object_mapping ();
			$om->object_id = $goods_id;
			$om->object_type_id = 1;
			$om->domain_id = $this->domain_id;
			$om->source_id = $sp4m->source_id;
			$om->source_url = $sp4m->source_url;
			$om->create_time = date ( "Y-m-d H:i:s" );
			$res = Object_mapping::add_object_mapping ( $om );
			if ($res < 1) {
				return false;
			}
			$sp4m->current_status = SourceProductForMapping::IMPORT_GOODS_SUCESS;
			//$affert_row = SourceProductForMapping::update_SP4M ( $sp4m );
			$affert_row = $sp4m->save();
			
			unset($om);

			//保存属性
			if(!empty($product ['attrs']) && count($product ['attrs']) > 0)
				$this->update_goods_attr ( $product ['attrs'], $goods );
		}
		else {
			if($goods->name != $same_goods->name ||
			$goods->cat_id != $same_goods->cat_id ||
			$goods->url_aka != $same_goods->url_aka ||
			$goods->brand_id != $same_goods->brand_id ||
			$goods->goods_desc != $same_goods->goods_desc ||
			$goods->price != $same_goods->price ||
			$goods->good_sn != $same_goods->good_sn ||
			$goods->status != $same_goods->status ||
			$goods->market_price != $same_goods->market_price ||
			$goods->goods_model != $same_goods->goods_model ||
			$goods->launch_date != $same_goods->launch_date ||
			$goods->pinyin != $same_goods->pinyin)
			{
				Goods::update_goods( $goods );
			}
			$goodsId= $same_goods->id;
			if(!empty($goodsId)){
				$goods->id = $goodsId;
				//保存属性
				if(!empty($product ['attrs']) && count($product ['attrs']) > 0)
					$this->update_goods_attr ( $product ['attrs'], $goods );
			}

		}
		unset($same_goods);
		unset($sp4m);
		unset($product);
		
		return $goods;
	}
	
	private function update_goods_img($source_id, $goods_id, $product) {
		//生成临时文件名
		$tmp_name = tempnam ( "tmp", "FOO" );
		//抓取图片
		$data = $this->req_url ( $product ['image'] );
		if (! $data) {
			return - 20;
		}
		//保存图片
		if (! write_file ( $tmp_name, $data )) {
			return - 21;
		}
		//保存产品图片
		$product_pic = $this->save_product_pic ( $goods_id, $product ['image'], 1 );
		
		//resize图片
		$small_pic = $product_pic->get_photo_file_path ( Goods_photo::SMALL );
		$img = new Image_resize ( $tmp_name, $small_pic );
		$img->resize ( 125, 94 );
		$big_pic = $product_pic->get_photo_file_path ( Goods_photo::BIG );
		$img->resize_image_file = $big_pic;
		$img->resize ( 340, 255 );
		
		//echo $big_pic . "\r\n";
		//删除临时文件
		@unlink ( $tmp_name );
		unset($product_pic);
		return;
	}
	
	private function save_product_pic($goods_id = 0, $pic_url = "", $is_default = 0) {
		if ($goods_id < 1) {
			return false;
		}
		$goods_photo = new Goods_photo ();
		$goods_photo->goods_id = $goods_id;
		$goods_photo->is_default = $is_default == 1 ? 1 : 0;
		$goods_photo->create_time = date ( "Y-m-d H:i:s" );
		$goods_photo->source_pic_url = $pic_url;
		$res = Goods_photo::add_goods_photo ( $goods_photo );
		if ($res > 0) {
			$goods_photo->id = $res;
			return $goods_photo;
		}
		unset($goods_photo);
		return false;
	}
	
	private function update_goods_attr($goods_attr, $goods) {
		foreach ( $goods_attr as $attr ) {
			$attr_id = - 1;
			$pattribute = Attribute::get_attribute_by_name ( $attr ['name'], $goods->cat_id );
			if (! $pattribute) {
				$pattribute = new Attribute ();
				$pattribute->cat_id = $goods->cat_id;
				$pattribute->name = $attr ['name'];
				$pattribute->value_input_type = 1;
				$pattribute->is_key_params = 0;
				$attr_id = Attribute::add_attribute ( $pattribute );
			} else {
				$attr_id = $pattribute->id;
			}
			
			if ($attr_id > 0) {
				$is_exist = Goods_attr::is_goods_attr_exist($goods->id,$attr_id);
				if(!$is_exist)
				{
					$ga = new Goods_attr ();
					$ga->goods_id = $goods->id;
					$ga->attr_id = $attr_id;
					$ga->attr_value = $attr ['value'];
					Goods_attr::add_goods_attr ( $ga );
					unset($ga);
				}
			}
			unset($pattribute);
			unset($attr);
		}
		unset($goods_attr);
		return;
	}
} 

