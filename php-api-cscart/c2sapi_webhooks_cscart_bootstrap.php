<?php

//require json
//require http class

ini_set('display_errors', 0);

$c2skey = "THEKEYGENERATEDONCLICK2SYNC";
$basicauth = "BASE64ENCODEDSTRINGOFBASICAUTHENTICATIONFORCSCART";
$prodpagesize = 50;
$orderspagesize = 50;
$defaultcurrency = "MXN";
$urlprpodprefix = "https://mycscart.example.com/shop/index.php?dispatch=products.view&product_id=";
$defaultcategoryids = array(123); //for new products

function buildCSCartObjectFromUniversal($universal){

	global $defaultcategoryids;
	$obj = new stdClass();
	if(isset($universal->title)){
		$obj->product = $universal->title;
	}
	if(isset($universal->mpn)){
		$obj->product_code = $universal->mpn;
	}
	if(isset($universal->description)){
		$obj->short_description = $universal->description;
	}
	if(isset($universal->flyer)){
		$obj->full_description = $universal->flyer;
	}
	if(isset($universal->variations[0]->availabilities[0]->quantity)){
		$obj->amount = $universal->variations[0]->availabilities[0]->quantity;
		if($obj->amount < 0){
			$obj->amount = 0;
		}
	}
	if(isset($universal->variations[0]->prices[0]->number)){
		$obj->price = $universal->variations[0]->prices[0]->number;
	}
	if(isset($universal->variations[0]->images[0]->url)){
		$maindone = false;
		$variation = $universal->variations[0];
		$obj->image_pairs = array();
		for($j=0; $j<count($variation->images); $j++){
			$image = $variation->images[$j];
			if($maindone){
				//image_pairs
				$obj->image_pairs["".$j] = array(
					"detailed" => array(
						"image_path" => $image->url
					)
				);
			}else{
				$maindone = true;
				//main_pair
				$obj->main_pair = array(
					"detailed" => array(
						"image_path" => $image->url
					)
				);
			}
		}
	}
	if(!isset($universal->_id)){//is new, add default category
		$obj->category_ids = $defaultcategoryids;
	}

	if(isset($universal->brand)){
		//GET search for feature, with description brand
		//extract feature id with that prefix
		//GET get feature id -> variants
		//search for variant id with brand match
		//push that feature with that variant on product
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=features&items_per_page=1000');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Basic '.$basicauth
		));
		$rawcontent = trim(curl_exec($ch));
		curl_close($ch);
		$featurespage = json_decode($rawcontent);
		$featureid = -1;
		for($i=0; $i<count($featurespage->features); $i++){
			$feature = $featurespage->features[$i];
			if(strtolower($feature->description) == 'brand'){
				$featureid = $feature->feature_id;
			}
		}

		if($featureid != -1){

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=features/'.$featureid);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Basic '.$basicauth
			));
			$rawcontent = trim(curl_exec($ch));
			curl_close($ch);
			$feature = json_decode($rawcontent);
			$variationid = -1;

			for($i=0; $i<count($feature->variants); $i++){
				$variant = $featurespage->variants[$i];
				if(strtolower($variant->variant) == strtolower($universal->brand)){
					$variationid = $variant->variant_id;
				}
			}

			if($variationid != -1){

				$obj->product_features = array(
					"".$featureid => array(
						"feature_id" => "".$featureid,
						"variant_id" => "".$variationid
					)
				);

			}

		}
		
	}
	return $obj;

}

if($_SERVER['HTTP_C2SKEY'] == $c2skey){

	if($_SERVER['REQUEST_METHOD'] == "POST"){

		if($_GET["entity"] == "products"){

			$postedprod = json_decode(file_get_contents('php://input'));
			
			if(isset($postedprod->_id)){
				//update
				$id = $postedprod->_id;
				$update = buildCSCartObjectFromUniversal($postedprod);
				$data_json = json_encode($update);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=products/'.$id);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Basic '.$basicauth,
					'Content-Type: application/json',
					'Content-Length: '.strlen($data_json)
				));
				$rawcontent = trim(curl_exec($ch));
				curl_close($ch);
				$resp = json_decode($rawcontent);
				$return = new stdClass();
				if(isset($resp->product_id)){
					$return->success = true;
				}else{
					$return->success = false;
					$return->code = 403;
					$return->error = 'Could not update product';
					$return->reasons = array();
					if(isset($resp->message)){
						$reason = new stdClass();
						$reason->message = $resp->message;
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
				$insert = buildCSCartObjectFromUniversal($postedprod);
				$data_json = json_encode($insert);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=products');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Basic '.$basicauth,
					'Content-Type: application/json',
					'Content-Length: '.strlen($data_json)
				));
				$rawcontent = trim(curl_exec($ch));
				curl_close($ch);
				$resp = json_decode($rawcontent);
				$return = new stdClass();
				if(isset($resp->product_id)){
					$return->success = true;
					$return->product = new stdClass();
					$return->product->_id = $resp->product_id;
				}else{
					$return->success = false;
					$return->error = 403;
					$return->message = 'Could not create product';
					$return->reasons = array();
					if(isset($resp->message)){
						$reason = new stdClass();
						$reason->message = $resp->message;
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

			$sortorder = "asc";
			if($_GET["sortorder"] == "desc"){
				$sortorder = "desc";
			}
			$offset = (int)$_GET['offset'];
			$page = ((int)($offset/$prodpagesize))+1;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=products&sort_by=updated_timestamp&sort_order='.$sortorder.'&sort_order_rev='.$sortorder.'&page='.$page.'&items_per_page='.$prodpagesize);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Basic '.$basicauth
			));
			$content = trim(curl_exec($ch));
			curl_close($ch);
			$csobj = json_decode($content);

			$c2spage = new stdClass();
			$c2spage->paging = new stdClass();
			$c2spage->paging->pageSize = $prodpagesize;
			$c2spage->paging->itemsTotal = $csobj->params ? (int)$csobj->params->total_items : 999;
			$c2spage->paging->offset = $offset;
			$c2spage->products = array();

			for($i=0; $i<count($csobj->products); $i++){
				$csprod = $csobj->products[$i];
				$c2sprod = new stdClass();
				$c2sprod->_id = $csprod->product_id;
				$c2sprod->sku = $csprod->product_id;
				$c2sprod->title = $csprod->product;
				$c2sprod->url = $urlprpodprefix.$csprod->product_id;
				$c2sprod->brand = "";
				$c2sprod->last_updated = ((int)$csprod->updated_timestamp)*1000;
				foreach($csprod->product_features as $fid => $feature){
					if(strtolower($feature->description) == "brand" || strtolower($feature->description) == "marca" || strtolower($feature->feature_type) == "e"){
						$c2sprod->brand = $feature->variant;
					}
				}
				$c2sprod->mpn = $csprod->product_code;
				$c2sprod->model = "";
				$c2sprod->description = "";
				$c2sprod->tags = array();
				
				$c2sprod->variations = array();
				$variation = new stdClass();

				$variation->availabilities = array();
				$stock = new stdClass();
				$stock->tag = "default";
				$stock->quantity = (int)$csprod->amount;
				$variation->availabilities[] = $stock;

				$variation->prices = array();
				$price = new stdClass();
				$price->tag = "default";
				$price->currency = $defaultcurrency;
				$price->number = (float)$csprod->price;
				$variation->prices[] = $price;

				$variation->images = array();
				if(isset($csprod->main_pair) && isset($csprod->main_pair->detailed) && isset($csprod->main_pair->detailed->image_path)){
					$url = $csprod->main_pair->detailed->image_path;
					if(strlen($url)>0){
						$image = new stdClass();
						$image->url = $url;
						$variation->images[] = $image;
					}
				}
				foreach($csprod->image_pairs as $pairid => $csimage){
					$url = isset($csimage->detailed) ? $csimage->detailed->image_path : "";
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
				
				$c2sprod->variations[] = $variation;

				$c2spage->products[] = $c2sprod;
			}

			echo(json_encode($c2spage));
			exit();

		}else if($_GET["entity"] == "orders"){

			if(isset($_GET["id"])){

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=orders/'.$_GET["id"]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Basic '.$basicauth
				));
				$content = trim(curl_exec($ch));
				curl_close($ch);
				$csord = json_decode($content);

				$c2sord = new stdClass();
				$c2sord->_id = $csord->order_id;
				$c2sord->orderid = $csord->order_id;

				$c2sord->last_updated = ((int)$csord->timestamp)*1000;
				if($c2sord->last_updated >= (time()*1000)-1000*60*60*24*7){
					$c2sord->last_updated = time()*1000;
				}

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=statuses&items_per_page=1000');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Basic '.$basicauth
				));
				$content = trim(curl_exec($ch));
				curl_close($ch);
				$statusespage = json_decode($content);
				$statuses = isset($statusespage->statuses) ? $statusespage->statuses : $statusespage;
				$c2sord->status = $csord->status;
				foreach($statuses as $status){
					if($status->type == "O" && $status->status == $csord->status){
						$c2sord->status = $status->description;
					}
				}

				$c2sord->dateCreated = ((int)$csord->timestamp)*1000;
				$c2sord->dateClosed = 0;

				$total = new stdClass();
				$total->amount = $csord->total;
				$total->currency = $defaultcurrency;
				$c2sord->total = $total;

				$orderItems = array();
				foreach($csord->products as $itemid => $line){
					$orderItem = new stdClass();
					$orderItem->id = $line->product_id;
					$orderItem->quantity = (int)$line->amount;
					$orderItem->unitPrice = (float)$line->price;
					$orderItem->currencyId = $defaultcurrency;
					$orderItems[] = $orderItem;
				}
				foreach($csord->shipping as $shipping){
					if(is_numeric($shipping->rate)){
						$orderItem = new stdClass();
						$orderItem->id = 'shipping';
						$orderItem->quantity = 1;
						$orderItem->unitPrice = (float)$shipping->rate;
						$orderItem->currencyId = $defaultcurrency;
						$orderItems[] = $orderItem;
					}
				}
				$surcharge = 0;
				if(isset($csord->payment_surcharge)){
					$surcharge = (float)$csord->payment_surcharge;
				}else if(isset($csord->payment_method->a_surcharge)){
					$surcharge = (float)$csord->payment_method->a_surcharge;
				}
				if($surcharge>0){
					$orderItem = new stdClass();
					$orderItem->id = 'surcharge';
					$orderItem->quantity = 1;
					$orderItem->unitPrice = (float)$surcharge;
					$orderItem->currencyId = $defaultcurrency;
					$orderItems[] = $orderItem;
				}
				$c2sord->orderItems = $orderItems;

				$buyer = new stdClass();
				$buyer->id = $csord->user_id;
				$buyer->email = $csord->email;
				$buyer->phone = $csord->phone;
				$buyer->firstName = $csord->firstname;
				$buyer->lastName = $csord->lastname;

				$c2sord->buyer = $buyer;
				echo(json_encode($c2sord));
				exit();

			}else{

				$sortorder = "asc";
				if($_GET["sortorder"] == "desc"){
					$sortorder = "desc";
				}
				$offset = (int)$_GET['offset'];
				$page = ((int)($offset/$orderspagesize))+1;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://mycscart.example.com/shop/api.php?_d=orders&sort_by=date&sort_order='.$sortorder.'&page='.$page.'&items_per_page='.$orderspagesize);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Authorization: Basic '.$basicauth
				));
				$content = trim(curl_exec($ch));
				curl_close($ch);
				$csobj = json_decode($content);

				$c2spage = new stdClass();
				$c2spage->paging = new stdClass();
				$c2spage->paging->pageSize = $orderspagesize;
				$c2spage->paging->itemsTotal = $csobj->params ? (int)$csobj->params->total_items : 99999;
				$c2spage->paging->offset = $offset;
				$c2spage->orders = array();
				$remoteorders = $csobj->orders ? $csobj->orders : $csobj;

				for($i=0; $i<count($remoteorders); $i++){
					
					$csord = $remoteorders[$i];
					$c2sord = new stdClass();
					$c2sord->_id = $csord->order_id;
					$c2sord->orderid = $csord->order_id;

					$c2sord->last_updated = ((int)$csord->timestamp)*1000;
					if($c2sord->last_updated >= (time()*1000)-1000*60*60*24*7){
						$c2sord->last_updated = time()*1000;
					}

					$c2spage->orders[] = $c2sord;
				}

				if(count($c2spage->orders)<=0){
					$c2spage->paging->itemsTotal = $offset;
				}
				
				echo(json_encode($c2spage));
				exit();

			}

		}

	}

}else{

	echo "401 Unauthorized";
	exit();

}
