<?php

ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
set_time_limit(0);

include_once("simple_html_dom.php");

//paramsu
$c2skey = "PUT C2SKEY";
$defaultcurrency = "MXN";

$meta_page_results = array(
	"path" => "section.search_results a",
	"attribute" => "href",
	"url_prefix" => "https://www.example.com"
);

$meta_page_product = array(
	"sku" => array(
		"type" => "selector",
		"path" => '.main_content span.sku',
		"attribute" => "value"
	),
	"title" => array(
		"type" => "selector",
		"path" => 'head meta[property="og:title"]',
		"attribute" => "content"
	),
	"url" => array(
		"type" => "selector",
		"path" => 'head meta[property="og:url"]',
		"attribute" => "content"
	),
	"description" => array(
		"type" => "selector",
		"path" => "#description_box div.text",
		"attribute" => "innertext"
	),
	"price" => array(
		"type" => "selector",
		"path" => '.main_content span.price',
		"attribute" => "value"
	),
	"currency" => array(
		"type" => "selector",
		"path" => '.main_content span#item_curr',
		"attribute" => "value"
	),
	"availability" => array(
		"type" => "selector",
		"path" => '.main_content span#stock',
		"attribute" => "value"
	),
	"color" => array(
		"type" => "custom",
		"function" => "extractColor"
	),
	"imgs" => array(
		"type" => "custom",
		"function" => "getImages"
	),
	"category" => array(
		"type" => "custom",
		"function" => "buildCategory"
	)
);

$pages = array(
	"category_1" => array(
		"url" => "https://www.example.com/category_1",
		"path_link" => $path_link["path"]
	),
	"category_2" => array(
		"url" => "https://www.example.com/category_2",
		"path_link" => $path_link["path"]
	),
	"category_3" => array(
		"url" => "https://www.example.com/category_3",
		"path_link" => $path_link["path"]
	),
	"category_4" => array(
		"url" => "https://www.example.com/category_4",
		"path_link" => $path_link["path"]
	),
	"category_5" => array(
		"url" => "https://www.example.com/category_5",
		"path_link" => $path_link["path"]
	)
);

function buildPage($next){

	global $pages;
	$pageindex = "";
	$pagekeys = array_keys($pages);
	$selectedpage = "";
	$nextpage = "";
	$c2sprodsbuffer = array();
	if($next == ""){
		$selectedpage = $pagekeys[0];
		$nextpage = $pagekeys[1];
	}else{
		foreach($pagekeys as $i => $pagekey){
			if($pagekey == $next){
				$selectedpage = $pagekeys[$i];
				if(isset($pagekeys[$i+1])){
					$nextpage = $pagekeys[$i+1];
				}
			}
		}
	}
	if($selectedpage != ""){
		$pageresultsurl = $pages[$selectedpage]["url"];
		$productpageurls = getAllProductPageUrls($pageresultsurl);

		foreach($productpageurls as $productpageurl){
			$productpagedata = getProductDataFromProductPage($productpageurl);
			$product = translateProductData2C2SStructure($productpagedata);
			$c2sprodsbuffer[] = $product;
		}
		
	}else{
		//return empty page and finish this
	}

	$c2spage = new stdClass();
	$c2spage->paging = new stdClass();
	$c2spage->paging->pageSize = 0;
	$c2spage->paging->itemsTotal = 9999;
	$c2spage->paging->offset = (int)$_GET['offset'];
	$c2spage->paging->this = $selectedpage;
	$c2spage->paging->next = $nextpage;
	$c2spage->products = $c2sprodsbuffer;

	return $c2spage;

}

function translateProductData2C2SStructure($productdata){

	global $defaultcurrency;
	
	$c2sprod = new stdClass();
	$c2sprod->_id = $productdata["sku"];
	$c2sprod->sku = $productdata["sku"];
	$c2sprod->title = $productdata["title"];
	$c2sprod->url = $productdata["url"];
	$c2sprod->brand = "";
	$c2sprod->mpn = "";
	$c2sprod->model = "";
	$c2sprod->description = $productdata["description"];
	$c2sprod->tags = array();
	$c2sprod->variations = array();

	//stock, price, etc
	$variation = new stdClass();

	//stock
	$variation->availabilities = array();
	$stock = new stdClass();
	$stock->tag = "default";
	$stock->quantity = (int)$productdata["availability"];
	$variation->availabilities[] = $stock;

	$variation->prices = array();
	$price = new stdClass();
	$price->tag = "default";
	$price->currency = $productdata["currency"] ? $productdata["currency"] : $defaultcurrency;
	$price->number = (float)$productdata["price"];
	$variation->prices[] = $price;

	$variation->images = array();
	foreach($productdata["imgs"] as $imgurl){
		$image = new stdClass();
		$image->url = $imgurl;
		$variation->images[] = $image;
	}

	$variation->videos = array();
	$variation->barcode = "";
	$variation->size = "";
	$variation->color = $productdata["color"];
	
	$c2sprod->variations[] = $variation;

	$c2sprod->last_updated = time()*1000;

	return $c2sprod;

}

function getAllProductPageUrls($pageurl){

	global $meta_page_results;
	// Create DOM from URL or file
	$html = file_get_html($pageurl, false, null, 0, -1, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT);
	$page_urls = array();

	// Find all results urls
	foreach($html->find($meta_page_results["path"]) as $link){
		$page_urls[] = $meta_page_results["url_prefix"].$link->{$meta_page_results["attribute"]};
	}
	return $page_urls;

}

function getProductDataFromProductPage($productpageurl){

	global $meta_page_product;
	// Create DOM from URL or file
	$html = file_get_html($productpageurl, false, null, 0, -1, true, true, DEFAULT_TARGET_CHARSET, false, DEFAULT_BR_TEXT, DEFAULT_SPAN_TEXT);
	$keys = array_keys($meta_page_product);
	$obj = array();
	foreach($keys as $piece){
		if($meta_page_product[$piece]["type"] == "custom"){
			$obj[$piece] = $meta_page_product[$piece]["function"]($html);
		}else{
			$obj[$piece] = $html->find($meta_page_product[$piece]["path"], 0)->{$meta_page_product[$piece]["attribute"]};
		}
	}
	return $obj;

}

function getImages($html){

	$imgurls = array();
	foreach($html->find(".img_wrapper ul li.main-image") as $li){
		$img = $li->find("img",0);
		if($img && $img->src){
			if(strpos($img->src, "//") === 0){
				$imgurls[] = "http:".$img->src;
			}else{
				$imgurls[] = $img->src;
			}
		}
	}
	return $imgurls;

}

function buildCategory($html){

	$breadcrumbtexts = array();
	foreach($html->find("nav.breadcrumb_path ul li a") as $a){
		if(isset($a->innertext)){
			$breadcrumbtexts[] = $a->innertext;
		}
	}
	$categoryparts = array();
	for($i=1; $i<count($breadcrumbtexts)-1; $i++){
		$categoryparts[] = $breadcrumbtexts[$i];
	}
	return implode(" > ", $categoryparts);

}

function extractColor($html){

	foreach($html->find(".attributes-container") as $variation){
		$variationtext = trim($variation->plaintext);
		$variationtext_lower = strtolower($variationtext);
		if(strpos($variationtext_lower,'color') !== false){
			$parts = explode(":", $variationtext);
			array_shift($parts);
			$variationtext = implode(":", $parts);
			return $variationtext;
		}
	}
	return "";

}


if($_SERVER['HTTP_C2SKEY'] == $c2skey){

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		if($_GET["entity"] == "products"){

			//not supported
			echo "not supported in this example";
			exit();

		}else if($_GET["entity"] == "orders"){

			//not supported
			echo "not supported in this example";
			exit();

		}else{

			//not supported
			echo "not supported in this example";
			exit();

		}

	}else if($_SERVER['REQUEST_METHOD'] == "GET"){

		if($_GET["entity"] == "products"){

			$next = "";
			if($_GET["next"]){
				$next = $_GET["next"];
			}
			$c2spage = buildPage($next);
			echo json_encode($c2spage);
			exit();

		}else if($_GET["entity"] == "orders"){

			//not supported
			echo "not supported in this example";
			exit();

		}else{

			//not supported
			echo "not supported in this example";
			exit();

		}

	}

}else{

	echo "401 Unauthorized in this example";
	exit();

}