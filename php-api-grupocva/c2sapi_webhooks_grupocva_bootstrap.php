<?php

//require json
//require http class

ini_set('display_errors', 0);
ini_set('max_execution_time', 0);
set_time_limit(0);

//params
$urlprpodprefix = "http://www.grupocva.com/catalogo_clientes_xml/";
$c2skey = "";
$cliente = "";
$marcas = array("1","2","3");
$precio_dolar_en_pesos = 18.72;
$stock_ajuste_riesgo = -2;
$grupo = "%";
$clave = "%";
$codigo = "%";
$search = "";
$promociones = true;
$porcentaje = 0;
$sucursales = true;
$subgrupo = true;
$tc = true;
$dt = true;
$dc = true;
$depto = true;
$upc = true;
$exist = "";
$defaultcurrency = "MXN";
$almacenes = array(
	"GHIA_HAIER_PRODUCTO_TERMINADO",
	"MAYOREO_GHIA",
	"MAYOREO_ING_GHIA",
	"REGIONALES_GHIA",
	"VENTAS_ACAPULCO",
	"VENTAS_AGUASCALIENTES",
	"VENTAS_CAMPECHE",
	"VENTAS_CANCUN",
	"VENTAS_CHIHUAHUA",
	"VENTAS_COLIMA",
	"VENTAS_CULIACAN",
	"VENTAS_DF_HEROES",
	"VENTAS_DURANGO",
	"VENTAS_FINANCIAMIENTO",
	"VENTAS_GDL_PLAZA_DE_LA_COMPUTACION",
	"VENTAS_GUADALAJARA",
	"VENTAS_HERMOSILLO",
	"VENTAS_LA_PAZ",
	"VENTAS_LEON",
	"VENTAS_MONTERREY",
	"VENTAS_MORELIA",
	"VENTAS_OAXACA",
	"VENTAS_PACHUCA",
	"VENTAS_PUEBLA",
	"VENTAS_QUERETARO",
	"VENTAS_SAN_LUIS_POTOSI",
	"VENTAS_TAMPICO",
	"VENTAS_TEPIC",
	"VENTAS_TIJUANA",
	"VENTAS_TOLUCA",
	"VENTAS_TORREON",
	"VENTAS_TUXTLA",
	"VENTAS_VERACRUZ",
	"VENTAS_VILLAHERMOSA",
	"VENTAS_ZACATECAS"
);

//tunning
$prodpagesize = 50;
$orderspagesize = 50;

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

			$next = "";
			if($_GET["next"]){
				$next = $_GET["next"];
			}
			$offset = (int)$_GET['offset'];

			$thismarca = "";
			$nextmarca = "";
			
			if(count($marcas) == 0){

				//get marcas
				$marcas = getMarcas();
				if($marcas !== false){

					//iterar cada marca
					foreach($marcas as $marca){

						if($thismarca == ""){
							if($next == ""){
								$thismarca = $marca;
							}else{
								if($next == "".$marca){
									$thismarca = "".$marca;
								}
								continue;
							}
						}else{
							$nextmarca = $marca;
							break;
						}

					}

					
				}else{

					//not supported
					echo "could not get marcas";
					exit();

				}

			}else{

				//iterar cada marca
				foreach($marcas as $marca){

					if($thismarca == ""){
						if($next == ""){
							$thismarca = $marca;
						}else{
							if($next == "".$marca){
								$thismarca = "".$marca;
							}
							continue;
						}
					}else{
						$nextmarca = $marca;
						break;
					}

				}

			}


			//request de esa marca
			$ch = curl_init();
			$url = $urlprpodprefix.'lista_precios.xml?cliente='.urlencode($cliente).'&marca='.urlencode($thismarca).'&grupo='.urlencode($grupo).'&clave='.urlencode($clave).'&codigo='.urlencode($codigo).'&search='.urlencode($search).'&promos='.urlencode($promociones?'1':'0').'&porcentaje='.urlencode($porcentaje).'&sucursales='.urlencode($sucursales?'1':'0').'&subgpo='.urlencode($subgrupo?'1':'0').'&tc='.urlencode($tc?'1':'0').'&dt='.urlencode($dt?'1':'0').'&dc='.urlencode($dc?'1':'0').'&depto='.urlencode($depto?'1':'0').'&upc='.urlencode($upc?'1':'0').'&exist='.urlencode($exist);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$content = trim(curl_exec($ch));
			curl_close($ch);

			$articulos = simplexml_load_string($content);

			$c2spage = new stdClass();
			$c2spage->paging = new stdClass();
			$c2spage->paging->pageSize = 0;
			$c2spage->paging->itemsTotal = 9999;
			$c2spage->paging->offset = $offset;
			$c2spage->paging->thismarca = $thismarca;
			$c2spage->paging->next = $nextmarca;
			$c2spage->products = array();

			foreach($articulos->children() as $item){

				$c2sprod = new stdClass();
				$c2sprod->_id = "".$item->clave;
				$c2sprod->sku = "".$item->clave;
				$c2sprod->title = "".$item->descripcion;
				$c2sprod->url = "";
				$c2sprod->brand = "".$item->marca;

				$c2sprod->mpn = "".$item->codigo_fabricante;
				$c2sprod->model = "";
				$ficha_tecnica = (string)$item->ficha_tecnica;
				$ficha_comercial = (string)$item->ficha_comercial;
				$c2sprod->description = $ficha_tecnica." ".$ficha_comercial;

				$c2sprod->tags = array();
				
				$c2sprod->variations = array();
				$variation = new stdClass();

				$variation->availabilities = array();
				$stock = new stdClass();
				$stock->tag = "default";
				$disponible = (int)$item->disponible > 0 ? (int)$item->disponible : 0;
				$disponibleCD = (int)$item->disponibleCD > 0 ? (int)$item->disponibleCD : 0;
				$disponibleSum = 0;
				for($i=0; $i<count($almacenes); $i++){
					$almacen = $almacenes[$i];
					$stkalm = (int)$item->$almacen;
					$disponibleSum += $stkalm;
				}
				$disponiblefinal = (int)(($disponible + $disponibleCD + $disponibleSum) - $stock_ajuste_riesgo);
				$disponiblefinal = $disponiblefinal > 0 ? $disponiblefinal : 0;
				$stock->quantity = $disponiblefinal;
				$variation->availabilities[] = $stock;

				$variation->prices = array();
				$price = new stdClass();
				$price->tag = "default";
				$price->currency = $defaultcurrency;
				if($item->moneda == "Dolares"){
					$price->number = ((float)$item->precio)*$precio_dolar_en_pesos;
				}else{
					$price->number = (float)$item->precio;
				}
				$variation->prices[] = $price;

				$variation->images = array();
				$url = "".$item->imagen;
				if(strlen($url)>0){
					$image = new stdClass();
					$image->url = $url;
					$variation->images[] = $image;
				}

				$variation->videos = array();

				$variation->barcode = "".$item->upc;
				$variation->size = "";
				$variation->color = "";
				
				$c2sprod->variations[] = $variation;

				$c2spage->products[] = $c2sprod;

			}

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

function getMarcas(){

	global $urlprpodprefix;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $urlprpodprefix.'marcas.xml');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$content = trim(curl_exec($ch));
	curl_close($ch);
	$marcas = simplexml_load_string($content);
	$marcassorted = array();
	foreach($marcas->children() as $marca){
		$marcassorted[] = "".$marca;
	}
	sort($marcassorted);
	return $marcassorted;

}