<?php

if($_SERVER["REQUEST_METHOD"] == "GET"){

	$sql_count = mysql_query("SELECT count(*) FROM products");
	$total = mysql_fetch_array($sql_count);
	$sql_result = mysql_query("SELECT * FROM products LIMIT 50 OFFSET ".(int)$_GET["offset"]);
	$response = new stdClass();
	$response->paging = new stdClass();
	$response->products = array();
	$response->paging->itemsTotal = $total;
	$response->paging->pageSize = 50;
	$response->paging->offset = $_GET["offset"];
	while($row = mysql_fetch_array($sql_result)){
		$product = new stdClass();
		$product->sku = $row['id'];
		$product->title = $row['title'];
		$product->url = 'https://www.example.com/shop/'.$row['slug'];
		$product->brand = 'My Brand';
		$product->mpn = $row['mpn'];
		$product->model = $row['model'];
		$product->description = $row['description'];
		$product->variations = array();

		$variation = new stdClass();

		$variation->availabilities = array();
		$stock = new stdClass();
		$stock->tag = 'default';
		$stock->quantity = (int)$row['stock'];
		$variation->availabilities[] = $stock;

		$variation->prices = array();
		$price = new stdClass();
		$price->tag = 'default';
		$price->currency = 'USD';
		$price->number = (float)$row['price'];
		$variation->prices[] = $price;

		$variation->images = array();
		$image = new stdClass();
		$image->url = $row['imgurl'];
		$variation->images[] = $image;

		$variation->videos = array();
		$video = new stdClass();
		$video->url = $row['video'];
		$variation->videos[] = $video;

		$variation->barcode = $row['ean'];
		$variation->size = $row['size'];
		$variation->color = $row['color'];

		$product->variations[] = $variation;

		$response->products[] = $product;
	}
	echo json_encode($response);

}else if($_SERVER["REQUEST_METHOD"] == "POST"){

	echo 'unimplemented method';
	exit();

}else{

	echo 'unsupported request method';
	exit();

}