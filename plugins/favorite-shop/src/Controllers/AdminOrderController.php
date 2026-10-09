<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Shop\Domain\CodLifecycle;
use Throwable;

final class AdminOrderController
{
    public function __construct(private Application $app) {}
    private function db(): \PDO {
        $db=$this->app->make(Database::class);
        if(!method_exists($db,'getConnection')||!($pdo=$db->getConnection()) instanceof \PDO)throw new \RuntimeException('Favorite Shop database connection is unavailable.');
        return $pdo;
    }
    public function handle(Request $r): Response|string {
        if((int)($_SESSION['auth_user_id']??0)<=0&&!isset($GLOBALS['_test_current_user']))return Response::redirect('/admin/login');
        if(function_exists('current_user_can')&&!current_user_can('manage_options'))return Response::make('<h1>403 Access Denied</h1>',403);
        if($r->method()==='POST')return $this->post($r);
        $action=(string)$r->get('action','index');$id=(int)$r->get('id',0);
        return $action==='view'&&$id>0?$this->view($id):$this->index($r);
    }
    private function post(Request $r):Response {
        $token=(string)$r->post('_token','');
        if($token===''||!hash_equals((string)($_SESSION['_token']??''),$token)){$_SESSION['flash_error']='Security token expired.';return Response::redirect('/admin/page/favorite-shop-orders');}
        $id=(int)$r->post('id',0);$action=(string)$r->post('action','');$pdo=$this->db();
        try {
            $pdo->beginTransaction();
            $q=$pdo->prepare('SELECT * FROM favorite_shop_orders WHERE id=? FOR UPDATE');$q->execute([$id]);$order=$q->fetch(\PDO::FETCH_ASSOC);
            if(!$order)throw new \RuntimeException('Order not found.');
            $old=(string)$order['status'];$new=$old;
            if($action==='update_status') {
                $new=(string)$r->post('status','');
                if(!in_array($new,['pending','processing','shipped','delivered','cancelled','returned'],true))throw new \InvalidArgumentException('Invalid order status.');
                if(in_array($old,['cancelled','returned'],true)&&$new!==$old)throw new \InvalidArgumentException('Cancelled or returned orders cannot be reopened here.');
                if(in_array($new,['cancelled','returned'],true)&&!in_array($old,['cancelled','returned'],true))$this->restoreStock($pdo,$order);
                $pdo->prepare('UPDATE favorite_shop_orders SET status=? WHERE id=?')->execute([$new,$id]);
            } elseif($action==='record_cod') {
                $collected=filter_var($r->post('collected_cents',''),FILTER_VALIDATE_INT);
                if($collected===false||$collected<0)throw new \InvalidArgumentException('Collected amount must be a non-negative integer in minor units.');
                $total=(int)$order['total_cents'];
                if($collected<$total) {
                    $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='unpaid' WHERE id=?")->execute([$id]);
                    $new=$old;
                } else {
                    $pdo->prepare("UPDATE favorite_shop_orders SET payment_status='paid' WHERE id=?")->execute([$id]);
                    $pdo->prepare("UPDATE favorite_shop_shipments SET cod_collected_cents=? WHERE order_id=?")->execute([$collected,$id]);
                }
                $pdo->prepare("INSERT INTO favorite_shop_order_events (order_id,event_key,from_status,to_status,note,actor_user_id) VALUES (?,'cod_collection',?,?,?,?)")
                    ->execute([$id,$order['payment_status'],$collected>=$total?'paid':'unpaid','Collected minor units: '.$collected,(int)($_SESSION['auth_user_id']??0)]);
            } else throw new \InvalidArgumentException('Unknown order action.');
            if($action==='update_status')$pdo->prepare('INSERT INTO favorite_shop_order_events (order_id,event_key,from_status,to_status,note,actor_user_id) VALUES (?,?,?,?,?,?)')
                ->execute([$id,'status_change',$old,$new,trim((string)$r->post('notes','')),(int)($_SESSION['auth_user_id']??0)]);
            $pdo->commit();$_SESSION['flash_success']='Order updated.';
        } catch(Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']=$e instanceof \InvalidArgumentException?$e->getMessage():'Could not update order: '.$e->getMessage();}
        return Response::redirect('/admin/page/favorite-shop-orders?action=view&id='.$id);
    }
    private function restoreStock(\PDO $pdo,array $order):void {
        $q=$pdo->prepare('SELECT product_id,variant_id,quantity FROM favorite_shop_order_items WHERE order_id=?');$q->execute([(int)$order['id']]);
        foreach($q->fetchAll(\PDO::FETCH_ASSOC) as $item) {
            $qty=(float)$item['quantity'];
            if(!empty($item['variant_id'])) {
                $pdo->prepare('UPDATE favorite_shop_product_variants SET stock_quantity=stock_quantity+? WHERE id=?')->execute([$qty,(int)$item['variant_id']]);
                $table='favorite_shop_product_variants';$col='id';$key=(int)$item['variant_id'];
            } elseif(!empty($item['product_id'])) {
                $pdo->prepare('UPDATE favorite_shop_products SET stock_quantity=stock_quantity+? WHERE id=?')->execute([$qty,(int)$item['product_id']]);
                $table='favorite_shop_products';$col='id';$key=(int)$item['product_id'];
            } else continue;
            $q2=$pdo->query("SELECT stock_quantity FROM {$table} WHERE {$col}=".(int)$key);$after=(float)$q2->fetchColumn();
            $pdo->prepare("INSERT INTO favorite_shop_inventory_movements (product_id,variant_id,movement_type,quantity_delta,quantity_after,reference_type,reference_id,note,actor_user_id) VALUES (?,?, 'restore',?,?, 'order',?,'Stock restored on cancel/return',?)")
                ->execute([(int)($item['product_id']??0),$item['variant_id']??null,$qty,$after,(int)$order['id'],(int)($_SESSION['auth_user_id']??0)]);
        }
    }
    private function index(Request $r):string {
        $status=trim((string)$r->get('status',''));$search=trim((string)$r->get('search',''));
        $sql='SELECT o.*,a.recipient_name,a.city FROM favorite_shop_orders o LEFT JOIN favorite_shop_order_addresses a ON a.order_id=o.id AND a.address_type="shipping" WHERE 1=1';$params=[];
        if($status!==''&&in_array($status,['pending','processing','shipped','delivered','cancelled','returned'],true)){$sql.=' AND o.status=?';$params[]=$status;}
        if($search!==''){$sql.=' AND (o.order_number LIKE ? OR o.phone LIKE ? OR a.recipient_name LIKE ?)';array_push($params,'%'.$search.'%','%'.$search.'%','%'.$search.'%');}
        $sql.=' ORDER BY o.placed_at DESC LIMIT 200';$q=$this->db()->prepare($sql);$q->execute($params);$rows=$q->fetchAll(\PDO::FETCH_ASSOC);
        $html='<div style="max-width:1200px;margin:24px auto;padding:16px"><h1>Favorite Shop Orders</h1>'.$this->flashes().'<form><input name="search" placeholder="Order, phone or customer" value="'.self::e($search).'"><select name="status"><option value="">All statuses</option>';
        foreach(['pending','processing','shipped','delivered','cancelled','returned'] as $s)$html.='<option'.($status===$s?' selected':'').' value="'.$s.'">'.ucfirst($s).'</option>';
        $html.='</select><button>Filter</button></form><div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th></th></tr></thead><tbody>';
        foreach($rows as $o)$html.='<tr><td>'.self::e($o['order_number']).'<br>'.self::e($o['placed_at']).'</td><td>'.self::e($o['recipient_name']??'Guest').'<br>'.self::e($o['phone']).'</td><td>'.self::e($o['status']).'</td><td>'.self::e($o['payment_status']).'</td><td>'.self::money((int)$o['total_cents']).'</td><td><a href="/admin/page/favorite-shop-orders?action=view&id='.(int)$o['id'].'">View</a></td></tr>';
        if(!$rows)$html.='<tr><td colspan="6">No orders found.</td></tr>';
        return $html.'</tbody></table></div></div>';
    }
    private function view(int $id):string {
        $pdo=$this->db();$q=$pdo->prepare('SELECT o.*,a.* FROM favorite_shop_orders o LEFT JOIN favorite_shop_order_addresses a ON a.order_id=o.id AND a.address_type="shipping" WHERE o.id=?');$q->execute([$id]);$o=$q->fetch(\PDO::FETCH_ASSOC);
        if(!$o)return '<h1>Order not found</h1>';
        $q=$pdo->prepare('SELECT * FROM favorite_shop_order_items WHERE order_id=?');$q->execute([$id]);$items=$q->fetchAll(\PDO::FETCH_ASSOC);
        $html='<div style="max-width:1100px;margin:24px auto;padding:16px"><h1>Order '.self::e($o['order_number']).'</h1>'.$this->flashes().'<p>'.self::e($o['recipient_name']??'').' · '.self::e($o['phone']).' · '.self::e($o['address_line1']??'').' · '.self::e($o['city']??'').'</p><p>Payment: '.self::e($o['payment_status']).' | Subtotal: '.self::money((int)$o['subtotal_cents']).' | Discount: '.self::money((int)$o['discount_cents']).' | Shipping: '.self::money((int)$o['shipping_cents']).' | Total: '.self::money((int)$o['total_cents']).'</p><table><tr><th>Item</th><th>SKU</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr>';
        foreach($items as $i)$html.='<tr><td>'.self::e($i['name_snapshot']).'</td><td>'.self::e($i['sku_snapshot']).'</td><td>'.self::e($i['quantity']).'</td><td>'.self::money((int)$i['unit_price_cents']).'</td><td>'.self::money((int)$i['line_total_cents']).'</td></tr>';
        $html.='</table><form method="post">'.$this->csrf().'<input type="hidden" name="id" value="'.$id.'"><label>Update order status<select name="status">';
        foreach(['pending','processing','shipped','delivered','cancelled','returned'] as $s)$html.='<option'.($o['status']===$s?' selected':'').' value="'.$s.'">'.ucfirst($s).'</option>';
        $html.='</select></label><label>Internal note<input name="notes" maxlength="1000"></label><button name="action" value="update_status">Save status</button></form><form method="post">'.$this->csrf().'<input type="hidden" name="id" value="'.$id.'"><label>COD collected amount (minor units)<input type="number" min="0" name="collected_cents" required></label><button name="action" value="record_cod">Record COD collection</button></form><p><a href="/admin/page/favorite-shop-orders">Back to orders</a></p></div>';
        return $html;
    }
    private function csrf():string {if(empty($_SESSION['_token']))$_SESSION['_token']=bin2hex(random_bytes(32));return '<input type="hidden" name="_token" value="'.self::e($_SESSION['_token']).'">';}
    private function flashes():string{$s='';foreach(['flash_error','flash_success'] as $k){if(isset($_SESSION[$k])){$s.='<p>'.self::e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}}return $s;}
    private static function e(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
    private static function money(int $c):string{return '৳'.number_format($c/100,2);}
}
