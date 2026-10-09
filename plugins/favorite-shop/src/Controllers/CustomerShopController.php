<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Shop\Domain\CouponPolicy;
use FavoriteCMS\Shop\Domain\OfferPricing;
use FavoriteCMS\Shop\Domain\Quantity;
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
         
        return $this->shell('Shop', '<form method="get"><input name="q" value="'.self::e($search).'" placeholder="Search products"><button>Search</button></form><div class="grid">'.implode('', array_map(fn($p)=>$this->productCard($p),$rows)).'</div><p><a href="/shop/cart">View cart</a></p>');
    }

    private function productCard(array $p): string
    {
        $image=trim((string)($p['cover_image_url']??''));$body='<article class="card">'.($image!==''?'<img loading="lazy" src="'.self::e($image).'" alt="'.self::e($p['name']).'">':'').'<h2>'.self::e($p['name']).'</h2>';
        if(($p['product_type']??'simple')==='variable'){
            $q=$this->db()->prepare("SELECT * FROM favorite_shop_product_variants WHERE product_id=? AND status='active' ORDER BY id");$q->execute([(int)$p['id']]);$variants=$q->fetchAll(\PDO::FETCH_ASSOC);
            if(!$variants)return $body.'<p>Currently unavailable.</p></article>';
            $body.='<form method="post" action="/shop/cart/add/'.(int)$p['id'].'">'.$this->csrf().'<label>Choose variant<select name="variant_id" required>';
            foreach($variants as $v){$options=json_decode((string)$v['option_values_json'],true)?:[];$label=implode(' / ',array_map(fn($k,$value)=>$k.': '.$value,array_keys($options),array_values($options)));$price=$v['price_cents']===null?(int)$p['price_cents']:(int)$v['price_cents'];$sale=$v['sale_price_cents']===null?($p['sale_price_cents']??null):$v['sale_price_cents'];if($sale!==null&&(int)$sale>0)$price=min($price,(int)$sale);$body.='<option value="'.(int)$v['id'].'">'.self::e($label).' — '.self::money($price).' · stock '.self::e($v['stock_quantity']).'</option>';}
            return $body.'</select></label><label>Quantity <input type="number" min="0.001" step="0.001" name="quantity" value="1" required></label><button>Add to cart</button></form></article>';
        }
        $price=(int)$p['price_cents'];if((int)($p['sale_price_cents']??0)>0)$price=min($price,(int)$p['sale_price_cents']);
        return $body.'<p>'.self::money($price).'</p><form method="post" action="/shop/cart/add/'.(int)$p['id'].'">'.$this->csrf().'<label>Quantity <input type="number" min="0.001" step="0.001" name="quantity" value="1" required></label><button>Add to cart</button></form></article>';
    }

    public function add(Request $request, string $id): Response
    {
        if(!$this->validCsrf($request))return $this->flashRedirect('/shop','Session expired. Please try again.');
        $productId=filter_var($id,FILTER_VALIDATE_INT);$qtyRaw=trim((string)$request->post('quantity','1'));
        if(!$productId||$productId<1)return $this->flashRedirect('/shop','Choose a valid product quantity.');
        $q=$this->db()->prepare("SELECT * FROM favorite_shop_products WHERE id=? AND status='published'");$q->execute([$productId]);$p=$q->fetch(\PDO::FETCH_ASSOC);
        if(!$p)return $this->flashRedirect('/shop','This product is not available.');
        $variantId=0;$variant=null;
        if(($p['product_type']??'simple')==='variable'){
            $variantId=filter_var($request->post('variant_id',''),FILTER_VALIDATE_INT);
            if(!$variantId||$variantId<1)return $this->flashRedirect('/shop','Choose a product variant.');
            $vq=$this->db()->prepare("SELECT * FROM favorite_shop_product_variants WHERE id=? AND product_id=? AND status='active'");$vq->execute([$variantId,$productId]);$variant=$vq->fetch(\PDO::FETCH_ASSOC);
            if(!$variant)return $this->flashRedirect('/shop','Selected variant is no longer available.');
        }
        $unit=(string)(($variant['unit_type']??null)?:($p['unit_type']??'piece'));
        try{$qty=Quantity::forUnit($qtyRaw,$unit);}catch(\InvalidArgumentException $e){return $this->flashRedirect('/shop',$e->getMessage());}
        $cart=$this->sessionCart();$key=$variantId>0?$productId.':'.$variantId:(string)$productId;$newQty=(float)($cart[$key]??0)+(float)$qty;
        if($newQty>999999)return $this->flashRedirect('/shop','Cart quantity is too large.');
        $manageStock=$variantId>0?true:(int)$p['manage_stock']===1;$stock=$variantId>0?(float)$variant['stock_quantity']:(float)$p['stock_quantity'];$backorder=$variantId>0?(int)($variant['allow_backorder']??0)===1:(int)$p['allow_backorder']===1;
        if($manageStock&&$stock<$newQty&&!$backorder)return $this->flashRedirect('/shop','Not enough stock for that quantity.');
        $cart[$key]=Quantity::normalize($newQty);$_SESSION['favorite_shop_cart']=$cart;
        return $this->flashRedirect('/shop/cart','Product added to cart.');
    }

    public function cart(Request $request): string
    {
        $items = $this->cartItems();
        $body = '<h1>Your cart</h1>';
        if (!$items) return $this->shell('Cart', $body.'<p>Your cart is empty.</p><a href="/shop">Continue shopping</a>');
        $subtotal = 0;
        foreach ($items as $item) {
            $line = (int)round($item['unit_price_cents'] * (float)$item['quantity']); $subtotal += $line;
            $body .= '<article class="card"><strong>'.self::e($item['name']).'</strong> — '.self::money($item['unit_price_cents']).' × '.self::e($item['quantity']).' = '.self::money($line)
                .'<form method="post" action="/shop/cart/remove/'.self::e($item['cart_key']).'">'.$this->csrf().'<button>Remove</button></form></article>';
        }
        $body .= '<p><strong>Subtotal: '.self::money($subtotal).'</strong></p><a class="btn" href="/shop/checkout">Proceed to checkout</a> · <a href="/shop">Continue shopping</a>';
        return $this->shell('Cart', $body);
    }

    public function remove(Request $request, string $id): Response
    {
        if(!$this->validCsrf($request))return $this->flashRedirect('/shop/cart','Session expired. Please try again.');
        if(!preg_match('/^\d+(?::\d+)?$/',$id))return Response::redirect('/shop/cart');
        $cart=$this->sessionCart();unset($cart[$id]);$_SESSION['favorite_shop_cart']=$cart;
        return Response::redirect('/shop/cart');
    }

    public function checkout(Request $request): Response|string
    {
        $items = $this->cartItems();
        if (!$items) return Response::redirect('/shop/cart');
        if ($request->method() === 'POST') return $this->placeOrder($request, $items);
        $oldAddress=$_SESSION['favorite_shop_checkout']??[];
        $country=strtoupper((string)($oldAddress['country_code']??'BD'));
        $estimatedShipping=$this->shippingCents($this->db(),$country,(string)($oldAddress['division']??''),(string)($oldAddress['district']??''),(string)($oldAddress['city']??'Dhaka'),(string)($oldAddress['area']??''));
        $pricing=$this->calculate($items,'',$estimatedShipping);
        $prepaidMethods = [];
        if ($this->app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)) {
            try { $prepaidMethods = $this->app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)->getAvailablePaymentMethods('BDT'); }
            catch (\Throwable $e) { error_log('[Favorite Shop checkout methods] '.$e->getMessage()); }
        }
        $paymentOptions = '<option value="cash_on_delivery">Cash on Delivery (COD)</option>';
        if ($prepaidMethods) $paymentOptions .= '<option value="favorite_pay">Prepaid — Favorite Pay (configured gateways)</option>';
        $body = '<h1>Checkout</h1>'. $this->flashMessages().'<form method="post" action="/shop/checkout">'.$this->csrf()
            .'<label>Recipient name<input name="recipient_name" maxlength="190" required value="'.self::e($_SESSION['favorite_shop_checkout']['recipient_name'] ?? '').'"></label>'
            .'<label>Phone<input name="phone" maxlength="40" required value="'.self::e($_SESSION['favorite_shop_checkout']['phone'] ?? '').'"></label>'
            .'<label>Address<input name="address_line1" maxlength="255" required value="'.self::e($_SESSION['favorite_shop_checkout']['address_line1'] ?? '').'"></label>'
            .'<label>Area<input name="area" maxlength="120" value="'.self::e($_SESSION['favorite_shop_checkout']['area'] ?? '').'"></label>'
            .'<label>City<input name="city" maxlength="120" required value="'.self::e($_SESSION['favorite_shop_checkout']['city'] ?? 'Dhaka').'"></label>'
            .'<label>District<input name="district" maxlength="120" value="'.self::e($_SESSION['favorite_shop_checkout']['district'] ?? '').'"></label>'
            .'<label>Division / state<input name="division" maxlength="120" value="'.self::e($_SESSION['favorite_shop_checkout']['division'] ?? '').'"></label>'
            .'<label>Postal code<input name="postal_code" maxlength="30" value="'.self::e($_SESSION['favorite_shop_checkout']['postal_code'] ?? '').'"></label>'
            .'<label>Country<select name="country_code"><option value="BD">Bangladesh</option><option value="US">United States</option><option value="GB">United Kingdom</option></select></label>'
            .'<label>Coupon code (optional)<input name="coupon_code" maxlength="100" value="'.self::e($_SESSION['favorite_shop_coupon_code'] ?? '').'"></label>'
            .'<label>Order note<textarea name="customer_note" maxlength="3000">'.self::e($_SESSION['favorite_shop_checkout']['customer_note'] ?? '').'</textarea></label>'
            .'<label>Payment method<select name="payment_method">'.$paymentOptions.'</select></label><section class="card"><strong>Order estimate</strong><p>Items subtotal: '.self::money($pricing['subtotal_cents']).'</p><p>Estimated delivery: '.self::money($estimatedShipping).' ('.self::e($_SESSION['favorite_shop_shipping_zone']??'fallback zone').')</p><p>Estimated total before coupon: '.self::money($pricing['total_cents']).'</p><small>Final delivery rate and offers are recalculated on the server when you place the order.</small></section><button type="submit">Place order</button></form>';
        return $this->shell('Checkout', $body);
    }

    private function placeOrder(Request $request, array $items): Response
    {
        if (!$this->validCsrf($request)) return $this->flashRedirect('/shop/checkout', 'Session expired. Please try again.');
        $input = [];
        foreach (['recipient_name'=>190,'phone'=>40,'address_line1'=>255,'address_line2'=>255,'area'=>120,'city'=>120,'district'=>120,'division'=>120,'postal_code'=>30,'country_code'=>2,'customer_note'=>3000,'coupon_code'=>100,'payment_method'=>40] as $key=>$max) {
            $value = trim((string)$request->post($key, ''));
            $input[$key] = (function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max));
        }
        $_SESSION['favorite_shop_checkout'] = $input; $_SESSION['favorite_shop_coupon_code'] = $input['coupon_code'];
        if (!in_array($input['payment_method'], ['cash_on_delivery','favorite_pay'], true)) $input['payment_method'] = 'cash_on_delivery';
        if ($input['payment_method'] === 'favorite_pay') {
            if (!$this->app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)) return $this->flashRedirect('/shop/checkout', 'Prepaid payments are unavailable. Please choose Cash on Delivery.');
            try {
                $available = $this->app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)->getAvailablePaymentMethods('BDT');
                if (!$available) return $this->flashRedirect('/shop/checkout', 'No prepaid gateway is currently configured. Please choose Cash on Delivery.');
            } catch (\Throwable $e) {
                error_log('[Favorite Shop checkout validation] '.$e->getMessage());
                return $this->flashRedirect('/shop/checkout', 'Could not load payment methods. Please choose Cash on Delivery or try again later.');
            }
        }
        if ($input['recipient_name']==='' || $input['phone']==='' || $input['address_line1']==='' || $input['city']==='') return $this->flashRedirect('/shop/checkout', 'Recipient, phone and delivery address are required.');
        if (!preg_match('/^[A-Z]{2}$/', strtoupper($input['country_code']))) $input['country_code']='BD';
        $pdo = $this->db();
        try {
            $pdo->beginTransaction();
            // Re-read product records and lock stock rows before calculating the payable total.
            $locked = [];
            foreach ($items as $item) {
                $q=$pdo->prepare("SELECT * FROM favorite_shop_products WHERE id=? AND status='published' FOR UPDATE");
                $q->execute([(int)$item['product_id']]);$p=$q->fetch(\PDO::FETCH_ASSOC);
                if(!$p)throw new \RuntimeException('A cart product is no longer available.');
                $qty=(float)$item['quantity'];$variantId=(int)($item['variant_id']??0);$v=null;
                if($variantId>0){
                    $vq=$pdo->prepare("SELECT * FROM favorite_shop_product_variants WHERE id=? AND product_id=? AND status='active' FOR UPDATE");
                    $vq->execute([$variantId,(int)$p['id']]);$v=$vq->fetch(\PDO::FETCH_ASSOC);
                    if(!$v)throw new \RuntimeException('A selected product variant is no longer available.');
                    if(!StockStatus::canFulfil($v['stock_quantity'],$qty,true,(int)($v['allow_backorder']??0)===1))throw new \RuntimeException('Insufficient stock for selected variant of '.(string)$p['name'].'.');
                }elseif((int)$p['manage_stock']===1&&(float)$p['stock_quantity']+0.0000001<$qty&&(int)$p['allow_backorder']!==1)throw new \RuntimeException('Insufficient stock for '.(string)$p['name'].'.');
                $unit=$v&&$v['price_cents']!==null?(int)$v['price_cents']:(int)$p['price_cents'];
                $sale=$v&&$v['sale_price_cents']!==null?$v['sale_price_cents']:($p['sale_price_cents']??null);
                if($sale!==null&&(int)$sale>0)$unit=min($unit,(int)$sale);
                $options=$v?(json_decode((string)$v['option_values_json'],true)?:[]):[];
                $locked[]=['product_id'=>(int)$p['id'],'variant_id'=>$variantId?:null,'variant_snapshot_json'=>$options?json_encode($options,JSON_UNESCAPED_UNICODE):null,'name'=>$item['name'],'sku'=>($v['sku']??null)?:$p['sku'],'quantity'=>$qty,'unit_price_cents'=>$unit,'category_ids'=>$this->categoryIds($pdo,(int)$p['id']),'labels'=>json_decode((string)($p['labels_json']??'[]'),true)?:[],'manage_stock'=>$variantId>0?1:(int)$p['manage_stock'],'allow_backorder'=>$variantId>0?(int)($v['allow_backorder']??0):(int)$p['allow_backorder'],'unit_type'=>($v['unit_type']??null)?:($p['unit_type']??'piece'),'unit_quantity'=>($v['unit_quantity']??null)?:($p['unit_quantity']??1),'unit_label'=>($v['unit_label']??null)?:($p['unit_label']??null),'weight_grams'=>($v['weight_grams']??null)??($p['weight_grams']??null),'stock_quantity'=>$variantId>0?(float)$v['stock_quantity']:(float)$p['stock_quantity']];
            }
            $shipping = $this->shippingCents($pdo, strtoupper($input['country_code']), $input['division'], $input['district'], $input['city'], $input['area']);
            $pricing = $this->calculateWithPDO($pdo, $locked, $input['coupon_code'], $shipping);
            $shipping = (int)$pricing['shipping_cents'];
            $orderNumber = 'FS'.gmdate('ymd').strtoupper(bin2hex(random_bytes(10)));
            $userId=(int)($_SESSION['auth_user_id'] ?? 0); if ($userId<1) $userId=null;
            $pdo->prepare("INSERT INTO favorite_shop_orders (order_number,user_id,phone,status,payment_method,payment_status,currency,subtotal_cents,discount_cents,shipping_cents,tax_cents,total_cents,customer_note,coupon_code_snapshot,discount_details_json,shipping_zone_snapshot) VALUES (?,?,?,'pending',?,'unpaid','BDT',?,?,?,?,?,?,?, ?,?)")
                ->execute([$orderNumber,$userId,$input['phone'],$input['payment_method'],$pricing['subtotal_cents'],$pricing['discount_cents'],$shipping,$pricing['tax_cents'],$pricing['total_cents'],$input['customer_note'] ?: null,$pricing['coupon_code'],$pricing['details_json'],$pricing['zone']]);
            $orderId=(int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO favorite_shop_order_addresses (order_id,address_type,recipient_name,phone,address_line1,address_line2,area,city,district,division,postal_code,country_code) VALUES (?,'shipping',?,?,?,?,?,?,?,?,?,?)")
                ->execute([$orderId,$input['recipient_name'],$input['phone'],$input['address_line1'],$input['address_line2'] ?: null,$input['area'] ?: null,$input['city'],$input['district'] ?: $input['city'],$input['division'] ?: null,$input['postal_code'] ?: null,$input['country_code']]);
            foreach ($pricing['items'] as $item) {
                $pdo->prepare("INSERT INTO favorite_shop_order_items (order_id,product_id,variant_id,sku_snapshot,name_snapshot,variant_snapshot_json,unit_price_cents,quantity,line_total_cents,stock_managed_snapshot,stock_reserved_quantity,unit_snapshot,unit_quantity_snapshot,unit_label_snapshot,shipping_weight_grams_snapshot) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$orderId,$item['product_id'],$item['variant_id']??null,$item['sku'],$item['name'],$item['variant_snapshot_json']??null,$item['unit_price_cents'],$item['quantity'],(int)round((float)$item['quantity']*(int)$item['unit_price_cents'])-(int)($item['offer_discount_cents'] ?? 0),$item['manage_stock'],$item['manage_stock']===1?min((float)($item['stock_quantity']??0),(float)$item['quantity']):0,$item['unit_type'] ?? 'piece',$item['unit_quantity'] ?? 1,$item['unit_label'] ?? null,$item['weight_grams'] ?? null]);
                if ($item['manage_stock']===1) {
                    if(!empty($item['variant_id'])){
                        $u=$pdo->prepare("UPDATE favorite_shop_product_variants SET stock_status=CASE WHEN GREATEST(0,stock_quantity-?)<=0 THEN CASE WHEN allow_backorder=1 THEN 'on_backorder' ELSE 'out_of_stock' END WHEN GREATEST(0,stock_quantity-?)<=low_stock_threshold THEN 'low_stock' ELSE 'in_stock' END, stock_quantity=GREATEST(0,stock_quantity-?) WHERE id=? AND product_id=? AND (allow_backorder=1 OR stock_quantity>=?)");
                        $u->execute([$item['quantity'],$item['quantity'],$item['quantity'],$item['variant_id'],$item['product_id'],$item['quantity']]);
                        if($u->rowCount()!==1&&!((int)($item['allow_backorder']??0)===1&&(float)($item['stock_quantity']??0)<=0))throw new \RuntimeException('Variant stock changed during checkout; please retry.');
                        $after=$pdo->prepare("SELECT stock_quantity FROM favorite_shop_product_variants WHERE id=?");$after->execute([$item['variant_id']]);$qtyAfter=(float)$after->fetchColumn();
                    }else{
                        $u=$pdo->prepare("UPDATE favorite_shop_products SET stock_status=CASE WHEN GREATEST(0,stock_quantity-?)<=0 THEN CASE WHEN allow_backorder=1 THEN 'on_backorder' ELSE 'out_of_stock' END WHEN GREATEST(0,stock_quantity-?)<=low_stock_threshold THEN 'low_stock' ELSE 'in_stock' END, stock_quantity=GREATEST(0,stock_quantity-?) WHERE id=? AND (allow_backorder=1 OR stock_quantity>=?)");
                        $u->execute([$item['quantity'],$item['quantity'],$item['quantity'],$item['product_id'],$item['quantity']]);
                        if($u->rowCount()!==1&&!((int)($item['allow_backorder']??0)===1&&(float)($item['stock_quantity']??0)<=0))throw new \RuntimeException('Stock changed during checkout; please retry.');
                        $after=$pdo->prepare("SELECT stock_quantity FROM favorite_shop_products WHERE id=?");$after->execute([$item['product_id']]);$qtyAfter=(float)$after->fetchColumn();
                    }
                    $pdo->prepare("INSERT INTO favorite_shop_inventory_movements (product_id,variant_id,movement_type,quantity_delta,quantity_after,reference_type,reference_id,note,actor_user_id) VALUES (?,?, 'order',?,?, 'order',?,'Stock reserved at checkout',?)")
                        ->execute([$item['product_id'],$item['variant_id']??null,-min((float)($item['stock_quantity']??0),(float)$item['quantity']),$qtyAfter,$orderId,$userId]);
                }
            }
            $offerUsage=[];
            foreach(($pricing['discounts']['applied'] ?? []) as $entry){$oid=(int)($entry['offer_id']??0);if($oid>0)$offerUsage[$oid]=($offerUsage[$oid]??0)+(int)($entry['discount_cents']??0);}
            $customerKey=$userId?'user:'.$userId:'guest:'.hash('sha256',$input['phone']);
            foreach($offerUsage as $offerId=>$offerDiscount){
                $u=$pdo->prepare("UPDATE favorite_shop_offers SET usage_count=usage_count+1 WHERE id=? AND status IN ('scheduled','active') AND starts_at<=UTC_TIMESTAMP() AND ends_at>UTC_TIMESTAMP() AND (usage_limit IS NULL OR usage_count<usage_limit)");
                $u->execute([(int)$offerId]);if($u->rowCount()!==1)throw new \RuntimeException('An offer usage limit was reached. Please retry checkout.');
                $pdo->prepare('INSERT INTO favorite_shop_offer_redemptions (offer_id,order_id,customer_key,discount_cents) VALUES (?,?,?,?)')->execute([(int)$offerId,$orderId,$customerKey,$offerDiscount]);
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
            if ($input['payment_method'] === 'favorite_pay') {
                $_SESSION['flash_success'] = 'Order created. Complete your prepaid payment to confirm it.';
                return Response::redirect('/shop/pay/'.$orderNumber);
            }
            $_SESSION['flash_success']='Order placed. Pay cash on delivery; payment remains unpaid until collected.';
            return Response::redirect('/shop/order/'.$orderNumber);
        } catch(Throwable $e) {
            if($pdo->inTransaction()) $pdo->rollBack();
            error_log('[Favorite Shop checkout] Order placement failed: '.$e->getMessage());
            return $this->flashRedirect('/shop/checkout', $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Could not place the order. Your cart and form were preserved; please try again.');
        }
    }

    public function payment(Request $request, string $orderNumber): Response|string
    {
        $pdo = $this->db();
        $q = $pdo->prepare('SELECT * FROM favorite_shop_orders WHERE order_number=? LIMIT 1');
        $q->execute([$orderNumber]);
        $order = $q->fetch(\PDO::FETCH_ASSOC);
        if (!$order) return Response::make('<h1>Order not found</h1>', 404);
        if ((int)($order['user_id'] ?? 0) > 0 && (int)($_SESSION['auth_user_id'] ?? 0) !== (int)$order['user_id']) {
            return Response::make('<h1>403 Access denied</h1>', 403);
        }
        if (in_array((string)$order['status'], ['cancelled','returned'], true)) {
            return $this->shell('Payment unavailable', '<p class="notice">This order is cancelled or returned. Please contact the store before attempting payment.</p>');
        }
        if ((string)$order['payment_status'] === 'paid') {
            return Response::redirect('/shop/order/'.$orderNumber);
        }
        if (!$this->app->has(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class)) {
            return $this->shell('Prepaid payment unavailable', '<p class="notice">Favorite Pay is not active. Your order has been created, but prepaid payment is unavailable. Please contact the store.</p><a href="/shop/order/'.self::e($orderNumber).'">View order</a>');
        }
        $payments = $this->app->make(\FavoriteCMS\Pay\Contracts\PaymentServiceInterface::class);
        // bKash returns here; execute the provider API before marking the order paid.
        if ((string)($order['payment_method'] ?? '') === 'bkash_direct'
            && (string)$request->get('status', '') !== ''
            && (string)$request->get('paymentID', '') !== ''
            && method_exists($payments, 'getAttemptsForTransaction')
            && method_exists($payments, 'markAttemptSuccessfulViaWebhook')
            && method_exists($payments, 'markAttemptFailedViaWebhook')) {
            try {
                $attempts = $payments->getAttemptsForTransaction((string)($order['payment_intent_id'] ?? ''));
                $attempt = null;
                foreach ($attempts as $candidate) {
                    if ($candidate->getGatewayId() === 'bkash_direct') { $attempt = $candidate; break; }
                }
                if ($attempt && !in_array($attempt->getStatus()->value, ['succeeded','failed','cancelled'], true)) {
                    $gateway = $this->app->make(\FavoriteCMS\Pay\Services\GatewayRegistry::class)->get('bkash_direct');
                    if (method_exists($gateway, 'executeCallback')) {
                        $verifiedAttempt = $gateway->executeCallback($attempt, $request->all());
                        if ($verifiedAttempt->getStatus()->value === 'succeeded') {
                            $payments->markAttemptSuccessfulViaWebhook($attempt->getId(), $verifiedAttempt->getTransactionReference(), $verifiedAttempt->getMetadata());
                        } else {
                            $payments->markAttemptFailedViaWebhook($attempt->getId(), $verifiedAttempt->getErrorMessage() ?? 'bKash payment was not completed.', $verifiedAttempt->getTransactionReference(), $verifiedAttempt->getMetadata());
                        }
                    }
                }
                return Response::redirect('/shop/order/'.$orderNumber);
            } catch (\Throwable $e) {
                error_log('[Favorite Shop bKash return] '.$e->getMessage());
                return $this->flashRedirect('/shop/pay/'.$orderNumber, 'Could not verify the bKash return. Check the order status or contact the store.');
            }
        }

        try {
            $methods = $payments->getAvailablePaymentMethods('BDT');
        } catch (\Throwable $e) {
            error_log('[Favorite Shop payment methods] '.$e->getMessage());
            $methods = [];
        }
        if ($request->method() === 'POST') {
            if (!$this->validCsrf($request)) return $this->flashRedirect('/shop/pay/'.$orderNumber, 'Session expired. Please try again.');
            $gatewayId = trim((string)$request->post('gateway_id', ''));
            $method = null;
            foreach ($methods as $candidate) if (($candidate['id'] ?? '') === $gatewayId) { $method = $candidate; break; }
            if (!$method) return $this->flashRedirect('/shop/pay/'.$orderNumber, 'That payment method is not currently enabled or configured.');
            try {
                $intentId = trim((string)($order['payment_intent_id'] ?? ''));
                $intent = $intentId !== '' ? $payments->getIntent($intentId) : null;
                if (!$intent || $intent->getGatewayId() !== $gatewayId || in_array($intent->getStatus()->value, ['failed','cancelled'], true)) {
                    $intent = $payments->createIntent('favorite-shop', $orderNumber, new \FavoriteCMS\Pay\Domain\Money((int)$order['total_cents'], 'BDT'), [
                        'gateway_id' => $gatewayId,
                        'customer_id' => (int)($order['user_id'] ?? 0) > 0 ? (int)$order['user_id'] : null,
                        'metadata' => ['shop_order_id' => (int)$order['id'], 'order_number' => $orderNumber],
                    ]);
                    $intentId = $intent->getId();
                    $pdo->prepare("UPDATE favorite_shop_orders SET payment_intent_id=?,payment_method=? WHERE id=? AND payment_status<>'paid'")->execute([$intentId,$gatewayId,(int)$order['id']]);
                    $order['payment_intent_id'] = $intentId;
                    $order['payment_method'] = $gatewayId;
                }
                if (!empty($method['is_manual'])) {
                    $trx = trim((string)$request->post('transaction_reference', ''));
                    $sender = trim((string)$request->post('sender_account', ''));
                    if ($trx === '' || strlen($trx) > 190 || $sender === '' || strlen($sender) > 100) {
                        return $this->flashRedirect('/shop/pay/'.$orderNumber, 'Enter your sender account/number and transaction reference (TrxID).');
                    }
                    $payments->submitManualVerification($intentId, $gatewayId, $trx, [
                        'sender_account' => $sender,
                        'notes' => substr(trim((string)$request->post('notes', '')), 0, 1000),
                        'idempotency_key' => 'shop-'.$orderNumber.'-'.hash('sha256', strtolower($gatewayId.':'.$trx)),
                        'metadata' => ['shop_order_number' => $orderNumber],
                    ]);
                    $pdo->prepare("UPDATE favorite_shop_orders SET payment_intent_id=?,payment_method=?,payment_status='awaiting_verification' WHERE id=? AND payment_status<>'paid'")->execute([$intentId,$gatewayId,(int)$order['id']]);
                    $_SESSION['flash_success'] = 'Payment details submitted. The store will verify the transaction before confirming your order.';
                    return Response::redirect('/shop/order/'.$orderNumber);
                }
                $pdo->prepare("UPDATE favorite_shop_orders SET payment_intent_id=?,payment_method=?,payment_status='pending' WHERE id=? AND payment_status<>'paid'")->execute([$intentId,$gatewayId,(int)$order['id']]);
                $attempt = $payments->initiatePayment($intentId, $gatewayId, ['return_url' => '/shop/order/'.$orderNumber, 'cancel_url' => '/shop/pay/'.$orderNumber]);
                $metadata = $attempt->getMetadata();
                $target = (string)($metadata['checkout_url'] ?? $metadata['universal_url'] ?? $metadata['bkash_url'] ?? '');
                if ($target !== '' && filter_var($target, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($target, PHP_URL_SCHEME)), ['https','http'], true)) {
                    return Response::redirect($target);
                }
                return $this->shell('Payment initiated', '<p class="notice">The payment provider accepted the initiation request but did not return a redirect URL. Check the configured gateway settings or contact the store.</p><a href="/shop/order/'.self::e($orderNumber).'">View order</a>');
            } catch (\Throwable $e) {
                error_log('[Favorite Shop prepaid checkout] '.$e->getMessage());
                $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='failed' WHERE id=? AND payment_status<>'paid'")->execute([(int)$order['id']]);
                return $this->flashRedirect('/shop/pay/'.$orderNumber, 'Could not start this payment. Check the gateway configuration and try again.');
            }
        }
        $body = $this->flashMessages();
        if (!$methods) {
            $body .= '<p class="notice">No prepaid methods are currently enabled and configured in Favorite Pay. You can return to the order or contact the store.</p>';
        } else {
            $body .= '<p>Order total: <strong>'.self::money((int)$order['total_cents']).'</strong></p>';
            $body .= '<form method="post" action="/shop/pay/'.self::e($orderNumber).'">'.$this->csrf();
            $body .= '<label>Payment gateway<select name="gateway_id" required>';
            foreach ($methods as $method) {
                $id = (string)($method['id'] ?? '');
                $body .= '<option value="'.self::e($id).'">'.self::e($method['title'] ?? $id).'</option>';
            }
            $body .= '</select></label>';
            foreach ($methods as $method) {
                if (empty($method['is_manual'])) continue;
                $instructions = is_array($method['instructions'] ?? null) ? $method['instructions'] : [];
                $body .= '<details class="favorite-shop-card"><summary>'.self::e($method['title'] ?? $method['id'] ?? 'Manual payment').' payment instructions</summary><div><h2>'.self::e($method['title'] ?? $method['id'] ?? 'Manual payment').'</h2>';
                foreach (['account_name'=>'Account name','account_number'=>'Account / number','account_type'=>'Account type','bank_name'=>'Bank','branch_name'=>'Branch','routing_no'=>'Routing number','instructions'=>'Instructions','reference_instructions'=>'Transaction reference','proof_requirements'=>'Required details'] as $key=>$label) {
                    if (!empty($instructions[$key])) $body .= '<p><strong>'.self::e($label).':</strong> '.self::e($instructions[$key]).'</p>';
                }
                $body .= '</div></details>';
            }
            $body .= '<label>Sender account / phone (manual methods)<input name="sender_account" maxlength="100" autocomplete="tel"></label>';
            $body .= '<label>Transaction reference / TrxID (manual methods)<input name="transaction_reference" maxlength="190"></label>';
            $body .= '<label>Notes (optional)<textarea name="notes" maxlength="1000"></textarea></label>';
            $body .= '<p class="notice">Manual payments remain unconfirmed until a store operator verifies the incoming funds. Automatic gateways depend on provider confirmation/webhooks.</p><button type="submit">Continue to payment</button></form>';
        }
        return $this->shell('Pay for order '.$orderNumber, $body);
    }

    public function order(Request $request, string $orderNumber): Response|string
    {
        $q=$this->db()->prepare("SELECT o.*,a.recipient_name,a.address_line1,a.area,a.city,a.country_code FROM favorite_shop_orders o LEFT JOIN favorite_shop_order_addresses a ON a.order_id=o.id AND a.address_type='shipping' WHERE o.order_number=?");
        $q->execute([$orderNumber]);$o=$q->fetch(\PDO::FETCH_ASSOC);
        if(!$o)return Response::make('<h1>Order not found</h1>',404);
        if((int)($o['user_id'] ?? 0)>0 && (int)($_SESSION['auth_user_id'] ?? 0)!==(int)$o['user_id'])return Response::make('<h1>403 Access denied</h1>',403);
        $paymentLabel = (string)($o['payment_method'] ?? '') === 'cash_on_delivery' ? 'Cash on Delivery' : (string)($o['payment_method'] ?? 'Prepaid');
        $paymentAction = in_array((string)$o['payment_status'], ['unpaid','failed','pending'], true) && (string)($o['payment_method'] ?? '') !== 'cash_on_delivery'
            ? '<p><a class="btn" href="/shop/pay/'.self::e($orderNumber).'">Continue payment / retry</a></p>' : '';
        return $this->shell('Order '.$orderNumber,'<h1>Thank you for your order</h1><p>Order: '.self::e($orderNumber).'</p><p>Status: '.self::e($o['status']).'</p><p>Payment: '.self::e($o['payment_status']).' — '.self::e($paymentLabel).'</p><p>Total: '.self::money((int)$o['total_cents']).'</p><p>Deliver to: '.self::e($o['recipient_name'] ?? '').', '.self::e($o['address_line1'] ?? '').', '.self::e($o['city'] ?? '').'</p>'.$paymentAction.'<a href="/shop">Continue shopping</a>');
    }

    private function calculate(array $items, string $couponCode, int $shipping): array
    {
        return $this->calculateWithPDO($this->db(),$items,$couponCode,$shipping);
    }

    private function calculateWithPDO(\PDO $pdo, array $items, string $couponCode, int $shipping): array
    {
        $subtotal=0;
        foreach($items as $i)$subtotal+=(int)round((int)$i['unit_price_cents']*(float)$i['quantity']);
        $offerRows=$pdo->query("SELECT * FROM favorite_shop_offers WHERE status IN ('scheduled','active') AND starts_at<=UTC_TIMESTAMP() AND ends_at>UTC_TIMESTAMP() ORDER BY priority DESC")->fetchAll(\PDO::FETCH_ASSOC);
        $offers=PromotionEngine::applyOffers($items,$offerRows,$shipping);
        $offerDiscount=(int)$offers['discount_cents'];
        $discount=$offerDiscount;
        $shipDiscount=(int)$offers['shipping_discount_cents'];
        $couponId=null;$couponDiscount=0;$couponCodeSaved=null;
        if($couponCode!==''){
            $couponSql="SELECT * FROM favorite_shop_coupons WHERE code=? AND status='active' LIMIT 1".($pdo->inTransaction()?' FOR UPDATE':'');
            $q=$pdo->prepare($couponSql);
            $q->execute([strtoupper(trim($couponCode))]);$coupon=$q->fetch(\PDO::FETCH_ASSOC);
            if(!$coupon)throw new \InvalidArgumentException('Coupon code is invalid or inactive.');
            $uses=(int)$coupon['usage_count'];
            $customerKey=isset($_SESSION['auth_user_id'])?'user:'.(int)$_SESSION['auth_user_id']:'guest:'.hash('sha256',(string)($_SESSION['favorite_shop_checkout']['phone'] ?? ''));
            $cq=$pdo->prepare('SELECT COUNT(*) FROM favorite_shop_coupon_redemptions WHERE coupon_id=? AND customer_key=?');
            $cq->execute([(int)$coupon['id'],$customerKey]);$customerUses=(int)$cq->fetchColumn();
            if(CouponPolicy::state($coupon,$uses,$customerUses)!=='active')throw new \InvalidArgumentException('Coupon is not active or its usage limit has been reached.');
            if($subtotal<(int)$coupon['min_subtotal_cents'])throw new \InvalidArgumentException('Cart subtotal does not meet the coupon minimum.');
            $couponDiscount=PromotionEngine::couponDiscount($coupon,$offers['items'],$shipping);
            if($couponDiscount<=0)throw new \InvalidArgumentException('This coupon does not apply to the current cart.');
            $couponApplied=false;$offersAllowStack=true;foreach(($offers['applied']??[]) as $offerEntry)if(empty($offerEntry['stackable'])){$offersAllowStack=false;break;}$canStack=!empty($coupon['stackable'])&&$offersAllowStack;
            if((string)$coupon['discount_type']==='free_shipping'){
                if($canStack){$shipDiscount=max($shipDiscount,$couponDiscount);$couponApplied=true;}
                elseif($couponDiscount>$offerDiscount){$this->clearLineOffers($offers);$discount=0;$offerDiscount=0;$shipDiscount=$couponDiscount;$couponApplied=true;}
                else $couponDiscount=0;
            } else {
                if($canStack){$discount=min($subtotal,$offerDiscount+$couponDiscount);$couponApplied=true;}
                elseif($couponDiscount>$offerDiscount){$this->clearLineOffers($offers);$offerDiscount=0;$discount=min($subtotal,$couponDiscount);$couponApplied=true;}
                else $couponDiscount=0;
            }
            if($couponApplied){
                $couponId=(int)$coupon['id'];$couponCodeSaved=$coupon['code'];
            }
        }
        $discount=min($subtotal,max(0,$discount));
        $total=max(0,$subtotal-$discount+max(0,$shipping-$shipDiscount));
        return [
            'items'=>$offers['items'],'subtotal_cents'=>$subtotal,'discount_cents'=>$discount,
            'coupon_discount_cents'=>$couponDiscount,'shipping_discount_cents'=>$shipDiscount,
            'shipping_cents'=>max(0,$shipping-$shipDiscount),'tax_cents'=>0,'total_cents'=>$total,
            'coupon_id'=>$couponId,'coupon_code'=>$couponCodeSaved,
            'details_json'=>json_encode(['offers'=>$offers['applied'],'coupon_discount_cents'=>$couponDiscount,'shipping_discount_cents'=>$shipDiscount]),
            'zone'=>(string)($_SESSION['favorite_shop_shipping_zone'] ?? 'fallback'),'discounts'=>$offers
        ];
    }

    private function clearLineOffers(array &$offers): void
    {
        foreach($offers['items'] as &$item){$item['offer_discount_cents']=0;$item['offer_id']=null;}
        unset($item);
        $offers['discount_cents']=0;
        $offers['applied']=array_values(array_filter($offers['applied'],static fn($x)=>($x['type']??'')==='free_shipping'));
    }

    private function cartItems(): array
    {
        $cart=$this->sessionCart();$items=[];$pdo=$this->db();
        foreach($cart as $key=>$rawQty){
            if(!preg_match('/^(\d+)(?::(\d+))?$/',(string)$key,$m))continue;
            $productId=(int)$m[1];$variantId=isset($m[2])?(int)$m[2]:0;
            $q=$pdo->prepare("SELECT * FROM favorite_shop_products WHERE id=? AND status='published'");$q->execute([$productId]);$p=$q->fetch(\PDO::FETCH_ASSOC);if(!$p)continue;
            $v=null;
            if(($p['product_type']??'simple')==='variable'){
                if($variantId<1)continue;$vq=$pdo->prepare("SELECT * FROM favorite_shop_product_variants WHERE id=? AND product_id=? AND status='active'");$vq->execute([$variantId,$productId]);$v=$vq->fetch(\PDO::FETCH_ASSOC);if(!$v)continue;
            }
            $unit=(string)(($v['unit_type']??null)?:($p['unit_type']??'piece'));
            try{$qty=Quantity::forUnit($rawQty,$unit);}catch(\Throwable){continue;}
            $price=$v&&$v['price_cents']!==null?(int)$v['price_cents']:(int)$p['price_cents'];
            $sale=$v&&$v['sale_price_cents']!==null?$v['sale_price_cents']:($p['sale_price_cents']??null);
            if($sale!==null&&(int)$sale>0)$price=min($price,(int)$sale);
            if($v){$decodedOptions=json_decode((string)$v['option_values_json'],true);$options=is_array($decodedOptions)?$decodedOptions:[];}else{$options=[];}
            $displayName=(string)$p['name'];
            if($options){$parts=[];foreach($options as $optionName=>$optionValue)$parts[]=(string)$optionName.': '.(string)$optionValue;$displayName.=' — '.implode(' / ',$parts);}
            $variantSku=$v['sku']??null;if(!$variantSku)$variantSku=$p['sku'];
            $variantUnitQuantity=$v['unit_quantity']??null;if(!$variantUnitQuantity)$variantUnitQuantity=$p['unit_quantity']??1;
            $variantUnitLabel=$v['unit_label']??null;if(!$variantUnitLabel)$variantUnitLabel=$p['unit_label']??null;
            $weight=$v['weight_grams']??null;if($weight===null)$weight=$p['weight_grams']??null;
            $items[]=[
                'cart_key'=>(string)$key,
                'product_id'=>$productId,
                'variant_id'=>$variantId>0?$variantId:null,
                'name'=>$displayName,
                'sku'=>$variantSku,
                'variant_snapshot_json'=>$options?json_encode($options,JSON_UNESCAPED_UNICODE):null,
                'quantity'=>$qty,
                'unit_price_cents'=>$price,
                'category_ids'=>$this->categoryIds($pdo,$productId),
                'labels'=>json_decode((string)($p['labels_json']??'[]'),true)?:[],
                'unit_type'=>$unit,
                'unit_quantity'=>$variantUnitQuantity,
                'unit_label'=>$variantUnitLabel,
                'weight_grams'=>$weight,
                'manage_stock'=>$variantId>0?1:(int)$p['manage_stock'],
                'stock_quantity'=>$variantId>0?(float)$v['stock_quantity']:(float)$p['stock_quantity'],
                'allow_backorder'=>$variantId>0?0:(int)$p['allow_backorder']
            ];
        }
        return $items;
    }
    private function categoryIds(\PDO $pdo,int $id):array{$q=$pdo->prepare('SELECT category_id FROM favorite_shop_product_category_map WHERE product_id=?');$q->execute([$id]);return array_map('intval',$q->fetchAll(\PDO::FETCH_COLUMN));}
    private function shippingCents(\PDO $pdo,string $country,string $division,string $district,string $city,string $area):int{
        $q=$pdo->prepare("SELECT * FROM favorite_shop_delivery_zones WHERE country_code=? AND enabled=1 ORDER BY priority DESC,id DESC");$q->execute([$country]);$zones=$q->fetchAll(\PDO::FETCH_ASSOC);$best=null;$bestSpecificity=-1;
        foreach($zones as $z){$level=(string)$z['region_level'];$value=strtolower(trim((string)($z['region_value']??'')));$specificity=0;
            if($level==='area'){if($value===''||$value!==strtolower(trim($area)))continue;$specificity=6;}
            elseif($level==='city'){if($value!==strtolower(trim($city)))continue;$specificity=5;}
            elseif($level==='district'){if($value!==strtolower(trim($district)))continue;$specificity=4;}
            elseif($level==='division'){if($value!==strtolower(trim($division)))continue;$specificity=3;}
            elseif($level==='country')$specificity=2;
            elseif($level==='default')$specificity=1;
            else continue;
            if($specificity>$bestSpecificity||($specificity===$bestSpecificity&&(int)$z['priority']>(int)($best['priority']??PHP_INT_MIN))){$best=$z;$bestSpecificity=$specificity;}
        }
        if(!$best){foreach($zones as $z)if((int)($z['is_default']??0)===1){$best=$z;break;}}
        if($best){$_SESSION['favorite_shop_shipping_zone']=(string)$best['name'];return max(0,(int)$best['rate_cents']);}
        $q=$pdo->prepare("SELECT setting_value FROM favorite_shop_settings WHERE setting_key=?");$q->execute([$country==='BD'?'shipping_bd_default_cents':'shipping_intl_default_cents']);$v=$q->fetchColumn();$_SESSION['favorite_shop_shipping_zone']='fallback';return $v===false?0:max(0,(int)$v);
    }
    private function sessionCart():array{$c=$_SESSION['favorite_shop_cart']??[];return is_array($c)?$c:[];}
    private function validCsrf(Request $r):bool{$submitted=(string)$r->post('_token','');$session=(string)($_SESSION['_token']??'');return $submitted!==''&&$session!==''&&hash_equals($session,$submitted);}
    private function csrf():string{if(empty($_SESSION['_token']))$_SESSION['_token']=bin2hex(random_bytes(32));return '<input type="hidden" name="_token" value="'.self::e($_SESSION['_token']).'">';}
    private function flashRedirect(string $url,string $message):Response{$_SESSION['flash_error']=$message;return Response::redirect($url);}
    private function flashMessages():string{$out='';foreach(['flash_error','flash_success'] as $k){if(isset($_SESSION[$k])){$out.='<p class="notice">'.self::e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}}return $out;}
    /**
     * Render storefront content inside the active theme's real layout.
     * The theme owns document markup, header/footer, navigation, assets and appearance mode.
     */
    private function shell(string $title,string $body):string
    {
        $theme = 'default';
        try {
            $configured = \FavoriteCMS\Models\Setting::get('theme', 'active_theme', 'default');
            if (is_string($configured) && preg_match('/^[a-zA-Z0-9_-]+$/', $configured) === 1) {
                $theme = $configured;
            }
        } catch (\Throwable) {
            // Keep the CMS default theme as a safe fallback during early bootstrap.
        }

        $themesRoot = rtrim((string)APP_ROOT, '/\\\\') . '/themes';
        $themeDir = $themesRoot . '/' . $theme;
        if (!is_file($themeDir . '/header.php') || !is_file($themeDir . '/footer.php')) {
            $theme = 'default';
            $themeDir = $themesRoot . '/default';
        }
        if (!is_file($themeDir . '/header.php') || !is_file($themeDir . '/footer.php')) {
            throw new \RuntimeException('The active theme does not provide a compatible header.php and footer.php.');
        }

        // Variables consumed by standard Favorite CMS themes.
        $siteTitle = \FavoriteCMS\Models\Setting::get('general', 'site_name', 'Favorite CMS');
        $siteTagline = \FavoriteCMS\Models\Setting::get('general', 'site_description', '');
        $metaTitle = $title . ' — ' . $siteTitle;
        $metaDescription = $title . ' on ' . $siteTitle;
        $bodyClass = 'favorite-shop-page';
        $fcdHasSidebar = false;
        $shopStyles = '<style id="favorite-shop-theme-adapter">
.favorite-shop-page .favorite-shop-main{width:100%;min-width:0}
.favorite-shop-page .favorite-shop-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:1rem}
.favorite-shop-page .favorite-shop-card{min-width:0;border:1px solid color-mix(in srgb,currentColor 18%,transparent);border-radius:var(--radius-md,12px);padding:1rem;margin:1rem 0;background:transparent;color:inherit}
.favorite-shop-page .favorite-shop-card img{display:block;max-width:100%;max-height:220px;object-fit:contain;margin-inline:auto}
.favorite-shop-page .favorite-shop-main label{display:block;margin:1rem 0}
.favorite-shop-page .favorite-shop-main input,.favorite-shop-page .favorite-shop-main textarea,.favorite-shop-page .favorite-shop-main select{display:block;width:100%;max-width:100%;box-sizing:border-box;padding:.7rem;border:1px solid color-mix(in srgb,currentColor 28%,transparent);border-radius:var(--radius-sm,7px);margin-top:.3rem;background:transparent;color:inherit;font:inherit}
.favorite-shop-page .favorite-shop-main button,.favorite-shop-page .favorite-shop-main .btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;padding:.65rem 1rem;border:1px solid var(--brand-accent,currentColor);border-radius:var(--radius-sm,7px);background:var(--brand-accent,transparent);color:var(--button-text-color,inherit);font:inherit;cursor:pointer;text-decoration:none}
.favorite-shop-page .favorite-shop-main a{color:var(--brand-accent,inherit)}
.favorite-shop-page .favorite-shop-main .notice{padding:.8rem 1rem;border:1px solid color-mix(in srgb,currentColor 18%,transparent);border-radius:8px;background:var(--surface-muted,transparent);color:inherit}
@media(max-width:600px){.favorite-shop-page .favorite-shop-main{padding-inline:0}.favorite-shop-page .favorite-shop-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,160px),1fr))}}
</style>';

        ob_start();
        require $themeDir . '/header.php';
        echo $shopStyles;
        echo '<main id="main-content" class="site-main favorite-shop-main" tabindex="-1"><div class="favorite-shop-content"><h1>' . self::e($title) . '</h1>';
        echo str_replace('class="grid"', 'class="favorite-shop-grid"', str_replace('class="card"', 'class="favorite-shop-card"', $body));
        echo '</div></main>';
        $sidebar = $themeDir . '/sidebar.php';
        if (is_file($sidebar)) {
            require $sidebar;
        }
        require $themeDir . '/footer.php';
        return (string)ob_get_clean();
    }
    private static function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private static function money(int $cents):string{return '৳'.number_format($cents/100,2);}
}
