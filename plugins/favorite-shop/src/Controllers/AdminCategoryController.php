<?php
declare(strict_types=1);
namespace FavoriteCMS\Shop\Controllers;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use Throwable;

/** Minimal category manager used by products and category-targeted offers. */
final class AdminCategoryController
{
    public function __construct(private Application $app) {}
    public function handle(Request $r): Response|string
    {
        if ((int)($_SESSION['auth_user_id']??0)<=0 && !isset($GLOBALS['_test_current_user'])) return Response::redirect('/admin/login');
        if (function_exists('current_user_can') && !current_user_can('manage_options')) return Response::make('<h1>403 Access Denied</h1>',403);
        if ($r->method()==='POST') return $this->save($r);
        $pdo=$this->db();$rows=$pdo->query('SELECT id,name,slug,description,parent_id,created_at FROM favorite_shop_product_categories ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $token=$e($_SESSION['_token']??(function_exists('csrf_token')?csrf_token():''));
        $html='<div style="max-width:1000px;margin:24px auto;padding:18px"><style>.fscard{border:1px solid #ddd;border-radius:12px;padding:18px;margin:14px 0}.fsgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.fsgrid label{display:block}.fsgrid input,.fsgrid textarea{box-sizing:border-box;width:100%;padding:10px;border:1px solid #bbb;border-radius:7px;margin:5px 0 12px}.fsbtn{padding:10px 14px;border:0;border-radius:7px;background:#185adb;color:white}.fstable{width:100%;border-collapse:collapse}.fstable td,.fstable th{text-align:left;padding:10px;border-bottom:1px solid #ddd}</style><h1>Product Categories</h1><p>Categories can be assigned to products and targeted by scheduled offers.</p>';
        foreach(['flash_error'=>'#fff0d7','flash_success'=>'#e2f8e8'] as $k=>$bg){if(isset($_SESSION[$k])){$html.='<p style="background:'.$bg.';padding:12px">'.$e($_SESSION[$k]).'</p>';unset($_SESSION[$k]);}}
        $html.='<form class="fscard" method="post"><input type="hidden" name="_token" value="'.$token.'"><div class="fsgrid"><label>Category name<input name="name" required maxlength="190"></label><label>Slug (optional)<input name="slug" maxlength="190"></label><label>Description<textarea name="description"></textarea></label></div><button class="fsbtn">Create category</button></form><div class="fscard"><h2>Categories</h2><div style="overflow:auto"><table class="fstable"><thead><tr><th>Name</th><th>Slug</th><th>Description</th></tr></thead><tbody>';
        foreach($rows as $row)$html.='<tr><td>'.$e($row['name']).'</td><td>'.$e($row['slug']).'</td><td>'.$e($row['description']).'</td></tr>';
        if(!$rows)$html.='<tr><td colspan="3">No categories created yet.</td></tr>';
        return $html.'</tbody></table></div></div></div>';
    }
    private function save(Request $r): Response
    {
        if(!hash_equals((string)($_SESSION['_token']??''),(string)$r->post('_token',''))){$_SESSION['flash_error']='Security token expired.';return Response::redirect('/admin/page/favorite-shop-categories');}
        try{
            $name=trim((string)$r->post('name',''));if($name===''||strlen($name)>190)throw new \InvalidArgumentException('Category name is required.');
            $slug=strtolower(trim((string)$r->post('slug','')));if($slug==='')$slug=$name;$slug=preg_replace('/[^a-z0-9]+/','-',$slug)??'';$slug=trim($slug,'-');if($slug==='')throw new \InvalidArgumentException('Enter a valid category name or slug.');
            $this->db()->prepare('INSERT INTO favorite_shop_product_categories (name,slug,description) VALUES (?,?,?)')->execute([$name,substr($slug,0,190),trim((string)$r->post('description',''))?:null]);
            $_SESSION['flash_success']='Category created.';return Response::redirect('/admin/page/favorite-shop-categories');
        }catch(Throwable $e){$_SESSION['flash_error']=$e instanceof \InvalidArgumentException?$e->getMessage():'Could not create category. The slug may already exist.';return Response::redirect('/admin/page/favorite-shop-categories');}
    }
    private function db(): \PDO{$db=$this->app->make(Database::class);if(!method_exists($db,'getConnection')||!($pdo=$db->getConnection()) instanceof \PDO)throw new \RuntimeException('Database connection unavailable.');return $pdo;}
}
