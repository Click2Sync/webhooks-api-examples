const express = require('express')
const app = express()
var MongoClient = require('mongodb').MongoClient;
var url = "mongodb://localhost:27017/mydb";

MongoClient.connect(url, function(err, db) {

	if(!err){

		app.get('/api/products', function (req, res) {
			var offset = 0;
			if(req.query.offset){
				offset = req.query.offset;
			}
			db.collection("products").count({},function(err, count){
				if(!err){
					db.collection("products").find({}).limit(50).skip(offset).toArray(function(err, result) {
						if (!err){
							var response = {
								paging:{
									pageSize:50,
									itemsTotal:count,
									offset:0
								},
								products:[]
							};
							for(var i=0; i<result.length; i+=1){
								var dbproduct = result[i];
								var product = {};
								product.sku = dbproduct._id;
								product.title = dbproduct.title;
								product.url = dbproduct.url;
								product.brand = dbproduct.brand;
								product.mpn = dbproduct.mpn;
								product.model = dbproduct.model;
								product.description = dbproduct.description;
								product.variations = [{
									availabilities:[{
										tag:'default',
										quantity:dbproduct.stock
									}],
									prices:[{
										tag:'default',
										currency:'USD',
										number:dbproduct.price
									}],
									images:[{
										url:dbproduct.imgurl
									}],
									videos:[{
										url:dbproduct.youtubeurl
									}],
									barcode:dbproduct.upc,
									size:dbproduct.size,
									color:dbproduct.color
								}];
								response.products.push(product);
							}
							res.json(response);
						}else{
							res.json({'msg':'could not query collections'});
						}
					});
				}else{
					res.json({'msg':'could not count products'});
				}
			});

		})

		app.listen(3000, function () {
			console.log('Click2Sync app listening on port 3000!')
		})

	}else{

		console.log('Could not connect to database ):');

	}

});