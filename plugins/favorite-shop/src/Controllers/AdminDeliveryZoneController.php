<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use Throwable;

final class AdminDeliveryZoneController
{
    public function __construct(private Application $app) {}
    private function db():\PDO{$db=$this->app->make(Database::class);if(!method_exists($db,'getConnection')||!($pdo=$db->getConnection()) instanceof \PDO)throw new \RuntimeException('Database unavailable.');return $pdo;}
    public function handle(Request $r):Response|string{
        if((int)($_SESSION['auth_user_id']??0)<=0)return Response::redirect('/admin/login');
        if(!function_exists('current_user_can')||!current_user_can('manage_options'))return Response::make('<h1>403 Access Denied</h1>',403);
        if($r->method()==='POST')return $this->save($r);
        return $this->page((int)$r->get('id',0));
    }
    private function save(Request $r):Response{
        if(!hash_equals((string)($_SESSION['_token']??''),(string)$r->post('_token',''))){$_SESSION['flash_error']='Security token expired.';return Response::redirect('/admin/page/favorite-shop-delivery');}
        try{
            $id=(int)$r->post('id',0);$country=strtoupper(trim((string)$r->post('country_code','BD')));$name=trim((string)$r->post('name',''));$level=(string)$r->post('region_level','default');$region=trim((string)$r->post('region_value',''));
            $rate=filter_var($r->post('rate_cents','0'),FILTER_VALIDATE_INT);$priority=filter_var($r->post('priority','0'),FILTER_VALIDATE_INT);
            if(!preg_match('/^[A-Z]{2}$/',$country)||$name===''||strlen($name)>190)throw new \InvalidArgumentException('Enter a valid country code and zone name.');
            if(!in_array($level,['default','country','division','district','city','area'],true))throw new \InvalidArgumentException('Invalid region level.');
            if($level!=='default'&&$region==='')throw new \InvalidArgumentException('Region value is required for this zone.');
            if($rate===false||$rate<0||$priority===false)throw new \InvalidArgumentException('Rate must be a non-negative integer in minor currency units.');
            $enabled=(int)(bool)$r->post('enabled',false);$default=(int)(bool)$r->post('is_default',false);
            $pdo=$this->db();$pdo->beginTransaction();
            if($default)$pdo->prepare('UPDATE favorite_shop_delivery_zones SET is_default=0 WHERE country_code=?')->execute([$country]);
            $data=[$country,$name,$level,$region?:null,$rate,$priority,$default,$enabled];
            if($id>0){$data[]=$id;$pdo->prepare('UPDATE favorite_shop_delivery_zones SET country_code=?,name=?,region_level=?,region_value=?,rate_cents=?,priority=?,is_default=?,enabled=? WHERE id=?')->execute($data);}
            else $pdo->prepare('INSERT INTO favorite_shop_delivery_zones (country_code,name,region_level,region_value,rate_cents,priority,is_default,enabled) VALUES (?,?,?,?,?,?,?,?)')->execute($data);
            $pdo->commit();$_SESSION['flash_success']='Delivery zone saved.';
        }catch(Throwable $e){if(isset($pdo)&&$pdo instanceof \PDO&&$pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']=$e instanceof \InvalidArgumentException?$e->getMessage():'Could not save delivery zone.';}
        return Response::redirect('/admin/page/favorite-shop-delivery');
    }
    private function page(int $id):string{
        $pdo=$this->db();$d=[];if($id>0){$q=$pdo->prepare('SELECT * FROM favorite_shop_delivery_zones WHERE id=?');$q->execute([$id]);$d=$q->fetch(\PDO::FETCH_ASSOC)?:[];}
        $rows=$pdo->query('SELECT * FROM favorite_shop_delivery_zones ORDER BY country_code,priority DESC,id DESC')->fetchAll(\PDO::FETCH_ASSOC);
        if(empty($_SESSION['_token']))$_SESSION['_token']=bin2hex(random_bytes(32));$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$v=static fn($k,$default='')=>$e($d[$k]??$default);
        $html='<div style="max-width:1100px;margin:24px auto;padding:16px"><h1>Favorite Shop Delivery Zones</h1>';
        foreach(['flash_error','flash_success'] as $k)if(isset($_SESSION[$k])){$html.='<p>'.$e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}
        $html.='<form method="post" style="padding:16px;border:1px solid #ddd;border-radius:12px"><input type="hidden" name="_token" value="'.$e($_SESSION['_token']).'"><input type="hidden" name="id" value="'.(int)($d['id']??0).'"><label>Zone name<input name="name" required maxlength="190" value="'.$v('name').'" placeholder="Dhaka city"></label><label>Country ISO code<input name="country_code" required maxlength="2" value="'.$v('country_code','BD').'"></label><label>Region level<select name="region_level">';
        foreach(['default'=>'Fallback/default','country'=>'Country','division'=>'Division/state','district'=>'District','city'=>'City','area'=>'Area'] as $key=>$label)$html.='<option value="'.$key.'"'.($v('region_level','default')===$key?' selected':'').'>'.$label.'</option>';
        $html.='</select></label><label>Region value<input name="region_value" value="'.$v('region_value').'" placeholder="Dhaka"></label><label>Delivery rate (minor units; ৳100 = 10000)<input type="number" min="0" name="rate_cents" value="'.$v('rate_cents','0').'"></label><label>Priority (higher wins on equal specificity)<input type="number" name="priority" value="'.$v('priority','0').'"></label><label><input type="checkbox" name="is_default" value="1"'.(!empty($d['is_default'])?' checked':'').'> Default zone for this country</label><label><input type="checkbox" name="enabled" value="1"'.(!isset($d['enabled'])||!empty($d['enabled'])?' checked':'').'> Enabled</label><button>Save zone</button></form><h2>Configured zones</h2><div style="overflow:auto"><table style="width:100%"><tr><th>Zone</th><th>Match</th><th>Rate</th><th>Priority</th><th>Status</th><th></th></tr>';
        foreach($rows as $z)$html.='<tr><td>'.$e($z['name']).'</td><td>'.$e($z['country_code']).' · '.$e($z['region_level']).' · '.$e($z['region_value']).'</td><td>৳'.number_format((int)$z['rate_cents']/100,2).'</td><td>'.(int)$z['priority'].'</td><td>'.(!empty($z['enabled'])?'Enabled':'Disabled').(!empty($z['is_default'])?' · Default':'').'</td><td><a href="/admin/page/favorite-shop-delivery?id='.(int)$z['id'].'">Edit</a></td></tr>';
        return $html.'</table></div></div>';
    }
}
