<?php
declare(strict_types=1);
namespace FavoriteCMS\PageBuilder;
use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Hook;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Core\Router;
use FavoriteCMS\Models\Setting;
use FavoriteCMS\Core\AdminMenu;
final class FavoritePageBuilderPlugin {
    private static ?self $instance = null;
    private bool $ready = false;
    private BuilderRepository $repo;
    private BuilderRenderer $renderer;
    private function __construct(private Application $app) {
        $this->repo = new BuilderRepository($app->make(Database::class));
        $this->renderer = new BuilderRenderer($app->make(Database::class));
    }
    public static function bootstrap(Application $app): self {
        if (self::$instance) return self::$instance;
        self::$instance = new self($app); self::$instance->register(); return self::$instance;
    }
    public function register(): void {
        if ($this->ready) return;
        $this->repo->ensureSchema();
        AdminMenu::addMenu('favorite-page-builder', 'Page Builder', '🧩', fn(Request $r) => $this->adminPage($r), 'manage_options', 34);
        Router::get('/admin/api/favorite-page-builder/pages', [$this, 'apiList']);
        Router::post('/admin/api/favorite-page-builder/save', [$this, 'apiSave']);
        Router::post('/admin/api/favorite-page-builder/delete', [$this, 'apiDelete']);
        Router::post('/admin/api/favorite-page-builder/import', [$this, 'apiImport']);
        Router::get('/admin/api/favorite-page-builder/library', [$this, 'apiLibrary']);
        Router::get('/favorite-page-builder-assets/builder.js', fn(Request $r) => Response::make((string)file_get_contents(__DIR__ . '/../assets/builder.js'))->header('Content-Type', 'application/javascript; charset=utf-8'));
        Router::get('/favorite-page-builder-assets/builder.css', fn(Request $r) => Response::make((string)file_get_contents(__DIR__ . '/../assets/builder.css'))->header('Content-Type', 'text/css; charset=utf-8'));
        Router::get('/builder/{slug}', [$this, 'publicPage']);
        Hook::addFilter('favorite_page_builder_elements', fn($elements) => $elements, 1, 1);
        $this->ready = true;
    }
    private function allowed(): bool {
        return function_exists('current_user_can') && current_user_can('manage_options');
    }
    private function csrfValid(Request $r): bool {
        $sent = (string)$r->post('_token', '');
        $expected = (string)($_SESSION['_token'] ?? '');
        if ($expected === '' && function_exists('csrf_token')) $expected = (string)csrf_token();
        return $sent !== '' && $expected !== '' && hash_equals($expected, $sent);
    }
    private function json(array $data, int $status=200): Response { return Response::json($data, $status); }
    public function apiList(Request $r): Response {
        if (!$this->allowed()) return $this->json(['success'=>false,'message'=>'Forbidden'],403);
        return $this->json(['success'=>true,'pages'=>$this->repo->all()]);
    }
    public function apiLibrary(Request $r): Response {
        if (!$this->allowed()) return $this->json(['success'=>false,'message'=>'Forbidden'],403);
        return $this->json(['success'=>true,'elements'=>$this->elements(),'templates'=>$this->templates()]);
    }
    public function apiSave(Request $r): Response {
        if (!$this->allowed()) return $this->json(['success'=>false,'message'=>'Forbidden'],403);
        if (!$this->csrfValid($r)) return $this->json(['success'=>false,'message'=>'Invalid security token. Reload the builder and try again.'],419);
        $raw = (string)$r->post('document','');
        $doc = json_decode($raw, true);
        if (!is_array($doc)) return $this->json(['success'=>false,'message'=>'Invalid page JSON.'],422);
        $title = trim((string)($doc['title'] ?? 'Untitled page'));
        if ($title === '') $title = 'Untitled page';
        $slug = $this->slug((string)($doc['slug'] ?? $title));
        $type = in_array(($doc['page_type'] ?? 'landing'), ['landing','checkout','confirmation','standard'], true) ? $doc['page_type'] : 'landing';
        $doc['title']=$title; $doc['slug']=$slug; $doc['page_type']=$type;
        $doc['sections']=array_slice(is_array($doc['sections'] ?? null) ? $doc['sections'] : [],0,100);
        $id=(int)$r->post('id',0);
        try {
            $saved=$this->repo->save($id,$title,$slug,$type,$doc,(int)($_SESSION['auth_user_id']??0));
            return $this->json(['success'=>true,'page'=>$saved,'message'=>'Page saved.']);
        } catch (\Throwable $e) { return $this->json(['success'=>false,'message'=>'Could not save page: '.$e->getMessage()],500); }
    }
    public function apiDelete(Request $r): Response {
        if (!$this->allowed()) return $this->json(['success'=>false,'message'=>'Forbidden'],403);
        if (!$this->csrfValid($r)) return $this->json(['success'=>false,'message'=>'Invalid security token.'],419);
        $id=(int)$r->post('id',0); if ($id<1) return $this->json(['success'=>false,'message'=>'Missing page ID.'],422);
        $this->repo->delete($id); return $this->json(['success'=>true]);
    }
    public function apiImport(Request $r): Response {
        if (!$this->allowed()) return $this->json(['success'=>false,'message'=>'Forbidden'],403);
        if (!$this->csrfValid($r)) return $this->json(['success'=>false,'message'=>'Invalid security token.'],419);
        $doc=json_decode((string)$r->post('document',''),true);
        if (!is_array($doc) || !is_array($doc['sections']??null)) return $this->json(['success'=>false,'message'=>'This is not a valid Favorite Page Builder JSON template.'],422);
        $doc['id']=0; $doc['title']=trim((string)($doc['title']??'Imported page')); $doc['slug']=$this->slug((string)($doc['title']));
        return $this->json(['success'=>true,'document'=>$doc]);
    }
    public function publicPage(Request $r, string $slug): Response {
        $page=$this->repo->findBySlug($slug);
        if (!$page || empty($page['published'])) return Response::make('Page not found.',404);
        $html=$this->renderer->render($page['document']);
        $site=(string)Setting::get('general','site_name','Favorite CMS');
        $title=htmlspecialchars((string)$page['title'],ENT_QUOTES,'UTF-8');
        $css=(string)file_get_contents(__DIR__.'/../assets/frontend.css');
        return Response::make('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.'</title><style>'.$css.'</style></head><body><header class="fpb-sitebar"><a href="'.htmlspecialchars(site_path('/'),ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($site,ENT_QUOTES,'UTF-8').'</a></header><main>'.$html.'</main></body></html>',200);
    }
    public function adminPage(Request $r): string {
        if (!$this->allowed()) return '<p>You do not have permission to manage pages.</p>';
        $token=function_exists('csrf_token')?(string)csrf_token():(string)($_SESSION['_token']??'');
        $base=rtrim((string)($GLOBALS['favorite_cms_base_path']??''),'/');
        $js=$base.'/favorite-page-builder-assets/builder.js';
        $css=$base.'/favorite-page-builder-assets/builder.css';
        $pages=$this->repo->all();
        $pageId=(int)$r->get('id',0);
        $selected=$pageId?$this->repo->find($pageId):null;
        $doc=$selected['document']??['title'=>'New page','slug'=>'new-page','page_type'=>'landing','published'=>false,'sections'=>[],'settings'=>[]];
        $docJson=htmlspecialchars((string)json_encode($doc,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),ENT_QUOTES,'UTF-8');
        $list='<div class="fpb-existing"><strong>Saved pages</strong><div>';
        foreach($pages as $p) $list.='<a href="'.htmlspecialchars(site_path('/admin/page/favorite-page-builder?id='.(int)$p['id']),ENT_QUOTES,'UTF-8').'">'.htmlspecialchars($p['title'],ENT_QUOTES,'UTF-8').'</a>';
        $list.='</div></div>';
        return '<link rel="stylesheet" href="'.htmlspecialchars($css,ENT_QUOTES,'UTF-8').'"><div id="fpb-app" data-token="'.htmlspecialchars($token,ENT_QUOTES,'UTF-8').'" data-base="'.htmlspecialchars($base,ENT_QUOTES,'UTF-8').'" data-doc="'.$docJson.'" data-id="'.(int)($selected['id']??0).'"><div class="fpb-topbar"><div><strong>Favorite Page Builder</strong><span>Visual editor</span></div><div class="fpb-top-actions"><button type="button" id="fpb-new-page">New page</button><button type="button" id="fpb-preview">Preview</button><button type="button" id="fpb-templates">Templates</button><button type="button" id="fpb-export">Export JSON</button><label class="fpb-import">Import JSON<input type="file" id="fpb-import" accept=".json,application/json"></label><button type="button" id="fpb-save" class="primary">Save page</button></div></div><div class="fpb-workspace"><aside class="fpb-left"><div class="fpb-tabs"><button class="active" data-tab="elements">Elements</button><button data-tab="pages">Pages</button></div><div id="fpb-elements"></div>'.$list.'<div class="fpb-help">Click an element on the canvas to edit its settings. Drag items to reorder.</div></aside><section class="fpb-center"><div class="fpb-page-meta"><input id="fpb-title" value="'.htmlspecialchars((string)($doc['title']??''),ENT_QUOTES,'UTF-8').'" placeholder="Page title"><input id="fpb-slug" value="'.htmlspecialchars((string)($doc['slug']??''),ENT_QUOTES,'UTF-8').'" placeholder="page-slug"><select id="fpb-type"><option value="landing">Landing page</option><option value="standard">Standard page</option><option value="checkout">Checkout page</option><option value="confirmation">Order confirmation</option></select><label><input type="checkbox" id="fpb-published" '.(!empty($doc['published'])?'checked':'').'> Published</label></div><div class="fpb-devicebar"><button data-device="desktop" class="active">Desktop</button><button data-device="tablet">Tablet</button><button data-device="mobile">Mobile</button><button id="fpb-add-section">+ Add section</button></div><div id="fpb-canvas" class="fpb-device-desktop"></div></section><aside class="fpb-right"><div class="fpb-inspector-title">Element settings</div><div id="fpb-inspector"><p>Select an element to customize.</p></div></aside></div><div id="fpb-status" role="status"></div><script src="'.htmlspecialchars($js,ENT_QUOTES,'UTF-8').'" defer></script></div><script>document.getElementById("fpb-type").value='.json_encode($doc['page_type']??'landing').';</script>';
    }
    private function slug(string $value): string {
        $value=strtolower(trim($value)); $value=preg_replace('/[^a-z0-9]+/','-',$value)??''; return trim(substr($value,0,180),'-')?:'page';
    }
    private function elements(): array {
        return [
            ['type'=>'heading','name'=>'Heading','category'=>'Basic','defaults'=>['text'=>'Your headline','tag'=>'h2','color'=>'#172033','alignment'=>'left','size'=>'36px']],
            ['type'=>'text','name'=>'Text','category'=>'Basic','defaults'=>['text'=>'Write something useful for your visitors.','color'=>'#475569','size'=>'16px','alignment'=>'left']],
            ['type'=>'image','name'=>'Image','category'=>'Media','defaults'=>['url'=>'','alt'=>'','radius'=>'12px']],
            ['type'=>'button','name'=>'Button','category'=>'Basic','defaults'=>['text'=>'Learn more','url'=>'#','background'=>'#2563eb','color'=>'#ffffff']],
            ['type'=>'divider','name'=>'Divider','category'=>'Layout','defaults'=>['color'=>'#e2e8f0']],
            ['type'=>'spacer','name'=>'Spacer','category'=>'Layout','defaults'=>['height'=>'32px']],
            ['type'=>'post_grid','name'=>'Recent posts grid','category'=>'Dynamic','defaults'=>['limit'=>6,'columns'=>3,'mode'=>'recent','taxonomy'=>'category','term'=>'']],
            ['type'=>'product_grid','name'=>'Product showcase grid','category'=>'Commerce','defaults'=>['limit'=>6,'columns'=>3,'mode'=>'recent','product_id'=>0,'category'=>'','tag'=>'']],
            ['type'=>'checkout_cta','name'=>'Checkout / Buy now','category'=>'Commerce','defaults'=>['text'=>'Order now','product_id'=>0,'checkout_url'=>'']],
            ['type'=>'order_confirmation','name'=>'Order confirmation','category'=>'Commerce','defaults'=>['heading'=>'Thank you for your order','text'=>'Your order has been received.']],
            ['type'=>'html','name'=>'Custom HTML','category'=>'Advanced','defaults'=>['html'=>'']],
        ];
    }
    private function templates(): array {
        return [
            ['name'=>'Product landing page','page_type'=>'landing','sections'=>[['columns'=>[['elements'=>[['type'=>'heading','settings'=>['text'=>'A better way to get results','tag'=>'h1','size'=>'48px']],['type'=>'text','settings'=>['text'=>'Explain the value of your product in a few short sentences.']],['type'=>'checkout_cta','settings'=>['text'=>'Get started','product_id'=>0]]]]]]]],
            ['name'=>'Product showcase','page_type'=>'landing','sections'=>[['columns'=>[['elements'=>[['type'=>'heading','settings'=>['text'=>'Featured products']],['type'=>'product_grid','settings'=>['limit'=>6,'columns'=>3]]]]]]]],
            ['name'=>'Thank you page','page_type'=>'confirmation','sections'=>[['columns'=>[['elements'=>[['type'=>'order_confirmation','settings'=>[]]]]]]]],
        ];
    }
}
