<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Shop\Domain\CouponPolicy;
use FavoriteCMS\Shop\Domain\OfferPricing;
use FavoriteCMS\Shop\Domain\OfferSchedule;
use FavoriteCMS\Shop\Domain\PromotionEngine;
use Throwable;

/**
 * Physical-product storefront, session cart and guest COD checkout.
 * Every amount and stock decision is rebuilt from database records server-side.
 */
final class CustomerShopController
{
    public function __construct(private Application $app) {}

    private function db(): \PDO
    {
        $db = $this->app->make(Database::class);
        if (!method_exists($db, 'getConnection') || !($pdo = $db->getConnection()) instanceof \PDO) {
            throw new \RuntimeException('Favorite Shop database connection is unavailable.');
        }
        return $pdo;
    }

    public function index(Request $request): string
    {
        $search = trim((string)$request->get('q', ''));
        $sql = "SELECT p.* FROM favorite_shop_products p WHERE p.status='published'";
        $params = [];
        if ($search !== '') { $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $params = ['%'.$search.'%', '%'.$search.'%']; }
        $sql .= " ORDER BY p.created_at DESC LIMIT 100";
        $q = $this->db()->prepare($sql); $q->execute($params);
        $rows = $q->fetchAll(\PDO::FETCH_ASSOC);
        $html = $this->shell('Shop', '<form method="get"><input name="q" value="'.self::e($search).'" placeholder="Search products"><button>Search</button></form><div class="grid">'; 
        foreach ($rows as $p) {
            $price = (int)($p['sale_price_cents'] ?? 0) > 0 ? (int)$p['sale_price_cents'] : (int)$p['price_cents'];
            $image = trim((string)($p['cover_image_url'] ?? ''));
            $html .= '<article class="card">'.($image!==''?'<img loading="lazy" src="'.self::e($image).'" alt="'.self::e($p['name']).'">':'')
                .'<h2>'.self::e($p['name']).'</h2><p>'.self::money($price).'</p><form method="post" action="/shop/cart/add/'.(int)$p['id'].'">'
                .$this->csrf().'<label>Quantity <input type="number" min="1" step="1" name="quantity" value="1" required></label><button>Add to cart</button></form></article>';
        }
        return $this->shell('Shop', '<form method="get"><input name="q" value="'.self::e($search).'" placeholder="Search products"><button>Search</button></form><div class="grid">'.implode('', array_map(fn($p)=>$this->productCard($p),$rows)).'</div><p><a href="/shop/cart">View cart</a></p>');
    }

    private function productCard(array $p): string
    {
        $price = (int)($p['sale_price_cents'] ?? 0) > 0 ? (int)$p['sale_price_cents'] : (int)$p['price_cents'];
        $image = trim((string)($p['cover_image_url'] ?? ''));
        return '<article class="card">'.($image!==''?'<img loading="lazy" src="'.self::e($image).'" alt="'.self::e($p['name']).'">':'')
            .'<h2>'.self::e($p['name']).'</h2><p>'.self::money($price).'</p><form method="post" action="/shop/cart/add/'.(int)$p['id'].'">'
            .$this->csrf().'<label>Quantity <input type="number" min="1" step="1" name="quantity" value="1" required></label><button>Add to cart</button></form></article>';
    }

    public function add(Request $request, string $id): Response
    {
        if (!$this->validCsrf($request)) return $this->flashRedirect('/shop', 'Session expired. Please try again.');
        $productId = filter_var($id, FILTER_VALIDATE_INT);
        $qty = filter_var($request->post('quantity', 1), FILTER_VALIDATE_INT);
        if (!$productId || $productId < 1 || !$qty || $qty < 1 || $qty > 999) return $this->flashRedirect('/shop', 'Choose a valid product quantity.');
        $q = $this->db()->prepare("SELECT id,stock_quantity,manage_stock,allow_backorder FROM favorite_shop_products WHERE id=? AND status='published'");
        $q->execute([$productId]); $p = $q->fetch(\PDO::FETCH_ASSOC);
        if (!$p) return $this->flashRedirect('/shop', 'This product is not available.');
        $cart = $this->cart();
        $newQty = (int)($cart[$productId] ?? 0) + $qty;
        if ((int)$p['manage_stock'] === 1 && (float)$p['stock_quantity'] < $newQty && (int)$p['allow_backorder'] !== 1) return $this->flashRedirect('/shop', 'Not enough stock for that quantity.');
        $cart[$productId] = $newQty; $_SESSION['favorite_shop_cart'] = $cart;
        return $this->flashRedirect('/shop/cart', 'Product added to cart.');
    }

    public function cart(Request $request): string
    {
        $items = $this->cartItems();
        $body = '<h1>Your cart</h1>';
        if (!$items) return $this->shell('Cart', $body.'<p>Your cart is empty.</p><a href="/shop">Continue shopping</a>');
        $subtotal = 0;
        foreach ($items as $item) {
            $line = $item['unit_price_cents'] * $item['quantity']; $subtotal += $line;
            $body .= '<article class="card"><strong>'.self::e($item['name']).'</strong> — '.self::money($item['unit_price_cents']).' × '.(int)$item['quantity'].' = '.self::money($line)
                .'<form method="post" action="/shop/cart/remove/'.(int)$item['product_id'].'">'.$this->csrf().'<button>Remove</button></form></article>';
        }
        $body .= '<p><strong>Subtotal: '.self::money($subtotal).'</strong></p><a class="btn" href="/shop/checkout">Proceed to checkout</a> · <a href="/shop">Continue shopping</a>';
        return $this->shell('Cart', $body);
    }

    public function remove(Request $request, string $id): Response
    {
        if (!$this->validCsrf($request)) return $this->flashRedirect('/shop/cart', 'Session expired. Please try again.');
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $cart = $this->cart(); if ($id) unset($cart[$id]); $_SESSION['favorite_shop_cart'] = $cart;
        return Response::redirect('/shop/cart');
    }

    public function checkout(Request $request): Response|string
    {
        $items = $this->cartItems();
        if (!$items) return Response::redirect('/shop/cart');
        if ($request->method() === 'POST') return $this->placeOrder($request, $items);
        $pricing = $this->calculate($items, '', 0);
        $body = '<h1>Checkout</h1>'. $this->flashMessages().'<form method="post" action="/shop/checkout">'.$this->csrf()
            .'<label>Recipient name<input name="recipient_name" maxlength="190" required value="'.self::e($_SESSION['favorite_shop_checkout']['recipient_name'] ?? '').'"></label>'
            .'<label>Phone<input name="phone" maxlength="40" required value="'.self::e($_SESSION['favorite_shop_checkout']['phone'] ?? '').'"></label>'
            .'<label>Address<input name="address_line1" maxlength="255" required value="'.self::e($_SESSION['favorite_shop_checkout']['address_line1'] ?? '').'"></label>'
            .'<label>Area<input name="area" maxlength="120" value="'.self::e($_SESSION['favorite_shop_checkout']['area'] ?? '').'"></label>'
            .'<label>City / district<input name="city" maxlength="120" required value="'.self::e($_SESSION['favorite_shop_checkout']['city'] ?? 'Dhaka').'"></label>'
            .'<label>Country<select name="country_code"><option value="BD">Bangladesh</option><option value="US">United States</option><option value="GB">United Kingdom</option></select></label>'
            .'<label>Coupon code (optional)<input name="coupon_code" maxlength="100" value="'.self::e($_SESSION['favorite_shop_coupon_code'] ?? '').'"></label>'
            .'<label>Order note<textarea name="customer_note" maxlength="3000">'.self::e($_SESSION['favorite_shop_checkout']['customer_note'] ?? '').'</textarea></label>'
            .'<p>Payment: <strong>Cash on Delivery (COD)</strong></p><button type="submit">Place order</button></form><p>Subtotal before shipping/discount: '.self::money($pricing['subtotal_cents']).'</p>';
        return $this->shell('Checkout', $body);
    }

    private function placeOrder(Request $request, array $items): Response
    {
        if (!$this->validCsrf($request)) return $this->flashRedirect('/shop/checkout', 'Session expired. Please try again.');
        $input = [];
        foreach (['recipient_name'=>190,'phone'=>40,'address_line1'=>255,'address_line2'=>255,'area'=>120,'city'=>120,'country_code'=>2,'customer_note'=>3000,'coupon_code'=>100] as $key=>$max) {
            $value = trim((string)$request->post($key, ''));
            $input[$key] = mb_substr($value, 0, $max);
        }
        $_SESSION['favorite_shop_checkout'] = $input; $_SESSION['favorite_shop_coupon_code'] = $input['coupon_code'];
        if ($input['recipient_name']==='' || $input['phone']==='' || $input['address_line1']==='' || $input['city']==='') return $this->flashRedirect('/shop/checkout', 'Recipient, phone and delivery address are required.');
        if (!preg_match('/^[A-Z]{2}$/', strtoupper($input['country_code']))) $input['country_code']='BD';
        $pdo = $this->db();
        try {
            $pdo->beginTransaction();
            // Re-read product records and lock stock rows before calculating the payable total.
            $locked = [];
            foreach ($items as $item) {
                $q = $pdo->prepare("SELECT * FROM favorite_shop_products WHERE id=? AND status='published' FOR UPDATE");
                $q->execute([(int)$item['product_id']]); $p=$q->fetch(\PDO::FETCH_ASSOC);
                if (!$p) throw new \RuntimeException('A cart product is no longer available.');
                $qty=(int)$item['quantity'];
                if ((int)$p['manage_stock']===1 && (float)$p['stock_quantity'] < $qty && (int)$p['allow_backorder']!==1) throw new \RuntimeException('Insufficient stock for '.(string)$p['name'].'.');
                $unit=(int)$p['price_cents']; if ((int)($p['sale_price_cents'] ?? 0)>0) $unit=min($unit,(int)$p['sale_price_cents']);
                $locked[]=['product_id'=>(int)$p['id'],'name'=>$p['name'],'sku'=>$p['sku'],'quantity'=>$qty,'unit_price_cents'=>$unit,'category_ids'=>$this->categoryIds($pdo,(int)$p['id']),'labels'=>json_decode((string)($p['labels_json'] ?? '[]'),true) ?: [],'manage_stock'=>(int)$p['manage_stock']];
            }
            $shipping = $this->shippingCents($pdo, strtoupper($input['country_code']), $input['city'], $input['area']);
            $pricing = $this->calculateWithPDO($pdo, $locked, $input['coupon_code'], $shipping);
            $orderNumber = 'FS'.gmdate('ymd').strtoupper(bin2hex(random_bytes(5)));
            $userId=(int)($_SESSION['auth_user_id'] ?? 0); if ($userId<1) $userId=null;
            $pdo->prepare("INSERT INTO favorite_shop_orders (order_number,user_id,phone,status,payment_method,payment_status,currency,subtotal_cents,discount_cents,shipping_cents,tax_cents,total_cents,customer_note,coupon_code_snapshot,discount_details_json,shipping_zone_snapshot) VALUES (?,?,?,'pending','cash_on_delivery','unpaid','BDT',?,?,?,?,?,?,?, ?,?)")
                ->execute([$orderNumber,$userId,$input['phone'],$pricing['subtotal_cents'],$pricing['discount_cents'],$shipping,$pricing['tax_cents'],$pricing['total_cents'],$input['customer_note'] ?: null,$pricing['coupon_code'],$pricing['details_json'],$pricing['zone']]);
            $orderId=(int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO favorite_shop_order_addresses (order_id,address_type,recipient_name,phone,address_line1,address_line2,area,city,district,country_code) VALUES (?,'shipping',?,?,?,?,?,?,?,?,?)")
                ->execute([$orderId,$input['recipient_name'],$input['phone'],$input['address_line1'],$input['address_line2'] ?: null,$input['area'] ?: null,$input['city'],$input['city'],$input['country_code']]);
            foreach ($pricing['items'] as $item) {
                $pdo->prepare("INSERT INTO favorite_shop_order_items (order_id,product_id,sku_snapshot,name_snapshot,unit_price_cents,quantity,line_total_cents,unit_snapshot,unit_quantity_snapshot,unit_label_snapshot,shipping_weight_grams_snapshot) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$orderId,$item['product_id'],$item['sku'],$item['name'],$item['unit_price_cents'],(int)$item['quantity'],(int)($item['quantity']*$item['unit_price_cents'])-(int)($item['offer_discount_cents'] ?? 0),$item['unit_type'] ?? 'piece',$item['unit_quantity'] ?? 1,$item['unit_label'] ?? null,$item['weight_grams'] ?? null]);
                if ($item['manage_stock']===1) {
                    $u=$pdo->prepare("UPDATE favorite_shop_products SET stock_quantity=stock_quantity-? WHERE id=? AND (allow_backorder=1 OR stock_quantity>=?)");
                    $u->execute([$item['quantity'],$item['product_id'],$item['quantity']]);
                    if ($u->rowCount()!==1) throw new \RuntimeException('Stock changed during checkout; please retry.');
                    $after=$pdo->prepare("SELECT stock_quantity FROM favorite_shop_products WHERE id=?");$after->execute([$item['product_id']]);$qtyAfter=(float)$after->fetchColumn();
                    $pdo->prepare("INSERT INTO favorite_shop_inventory_movements (product_id,movement_type,quantity_delta,quantity_after,reference_type,reference_id,note,actor_user_id) VALUES (?,'order',?,?, 'order',?,'Stock reserved at checkout',?)")
                        ->execute([$item['product_id'],-$item['quantity'],$qtyAfter,$orderId,$userId]);
                }
            }
            if ($pricing['coupon_id'] !== null) {
                $q=$pdo->prepare("UPDATE favorite_shop_coupons SET usage_count=usage_count+1 WHERE id=? AND status='active' AND (usage_limit IS NULL OR usage_count<usage_limit)");
                $q->execute([$pricing['coupon_id']]);
                if($q->rowCount()!==1) throw new \RuntimeException('Coupon usage limit was reached. Please retry checkout.');
                $pdo->prepare("INSERT INTO favorite_shop_coupon_redemptions (coupon_id,order_id,customer_key,discount_cents) VALUES (?,?,?,?)")
                    ->execute([$pricing['coupon_id'],$orderId,$userId ? 'user:'.$userId : 'guest:'.hash('sha256',$input['phone']),$pricing['coupon_discount_cents']]);
            }
            $pdo->commit();
            unset($_SESSION['favorite_shop_cart'],$_SESSION['favorite_shop_checkout'],$_SESSION['favorite_shop_coupon_code']);
            $_SESSION['flash_success']='Order placed. Pay cash on delivery; payment remains unpaid until collected.';
            return Response::redirect('/shop/order/'.$orderNumber);
        } catch(Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            return $this->flashRedirect('/shop/checkout', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Could not place the order: '.$e->getMessage());
        }
    }

    public function order(Request $request, string $orderNumber): Response|string
    {
        $q=$this->db()->prepare("SELECT o.*,a.recipient_name,a.address_line1,a.area,a.city,a.country_code FROM favorite_shop_orders o LEFT JOIN favorite_shop_order_addresses a ON a.order_id=o.id AND a.address_type='shipping' WHERE o.order_number=?");
        $q->execute([$orderNumber]);$o=$q->fetch(\PDO::FETCH_ASSOC);
        if(!$o)return Response::make('<h1>Order not found</h1>',404);
        if((int)($o['user_id'] ?? 0)>0 && (int)($_SESSION['auth_user_id'] ?? 0)!==(int)$o['user_id'])return Response::make('<h1>403 Access denied</h1>',403);
        return $this->shell('Order '.$orderNumber,'<h1>Thank you for your order</h1><p>Order: '.self::e($orderNumber).'</p><p>Status: '.self::e($o['status']).'</p><p>Payment: '.self::e($o['payment_status']).' — Cash on Delivery</p><p>Total: '.self::money((int)$o['total_cents']).'</p><p>Deliver to: '.self::e($o['recipient_name'] ?? '').', '.self::e($o['address_line1'] ?? '').', '.self::e($o['city'] ?? '').'</p><a href="/shop">Continue shopping</a>');
    }

    private function calculate(array $items, string $couponCode, int $shipping): array
    {
        return $this->calculateWithPDO($this->db(),$items,$couponCode,$shipping);
    }

    private function calculateWithPDO(\PDO $pdo, array $items, string $couponCode, int $shipping): array
    {
        $subtotal=0;foreach($items as $i)$subtotal+=(int)$i['unit_price_cents']*(int)$i['quantity'];
        $offerRows=$pdo->query("SELECT * FROM favorite_shop_offers WHERE status IN ('scheduled','active') AND starts_at<=UTC_TIMESTAMP() AND ends_at>UTC_TIMESTAMP() ORDER BY priority DESC")->fetchAll(\PDO::FETCH_ASSOC);
        $offers=PromotionEngine::applyOffers($items,$offerRows,$shipping);
        $discount=(int)$offers['discount_cents'];$shipDiscount=(int)$offers['shipping_discount_cents'];$couponId=null;$couponDiscount=0;$couponCodeSaved=null;
        if($couponCode!==''){
            $q=$pdo->prepare("SELECT * FROM favorite_shop_coupons WHERE code=? AND status='active' LIMIT 1");$q->execute([strtoupper(trim($couponCode))]);$coupon=$q->fetch(\PDO::FETCH_ASSOC);
            if(!$coupon)throw new \InvalidArgumentException('Coupon code is invalid or inactive.');
            $uses=(int)$coupon['usage_count'];$customerKey=isset($_SESSION['auth_user_id'])?'user:'.(int)$_SESSION['auth_user_id']:'guest:'.hash('sha256',(string)($_SESSION['favorite_shop_checkout']['phone'] ?? ''));
            $cq=$pdo->prepare('SELECT COUNT(*) FROM favorite_shop_coupon_redemptions WHERE coupon_id=? AND customer_key=?');$cq->execute([(int)$coupon['id'],$customerKey]);$customerUses=(int)$cq->fetchColumn();
            if(CouponPolicy::state($coupon,$uses,$customerUses)!=='active')throw new \InvalidArgumentException('Coupon is not active or its usage limit has been reached.');
            if($subtotal<(int)$coupon['min_subtotal_cents'])throw new \InvalidArgumentException('Cart subtotal does not meet the coupon minimum.');
            $couponDiscount=PromotionEngine::couponDiscount($coupon,$offers['items'],$shipping);
            if((string)$coupon['discount_type']==='free_shipping')$shipDiscount=max($shipDiscount,$couponDiscount);else $discount+=$couponDiscount;
            $couponId=(int)$coupon['id'];$couponCodeSaved=$coupon['code'];
        }
        $total=max(0,$subtotal-$discount+max(0,$shipping-$shipDiscount));
        $lineItems=$offers['items'];
        return ['items'=>$lineItems,'subtotal_cents'=>$subtotal,'discount_cents'=>$discount,'coupon_discount_cents'=>$couponDiscount,'shipping_discount_cents'=>$shipDiscount,'shipping_cents'=>max(0,$shipping-$shipDiscount),'tax_cents'=>0,'total_cents'=>$total,'coupon_id'=>$couponId,'coupon_code'=>$couponCodeSaved,'details_json'=>json_encode(['offers'=>$offers['applied'],'coupon_discount_cents'=>$couponDiscount,'shipping_discount_cents'=>$shipDiscount]),'zone'=>'default','discounts'=>$offers];
    }

    private function cartItems(): array
    {
        $cart=$this->cart();if(!$cart)return [];
        $ids=array_keys($cart);$q=$this->db()->prepare("SELECT * FROM favorite_shop_products WHERE status='published' AND id IN (".implode(',',array_fill(0,count($ids),'?')).")");$q->execute($ids);$rows=$q->fetchAll(\PDO::FETCH_ASSOC);$items=[];
        foreach($rows as $p){$price=(int)$p['price_cents'];if((int)($p['sale_price_cents']??0)>0)$price=min($price,(int)$p['sale_price_cents']);$items[]=['product_id'=>(int)$p['id'],'name'=>$p['name'],'sku'=>$p['sku'],'quantity'=>(int)$cart[$p['id']],'unit_price_cents'=>$price,'category_ids'=>$this->categoryIds($this->db(),(int)$p['id']),'labels'=>json_decode((string)($p['labels_json']??'[]'),true)?:[],'unit_type'=>$p['unit_type']??'piece','unit_quantity'=>$p['unit_quantity']??1,'unit_label'=>$p['unit_label']??null,'weight_grams'=>$p['weight_grams']??null,'manage_stock'=>(int)$p['manage_stock']];}
        return $items;
    }
    private function categoryIds(\PDO $pdo,int $id):array{$q=$pdo->prepare('SELECT category_id FROM favorite_shop_product_category_map WHERE product_id=?');$q->execute([$id]);return array_map('intval',$q->fetchAll(\PDO::FETCH_COLUMN));}
    private function shippingCents(\PDO $pdo,string $country,string $city,string $area):int{$q=$pdo->prepare("SELECT setting_value FROM favorite_shop_settings WHERE setting_key=?");$q->execute([$country==='BD'?'shipping_bd_default_cents':'shipping_intl_default_cents']);$v=$q->fetchColumn();return $v===false?0:max(0,(int)$v);}
    private function cart():array{$c=$_SESSION['favorite_shop_cart']??[];return is_array($c)?$c:[];}
    private function validCsrf(Request $r):bool{$submitted=(string)$r->post('_token','');$session=(string)($_SESSION['_token']??'');return $submitted!==''&&$session!==''&&hash_equals($session,$submitted);}
    private function csrf():string{if(empty($_SESSION['_token']))$_SESSION['_token']=bin2hex(random_bytes(32));return '<input type="hidden" name="_token" value="'.self::e($_SESSION['_token']).'">';}
    private function flashRedirect(string $url,string $message):Response{$_SESSION['flash_error']=$message;return Response::redirect($url);}
    private function flashMessages():string{$out='';foreach(['flash_error','flash_success'] as $k){if(isset($_SESSION[$k])){$out.='<p class="notice">'.self::e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}}return $out;}
    private function shell(string $title,string $body):string{return '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).'</title><style>body{font:16px system-ui;max-width:1100px;margin:30px auto;padding:0 16px;color:#222}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}.card{border:1px solid #ddd;border-radius:12px;padding:16px;margin:12px 0}.card img{max-width:100%;max-height:220px;object-fit:contain}label{display:block;margin:10px 0}input,textarea,select{display:block;width:100%;box-sizing:border-box;padding:10px;border:1px solid #bbb;border-radius:7px;margin-top:4px}button,.btn{padding:10px 14px;background:#175bd7;color:white;border:0;border-radius:7px;cursor:pointer;text-decoration:none}a{color:#175bd7}.notice{background:#fff2d9;padding:12px;border-radius:8px}</style><nav><a href="/shop">Shop</a> · <a href="/shop/cart">Cart</a> · <a href="/shop/checkout">Checkout</a></nav><main>'.$body.'</main></html>';}
    private static function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private static function money(int $cents):string{return '৳'.number_format($cents/100,2);}
}
