package com.example.c2s;

import java.io.IOException;
import java.io.OutputStream;
import java.net.InetSocketAddress;

import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpHandler;
import com.sun.net.httpserver.HttpServer;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

import org.json.simple.JSONObject;
import org.json.simple.JSONArray;
import org.apache.commons.io.IOUtils;
import java.util.*;

public class C2SAdapterServer {

	String userName = "username";
	String password = "password";
	String url = "jdbc:sqlserver://MYPC\\SQLEXPRESS;databaseName=MYDB";
	Connection conn;

	public static void main(String[] args) throws Exception {
		HttpServer server = HttpServer.create(new InetSocketAddress(8000), 0);
		server.createContext("/api/products", new MyProductsHandler());
		server.createContext("/api/orders", new MyOrdersHandler());
		this.adapter = this;
		try{
			Class.forName("com.microsoft.sqlserver.jdbc.SQLServerDriver");
			this.conn = DriverManager.getConnection(this.url, this.userName, this.password);
			server.start();
		}catch(SQLException ex){
			System.out.println("SQLException: " + ex.getMessage());
			System.out.println("SQLState: " + ex.getSQLState());
			System.out.println("VendorError: " + ex.getErrorCode());
		}
	}

	public Map<String, String> queryToMap(String query){
		Map<String, String> result = new HashMap<String, String>();
		for (String param : query.split("&")) {
			String pair[] = param.split("=");
			if (pair.length>1) {
				result.put(pair[0], pair[1]);
			}else{
				result.put(pair[0], "");
			}
		}
		return result;
	}

	static class MyProductsHandler implements HttpHandler {
		@Override
		public void handle(HttpExchange t) throws IOException {

			String response = "";
			if("get".equalsIgnoreCase(t.getRequestMethod())){
				int count = 0;
				Map<String, String> params = C2SAdapterServer.this.queryToMap(t.getRequestURI().getQuery());
				int offset = Integer.parseInt(params.get("offset"));
				Statement statement = C2SAdapterServer.this.conn.createStatement();
				String queryCount = "select count(*) from products";
				String queryString = "select * from products order by last_updated ASC LIMIT 50 OFFSET "+offset;
				ResultSet rsCount = statement.executeQuery(queryCount);
				while(rsCount.next()){
					count = rsCount.getInt(1);
				}
				ResultSet rsProds = statement.executeQuery(queryString);
				JSONObject jsonresponse = new JSONObject();
				JSONObject paging = new JSONObject();
				JSONObject products = new JSONArray();
				paging.put("pageSize",50);
				paging.put("itemsTotal",count);
				paging.put("offset",offset);
				jsonresponse.put("paging",paging);
				while (rsProds.next()) {

					JSONObject product = new JSONObject();
					product.put("sku",rsProds.getString("sku"));
					product.put("title",rsProds.getString("productname"));
					product.put("url","https://www.example.com/"+rsProds.getString("slug"));
					product.put("brand",rsProds.getString("brandname"));
					product.put("mpn",rsProds.getString("mpn"));
					product.put("model",rsProds.getString("model"));
					product.put("description",rsProds.getString("description"));

					JSONArray variations = new JSONArray();
					JSONObject variation = new JSONObject();

					JSONArray stocks = new JSONArray();
					JSONObject stock = new JSONObject();
					stock.put("tag","default");
					stock.put("quantity",rsProds.getDouble("stock"));
					stocks.put(stock);
					variation.put("availabilities",stocks);

					JSONArray prices = new JSONArray();
					JSONObject price = new JSONObject();
					price.put("tag","default");
					price.put("number",rsProds.getDouble("price"));
					price.put("currency","USD");
					prices.put(price);
					variation.put("prices",prices);

					JSONArray images = new JSONArray();
					JSONObject image = new JSONObject();
					image.put("url","https://www.example.com/images/"+rsProds.getString("sku")+"-large.jpg");
					images.put(image);
					variation.put("images",images);

					JSONArray videos = new JSONArray();
					JSONObject video = new JSONObject();
					video.put("url","https://www.youtube.com/watch?v="+rsProds.getString("videoId"));
					videos.put(video);
					variation.put("videos",videos);

					variation.put("barcode",rsProds.getString("upc"));

					variations.put(variation);
					products.put(product);

				}
				jsonresponse.put("products",products);
				response = jsonresponse.toString();
			}else if("post".equalsIgnoreCase(t.getRequestMethod())){
				response = "Unimplemented method";
			}else{
				response = "Unsupported method";
			}

			t.sendResponseHeaders(200, response.length());
			OutputStream os = t.getResponseBody();
			os.write(response.getBytes());
			os.close();
		}
	}

	static class MyOrdersHandler implements HttpHandler {
		@Override
		public void handle(HttpExchange t) throws IOException {
			String response = "Unimplemented endpoint";
			t.sendResponseHeaders(200, response.length());
			OutputStream os = t.getResponseBody();
			os.write(response.getBytes());
			os.close();
		}
	}

}