<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Installer.php';

use FavoriteCMS\Shop\Installer;

$dsn=(string)(getenv('FAVORITE_SHOP_TEST_DSN')?:'');
if($dsn===''){fwrite(STDERR,"FAVORITE_SHOP_TEST_DSN is required.\n");exit(2);}
$pdo=new PDO($dsn,(string)getenv('FAVORITE_SHOP_TEST_USER'),(string)getenv('FAVORITE_SHOP_TEST_PASSWORD'),[
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
foreach(Installer::statements() as $sql)$pdo->exec($sql);
foreach([
 'favorite_shop_products'=>['labels_json'=>'JSON NULL','low_stock_threshold'=>'DECIMAL(14,3) NOT NULL DEFAULT 0','allow_backorder'=>'TINYINT(1) NOT NULL DEFAULT 0','unit_type'=>"VARCHAR(20) NOT NULL DEFAULT 'piece'",'unit_quantity'=>'DECIMAL(14,6) NOT NULL DEFAULT 1','unit_label'=>'VARCHAR(80) NULL'],
 'favorite_shop_product_variants'=>['stock_status'=>"VARCHAR(20) NOT NULL DEFAULT 'in_stock'",'low_stock_threshold'=>'DECIMAL(14,3) NOT NULL DEFAULT 0','allow_backorder'=>'TINYINT(1) NOT NULL DEFAULT 0'],
 'favorite_shop_offers'=>['labels_json'=>'JSON NULL','max_discount_cents'=>'BIGINT NULL','buy_quantity'=>'INT UNSIGNED NULL','get_quantity'=>'INT UNSIGNED NULL','bundle_quantity'=>'INT UNSIGNED NULL'],
 'favorite_shop_coupons'=>['labels_json'=>'JSON NULL'],
 'favorite_shop_order_addresses'=>['division'=>'VARCHAR(120) NULL'],
 'favorite_shop_orders'=>['shipping_zone_snapshot'=>'VARCHAR(190) NULL','coupon_code_snapshot'=>'VARCHAR(100) NULL','discount_details_json'=>'JSON NULL'],
 'favorite_shop_order_items'=>['stock_managed_snapshot'=>'TINYINT(1) NOT NULL DEFAULT 1','stock_reserved_quantity'=>'DECIMAL(14,3) NOT NULL DEFAULT 0','unit_snapshot'=>"VARCHAR(20) NOT NULL DEFAULT 'piece'",'unit_quantity_snapshot'=>'DECIMAL(14,6) NOT NULL DEFAULT 1','unit_label_snapshot'=>'VARCHAR(80) NULL','shipping_weight_grams_snapshot'=>'INT NULL'],
] as $table=>$columns){
 foreach($columns as $column=>$definition){
  $q=$pdo->prepare("SHOW COLUMNS FROM $table LIKE ?");$q->execute([$column]);
  if(!$q->fetch())$pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
 }
}
foreach([
 'favorite_shop_products'=>['stock_quantity'],
 'favorite_shop_product_variants'=>['stock_quantity'],
 'favorite_shop_inventory_movements'=>['quantity_delta','quantity_after'],
 'favorite_shop_cart_items'=>['quantity'],
 'favorite_shop_order_items'=>['quantity'],
] as $table=>$columns)foreach($columns as $column){
 $default=$column==='stock_quantity'?' DEFAULT 0':($column==='quantity'&&$table==='favorite_shop_cart_items'?' DEFAULT 1':'');
 $pdo->exec("ALTER TABLE $table MODIFY COLUMN $column DECIMAL(14,3) NOT NULL$default");
}
foreach([
 'favorite_shop_products','favorite_shop_product_categories','favorite_shop_product_category_map','favorite_shop_product_variants',
 'favorite_shop_inventory_movements','favorite_shop_carts','favorite_shop_cart_items','favorite_shop_orders','favorite_shop_order_items',
 'favorite_shop_order_addresses','favorite_shop_shipments','favorite_shop_order_events','favorite_shop_offers','favorite_shop_offer_redemptions',
 'favorite_shop_coupons','favorite_shop_coupon_redemptions','favorite_shop_delivery_zones','favorite_shop_settings'
] as $table){
 $q=$pdo->prepare('SHOW TABLES LIKE ?');$q->execute([$table]);if(!$q->fetchColumn())throw new RuntimeException("Missing table: $table");
}
$q=$pdo->query("SHOW COLUMNS FROM favorite_shop_products LIKE 'stock_quantity'");$col=$q->fetch();
if(!str_starts_with(strtolower((string)$col['Type']),'decimal(14,3)'))throw new RuntimeException('Product stock quantity must be DECIMAL(14,3).');
$q=$pdo->query("SHOW COLUMNS FROM favorite_shop_order_items LIKE 'quantity'");$col=$q->fetch();
if(!str_starts_with(strtolower((string)$col['Type']),'decimal(14,3)'))throw new RuntimeException('Order quantity must be DECIMAL(14,3).');
foreach(Installer::statements() as $sql)$pdo->exec($sql);
echo "Favorite Shop MySQL schema smoke test passed.\n";
