<?php

//require json
//require http class

ini_set('display_errors', 0);

//MUST EDIT
$c2skey = "CLICK2SYNCGENERATEDAUTHKEY"; //proxy auth
$basicauth = "ACCESS_TOKEN_CONFIGURED_ON_MAGENTO"; //Access Token

//OPTIONAL
$newskusprefix = ""; //for new products
$defaultstatusid = 2; //for new products
$defaultattributesetid = 4; //for new products
$defaulttypeid = "simple"; //for new products
$defaultcategoryids = array(); //for new products
$defaultcurrency = "MXN"; //global
$urlprpodprefix = "https://magentohost.com/"; //global
$urlprpodsuffix = ".html"; //global
$urlimgprefix = "https://magentohost.com/pub/media/catalog/product/"; //global
$colorattribute = "color"; //global
$sizeattribute = "size_option"; //global
$brandattribute = "manufacturer"; //global

//TUNNING
$prodpagesize = 10;
$orderspagesize = 50;

//OTHER
$magattributes = null;

function buildMagentoObjectFromUniversal($universal){

	global $colorattribute;
	global $sizeattribute;
	global $brandattribute;
	$obj = new stdClass();

	if(isset($universal->title)){
		$obj->name = $universal->title;
	}
	if(isset($universal->variations[0]->availabilities[0]->quantity)){
		$si = new stdClass();
		$si->qty = $universal->variations[0]->availabilities[0]->quantity;
		if(!isset($obj->extension_attributes)){
			$obj->extension_attributes = new stdClass();
		}
		$obj->extension_attributes->stock_item = $si;
	}
	if(isset($universal->variations[0]->prices[0]->number)){
		$obj->price = $universal->variations[0]->prices[0]->number;
	}

	if(isset($universal->description) || isset($universal->brand) || isset($universal->variations[0]->color) || isset($universal->variations[0]->size)){

		global $magattributes;
		$magattributes = getMagentoCustomAttrs();

		$custom_attrs = array();
		if(isset($universal->description)){
			//custom attribute
			$cus_attr = new stdClass();
			$cus_attr->attribute_code = "description";
			$cus_attr->value = $universal->description;
			$custom_attrs[] = $cus_attr;
			$cus_attr = new stdClass();
			$cus_attr->attribute_code = "short_description";
			$cus_attr->value = $universal->description;
			$custom_attrs[] = $cus_attr;
		}
		if(isset($universal->brand)){
			//custom attribute
			for($j=0; $j<count($magattributes->items); $j++){
				$attr = $magattributes->items[$j];
				if($attr->attribute_code == $brandattribute){
					for($k=0; $k<count($attr->options); $k++){
						$option = $attr->options[$k];
						if($option->label == $universal->brand){
							$cus_attr = new stdClass();
							$cus_attr->attribute_code = $brandattribute;
							$cus_attr->value = $option->value;
							$custom_attrs[] = $cus_attr;
							break;
						}
					}
				}
			}
		}
		if(isset($universal->variations[0]->color)){
			//custom attribute
			for($j=0; $j<count($magattributes->items); $j++){
				$attr = $magattributes->items[$j];
				if($attr->attribute_code == $colorattribute){
					for($k=0; $k<count($attr->options); $k++){
						$option = $attr->options[$k];
						if($option->label == $universal->variations[0]->color){
							$cus_attr = new stdClass();
							$cus_attr->attribute_code = $colorattribute;
							$cus_attr->value = $option->value;
							$custom_attrs[] = $cus_attr;
							break;
						}
					}
				}
			}
		}
		if(isset($universal->variations[0]->size)){
			//custom attribute
			for($j=0; $j<count($magattributes->items); $j++){
				$attr = $magattributes->items[$j];
				if($attr->attribute_code == $sizeattribute){
					for($k=0; $k<count($attr->options); $k++){
						$option = $attr->options[$k];
						if($option->label == $universal->variations[0]->size){
							$cus_attr = new stdClass();
							$cus_attr->attribute_code = $sizeattribute;
							$cus_attr->value = $option->value;
							$custom_attrs[] = $cus_attr;
							break;
						}
					}
				}
			}
		}
		$obj->custom_attributes = $custom_attrs;
	}

	return $obj;

}

function roundNearestStep($number, $step){
	return ceil( $number / $step ) * $step;
}

function getMagentoCustomAttrs(){
	global $basicauth;
	global $urlprpodprefix;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $urlprpodprefix.'index.php/rest/V1/products/attributes?searchCriteria[page_size]=100');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization: Bearer '.$basicauth,
		'Content-Type: application/json'
	));
	$content = trim(curl_exec($ch));
	curl_close($ch);
	$magattributes = json_decode($content);
	return $magattributes;
}

function skugenerator(){
	global $newskusprefix;
	return $newskusprefix.substr("".(round(microtime(true) * 1000)),-6);
}

if($_SERVER['HTTP_C2SKEY'] == $c2skey){

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		if($_GET["entity"] == "products"){

			$postedprod = json_decode(file_get_contents('php://input'));
			
			if(isset($postedprod->_id)){
				//update
				$id = $postedprod->_id;
				$update = buildMagentoObjectFromUniversal($postedprod);
				$update->sku = $postedprod->_id;
				$data_json = new stdClass();
				$data_json->saveOptions = true;
				$data_json->product = $update;
				$data_json = json_encode($data_json);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $urlprpodprefix.'index.php/rest/V1/products/'.$id);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$basicauth,
					'Content-Type: application/json',
					'Content-Length: '.strlen($data_json)
				));
				$rawcontent = trim(curl_exec($ch));
				curl_close($ch);
				$resp = json_decode($rawcontent);
				$return = new stdClass();
				if(!isset($resp->message)){
					$return->success = true;
				}else{
					$return->success = false;
					$return->code = 403;
					$return->error = 'Could not update product';
					$return->reasons = array();
					if(isset($resp->message)){
						$reason = new stdClass();
						$reason->message = $resp->message.' '.json_encode($resp);
						$reason->code = 400;
						$return->reasons[] = $reason;
					}else{
						$reason = new stdClass();
						$reason->message = 'Unknown reason. Details: '.json_encode($resp);
						$reason->code = 500;
						$return->reasons[] = $reason;
					}
				}
				echo json_encode($return);
				exit();
			}else{
				//insert
				$insert = buildMagentoObjectFromUniversal($postedprod);

				//defaults
				$insert->sku = skugenerator();
				$insert->status = $defaultstatusid;
				$insert->attribute_set_id = $defaultattributesetid;
				$insert->type_id = $defaulttypeid;
				if(!isset($insert->custom_attributes)){
					$insert->custom_attributes = array();
				}
				$cust_attr = new stdClass();
				$cust_attr->attribute_code = "category_ids";
				$cust_attr->value = $defaultcategoryids;
				$insert->custom_attributes[] = $cust_attr;
				
				$data_json = new stdClass();
				$data_json->saveOptions = true;
				$data_json->product = $insert;
				$data_json = json_encode($data_json);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $urlprpodprefix.'index.php/rest/V1/products');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$basicauth,
					'Content-Type: application/json',
					'Content-Length: '.strlen($data_json)
				));
				$rawcontent = trim(curl_exec($ch));
				curl_close($ch);
				$resp = json_decode($rawcontent);
				$return = new stdClass();
				if(!isset($resp->message)){
					$return->success = true;
					$return->product = new stdClass();
					$return->product->_id = $resp->sku;
				}else{
					$return->success = false;
					$return->code = 403;
					$return->error = 'Could not insert product';
					$return->reasons = array();
					if(isset($resp->message)){
						$reason = new stdClass();
						$reason->message = $resp->message.' '.json_encode($resp);
						$reason->code = 400;
						$return->reasons[] = $reason;
					}else{
						$reason = new stdClass();
						$reason->message = 'Unknown reason. Details: '.json_encode($resp);
						$reason->code = 500;
						$return->reasons[] = $reason;
					}
				}
				echo json_encode($return);
				exit();
			}
			exit();

		}else if($_GET["entity"] == "orders"){

			//not supported
			echo "not supported";
			exit();

		}

	}else if($_SERVER['REQUEST_METHOD'] == "GET"){

		if($_GET["entity"] == "products"){

			//get attributes (size, and color) 1 request
			$magattributes = getMagentoCustomAttrs();

			$sortorder = "asc";
			if($_GET["sortorder"] == "desc"){
				$sortorder = "desc";
			}
			$offset = (int)$_GET['offset'];
			$offset = roundNearestStep($offset, $prodpagesize);
			$page = ((int)($offset/$prodpagesize))+1;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $urlprpodprefix.'index.php/rest/V1/products?searchCriteria[page_size]='.$prodpagesize.'&searchCriteria[sortOrders][0][field]=updated_at&searchCriteria[sortOrders][0][direction]='.$sortorder.'&searchCriteria[currentPage]='.$page);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer '.$basicauth,
				'Content-Type: application/json'
			));
			$content = trim(curl_exec($ch));
			curl_close($ch);
			$magobj = json_decode($content);

			$c2spage = new stdClass();
			$c2spage->paging = new stdClass();
			$c2spage->paging->pageSize = $prodpagesize;
			$c2spage->paging->itemsTotal = $magobj->total_count ? (int)$magobj->total_count : 999;
			$c2spage->paging->offset = $offset;
			$c2spage->products = array();

			for($i=0; $i<count($magobj->items); $i++){
				$magprod = $magobj->items[$i];
				$c2sprod = new stdClass();
				$c2sprod->_id = $magprod->sku;
				$c2sprod->sku = $magprod->sku;
				$c2sprod->title = $magprod->name;

				$c2sprod->brand = "";
				$c2sprod->last_updated = strtotime($magprod->updated_at)*1000;

				$imgurl = "";
				$colorattrrawval = null;
				$sizeattrrawval = null;

				for($j=0; $j<count($magprod->custom_attributes); $j+=1){
					$cattrobj = $magprod->custom_attributes[$j];
					switch($cattrobj->attribute_code){
						case "manufacturer":{
							//brand
							$manufacturerattrrawval = $cattrobj->value;
							break;
						}
						case "image":{
							$imgurl = $urlimgprefix.$cattrobj->value;
							break;
						}
						case "url_key":{
							$c2sprod->url = $urlprpodprefix.$cattrobj->value.$urlprpodsuffix;
							break;
						}
						case "description":{
							$c2sprod->description = $cattrobj->value;
							break;
						}
						case "size_option":{
							$sizeattrrawval = $cattrobj->value;
							break;
						}
						case "color":{
							$colorattrrawval = $cattrobj->value;
							break;
						}
						default:{
							break;
						}
					}
				}

				$c2sprod->mpn = "";
				$c2sprod->model = "";
				$c2sprod->tags = array();
				
				$c2sprod->variations = array();
				$variation = new stdClass();

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $urlprpodprefix.'index.php/rest/V1/stockItems/'.$magprod->sku);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Bearer '.$basicauth,
					'Content-Type: application/json'
				));
				$content = trim(curl_exec($ch));
				curl_close($ch);
				$magstockinfo = json_decode($content);

				$variation->availabilities = array();
				$stock = new stdClass();
				$stock->tag = "default";
				$stock->quantity = (int)$magstockinfo->qty;
				$variation->availabilities[] = $stock;

				$variation->prices = array();
				$price = new stdClass();
				$price->tag = "default";
				$price->currency = $defaultcurrency;
				$price->number = (float)$magprod->price;
				$variation->prices[] = $price;

				$variation->images = array();
				if(strlen($imgurl)>0){
					$url = $imgurl;
					if(strlen($url)>0){
						$image = new stdClass();
						$image->url = $url;
						$variation->images[] = $image;
					}
				}

				$variation->videos = array();

				$variation->barcode = "";

				$variation->size = "";
				$variation->color = "";

				for($j=0; $j<count($magattributes->items); $j++){
					$attr = $magattributes->items[$j];
					if($colorattrrawval != null){
						if($attr->attribute_code == $colorattribute){
							for($k=0; $k<count($attr->options); $k++){
								$option = $attr->options[$k];
								if($option->value == $colorattrrawval){
									$variation->color = $option->label;
								}
							}
						}
					}
					if($sizeattrrawval != null){
						if($attr->attribute_code == $sizeattribute){
							for($k=0; $k<count($attr->options); $k++){
								$option = $attr->options[$k];
								if($option->value == $sizeattrrawval){
									$variation->size = $option->label;
								}
							}
						}
					}
					if($manufacturerattrrawval != null){
						if($attr->attribute_code == $brandattribute){
							for($k=0; $k<count($attr->options); $k++){
								$option = $attr->options[$k];
								if($option->value == $manufacturerattrrawval){
									$c2sprod->brand = $option->label;
								}
							}
						}
					}
				}
				
				$c2sprod->variations[] = $variation;

				$c2spage->products[] = $c2sprod;
			}

			echo(json_encode($c2spage));
			exit();

		}else if($_GET["entity"] == "orders"){

			//not supported
			echo "not supported";
			exit();

		}

	}

}else{

	echo "401 Unauthorized";
	exit();

}
