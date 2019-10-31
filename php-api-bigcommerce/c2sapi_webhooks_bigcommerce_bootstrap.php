<?php

ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
set_time_limit(0);

//paramsu
$c2skey = "PUT C2SKEY";

$publichostnameprefix = "https://www.example.com";
$urlprpodprefix = "https://api.bigcommerce.com/stores/123456/v3";
$access_token = "PUT ACCESSTOKEN";
$clientid = "PUT CLIENTID";

$defaultcurrency = "USD";

//tunning
$prodpagesize = 20;
$orderspagesize = 20;

if($_SERVER['HTTP_C2SKEY'] == $c2skey){

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		if($_GET["entity"] == "products"){

			//not supported
			echo "not supported";
			exit();

		}else if($_GET["entity"] == "orders"){

			//not supported
			echo "not supported";
			exit();

		}else{

			//not supported
			echo "not supported";
			exit();

		}

	}else if($_SERVER['REQUEST_METHOD'] == "GET"){

		if($_GET["entity"] == "products"){

			$sortorder = $_GET["sortorder"];
			if($sortorder != "asc"){
				$sortorder = "desc";
			}
			$offset = $_GET["offset"];
			if(!isset($offset)){
				$offset = 0;
			}else{
				$offset = (int)$offset;
			}
			$page = ($offset/$prodpagesize)+1;

			$ch = curl_init();
			$url = $urlprpodprefix."/catalog/brands?limit=250";
			$customHeaders = array(
				'X-Auth-Token: '.$access_token,
				'X-Auth-Client: '.$clientid,
				'Content-Type: application/json',
				'Accept: application/json'
			);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
			$content = trim(curl_exec($ch));
			curl_close($ch);

			$bgbrandspage = json_decode($content);

			$ch = curl_init();
			$url = $urlprpodprefix."/catalog/products?page=".$page."&limit=".$prodpagesize."&sort=date_modified&direction=".$sortorder;
			$customHeaders = array(
				'X-Auth-Token: '.$access_token,
				'X-Auth-Client: '.$clientid,
				'Content-Type: application/json',
				'Accept: application/json'
			);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);

			$content = trim(curl_exec($ch));
			curl_close($ch);

			$c2sprods = [];
			$bgprodpage = json_decode($content);
			$bgprods = $bgprodpage->data;

			foreach($bgprods as $bgprod){

				$c2sprod = new stdClass();
				$c2sprod->_id = "".$bgprod->id;
				$c2sprod->sku = "".$bgprod->id;
				$c2sprod->title = "".$bgprod->name;
				
				$c2sprod->url = $publichostnameprefix.$bgprod->custom_url->url;
				$c2sprod->last_updated = strtotime($bgprod->date_modified)*1000;
				$c2sprod->mpn = $bgprod->mpn;
				$c2sprod->model = "";
				$c2sprod->description = $bgprod->description;

				$c2sprod->brand = $bgprod->brand_id;
				foreach($bgbrandspage->data as $bgbrand){
					if($bgprod->brand_id == $bgbrand->id){
						$c2sprod->brand = $bgbrand->name;
						break;
					}
				}

				$c2sprod->variations = array();

				$c2svariation = new stdClass();
				$c2svariation->availabilities = array();
				$c2svariation->prices = array();
				$c2svariation->images = array();

				$availability = new stdClass();
				$availability->tag = "default";
				$availability->quantity = $bgprod->inventory_level;
				$c2svariation->availabilities[] = $availability;

				$price = new stdClass();
				$price->tag = "default";
				$price->currency = $defaultcurrency;
				$price->number = $bgprod->price;
				$c2svariation->prices[] = $price;

				$c2sprod->variations[] = $c2svariation;

				$ch = curl_init();
				$url = $urlprpodprefix."/catalog/products/".$bgprod->id."/images";
				$customHeaders = array(
					'X-Auth-Token: '.$access_token,
					'X-Auth-Client: '.$clientid,
					'Content-Type: application/json',
					'Accept: application/json'
				);
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);

				$content = trim(curl_exec($ch));
				curl_close($ch);

				$bgimagespage = json_decode($content);

				foreach($bgimagespage->data as $bgimageobj){
					$c2simg = new stdClass();
					$c2simg->url = $bgimageobj->url_zoom;
					$c2svariation->images[] = $c2simg;
				}

				$c2svariation->videos = array();
				$c2svariation->barcode = json_encode(array(
					$bgprod->sku,
					$bgprod->upc
				));
				$c2svariation->color = ""; //TODO
				$c2svariation->size = ""; //TODO

				$c2sprods[] = $c2sprod;

			}

			$c2spage = new stdClass();
			$c2spage->products = $c2sprods;
			$c2spage->paging = new stdClass();
			$c2spage->paging->pageSize = $bgprodpage->meta->pagination->count;
			$c2spage->paging->itemsTotal = $bgprodpage->meta->pagination->total;
			$c2spage->paging->offset = ($bgprodpage->meta->pagination->current_page-1)*$bgprodpage->meta->pagination->per_page;

			header('Content-type: application/json');
			echo(json_encode($c2spage));
			exit();

		}else if($_GET["entity"] == "orders"){

			//not supported
			echo "not supported";
			exit();

		}else{

			//not supported
			echo "not supported";
			exit();

		}

	}

}else{

	echo "401 Unauthorized";
	exit();

}