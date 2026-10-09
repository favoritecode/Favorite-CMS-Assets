<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Services\MediaService;
use FavoriteCMS\Shop\Domain\ProductMeasurement;
use FavoriteCMS\Shop\Domain\OfferPricing;
use FavoriteCMS\Shop\Domain\Quantity;
use FavoriteCMS\Shop\Domain\StockStatus;
use Throwable;

final class AdminProductController
{
    public function __construct(private Application $app) {}

    public function handle(Request $request): Response|string
    {
        $userId = (int) ($_SESSION['auth_user_id'] ?? 0);
        if ($userId <= 0 && !isset($GLOBALS['_test_current_user'])) return Response::redirect('/admin/login');
        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            return Response::make('<h1>403 Access Denied</h1>', 403);
        }
        if ($request->method() === 'POST') return (string)$request->post('action','')==='bulk_variant_price' ? $this->bulkVariantPrice($request) : $this->save($request);
        $action = (string) $request->get('action', 'index');
        return match ($action) {
            'create' => $this->form(),
            'edit' => $this->form((int) $request->get('id', 0)),
            default => $this->index($request),
        };
    }

    private function db(): \PDO
    {
        $db = $this->app->make(Database::class);
        if (!method_exists($db, 'getConnection') || !($pdo = $db->getConnection()) instanceof \PDO) {
            throw new \RuntimeException('Favorite Shop database connection is unavailable.');
        }
        return $pdo;
    }

    private function index(Request $request): string
    {
        $pdo = $this->db();
        $search = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $sql = 'SELECT id,sku,name,product_type,unit_type,price_cents,sale_price_cents,stock_quantity,stock_status,status,cover_image_url,updated_at FROM favorite_shop_products WHERE 1=1';
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR sku LIKE ?)';
            $params[] = '%' . $search . '%'; $params[] = '%' . $search . '%';
        }
        if (in_array($status, ['draft','published','archived'], true)) { $sql .= ' AND status = ?'; $params[] = $status; }
        $sql .= ' ORDER BY updated_at DESC LIMIT 200';
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $counts = [];
        foreach (['all' => '1=1', 'published' => "status='published'", 'draft' => "status='draft'", 'archived' => "status='archived'"] as $key => $where) {
            $counts[$key] = (int) $pdo->query('SELECT COUNT(*) FROM favorite_shop_products WHERE ' . $where)->fetchColumn();
        }
        return $this->view('products/index', [
            'products'=>$products,'counts'=>$counts,'search'=>$search,'status'=>$status,
            'csrfToken'=>$this->csrf(),'categories'=>$pdo->query('SELECT id,name FROM favorite_shop_product_categories ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC),'flashSuccess'=>$_SESSION['flash_success'] ?? null,'flashError'=>$_SESSION['flash_error'] ?? null,
        ]);
    }

    private function bulkVariantPrice(Request $request):Response
    {
        if(!hash_equals((string)($_SESSION['_token']??''),(string)$request->post('_token',''))){$_SESSION['flash_error']='Security token expired.';return Response::redirect('/admin/page/favorite-shop-products');}
        try{
            $target=(string)$request->post('target','all');$operation=(string)$request->post('operation','increase_percent');$value=$request->post('value','');
            if(!in_array($target,['all','category','selected'],true))throw new \InvalidArgumentException('Choose a valid variant target.');
            if(!in_array($operation,['increase_percent','decrease_percent','set_price'],true))throw new \InvalidArgumentException('Choose a valid price operation.');
            if(!is_numeric($value)||(float)$value<0||(float)$value>999999999999)throw new \InvalidArgumentException('Enter a valid non-negative value.');
            if($operation!=='set_price'&&(float)$value>1000)throw new \InvalidArgumentException('Percentage change cannot exceed 1000%.');
            $params=[];$sql="SELECT v.id,COALESCE(v.price_cents,p.price_cents) AS current_price FROM favorite_shop_product_variants v JOIN favorite_shop_products p ON p.id=v.product_id WHERE v.status='active'";
            if($target==='category'){$categoryId=filter_var($request->post('category_id',''),FILTER_VALIDATE_INT);if(!$categoryId||$categoryId<1)throw new \InvalidArgumentException('Choose a category.');$sql.=' AND EXISTS (SELECT 1 FROM favorite_shop_product_category_map m WHERE m.product_id=p.id AND m.category_id=?)';$params[]=$categoryId;}
            if($target==='selected'){$ids=(array)$request->post('variant_ids',[]);if(!$ids)$ids=preg_split('/[,;\\s]+/',trim((string)$request->post('variant_ids_text','')))?:[];$ids=array_values(array_unique(array_filter(array_map('intval',$ids),static fn($n)=>$n>0)));if(!$ids)throw new \InvalidArgumentException('Enter at least one variant ID.');$sql.=' AND v.id IN ('.implode(',',array_fill(0,count($ids),'?')).')';array_push($params,...$ids);}
            $pdo=$this->db();$pdo->beginTransaction();$q=$pdo->prepare($sql.' FOR UPDATE');$q->execute($params);$rows=$q->fetchAll(\PDO::FETCH_ASSOC);if(!$rows)throw new \InvalidArgumentException('No active variants matched the selected target.');
            $amount=$operation==='set_price'?$this->moneyToCents($value):(int)round((float)$value*100);
            $update=$pdo->prepare('UPDATE favorite_shop_product_variants SET price_cents=? WHERE id=?');
            foreach($rows as $row){$current=(int)$row['current_price'];$new=match($operation){'increase_percent'=>(int)round($current*(1+(float)$value/100)),'decrease_percent'=>(int)round($current*(1-(float)$value/100)),default=>$amount};$update->execute([max(0,$new),(int)$row['id']]);}
            $pdo->commit();$_SESSION['flash_success']='Updated regular prices for '.count($rows).' variants. SKU, stock and sale-price overrides were preserved.';
        }catch(Throwable $e){if(isset($pdo)&&$pdo instanceof \PDO&&$pdo->inTransaction())$pdo->rollBack();$_SESSION['flash_error']=$e instanceof \InvalidArgumentException?$e->getMessage():'Could not bulk-update variant prices.';}
        return Response::redirect('/admin/page/favorite-shop-products');
    }

    private function form(int $id = 0): string
    {
        $product = [
            'id'=>0,'name'=>'','slug'=>'','sku'=>'','description'=>'','short_description'=>'',
            'product_type'=>'simple','unit_type'=>'piece','unit_quantity'=>'1','unit_label'=>'',
            'price_cents'=>0,'sale_price_cents'=>'','cost_cents'=>'','stock_quantity'=>'0',
            'stock_status'=>'in_stock','manage_stock'=>1,'low_stock_threshold'=>'0','allow_backorder'=>0,'weight_grams'=>'','cover_image_url'=>'',
            'gallery_json'=>'[]','labels_json'=>'[]','status'=>'draft',
        ];
        if ($id > 0) {
            $stmt = $this->db()->prepare('SELECT * FROM favorite_shop_products WHERE id = ?');
            $stmt->execute([$id]);
            $found = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$found) return '<div class="notice notice-error">Product not found.</div>';
            $product = array_merge($product, $found);
        }
        $gallery = json_decode((string) ($product['gallery_json'] ?? '[]'), true);
        $product['gallery_text'] = is_array($gallery) ? implode("\n", array_filter($gallery, 'is_string')) : '';
        $variantStmt=$this->db()->prepare('SELECT id,sku,option_values_json,unit_type,unit_quantity,unit_label,price_cents,sale_price_cents,stock_quantity,stock_status,low_stock_threshold,allow_backorder,weight_grams,image_url,status FROM favorite_shop_product_variants WHERE product_id=? ORDER BY id');
        $variantStmt->execute([(int)$product['id']]);$variantRows=$variantStmt->fetchAll(\PDO::FETCH_ASSOC);$variantInput=[];
        foreach($variantRows as $vr){$opts=json_decode((string)$vr['option_values_json'],true)?:[];$variantInput[]=['id'=>(int)$vr['id'],'sku'=>$vr['sku'],'options'=>$opts,'unit_type'=>$vr['unit_type'],'unit_quantity'=>$vr['unit_quantity'],'unit_label'=>$vr['unit_label'],'price'=>$vr['price_cents']===null?'':number_format((int)$vr['price_cents']/100,2,'.',''),'sale_price'=>$vr['sale_price_cents']===null?'':number_format((int)$vr['sale_price_cents']/100,2,'.',''),'stock_quantity'=>$vr['stock_quantity'],'stock_status'=>$vr['stock_status'],'low_stock_threshold'=>$vr['low_stock_threshold'],'allow_backorder'=>(int)$vr['allow_backorder'],'weight_grams'=>$vr['weight_grams'],'image_url'=>$vr['image_url'],'status'=>$vr['status']];}
        $product['variants_json_text']=json_encode($variantInput,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?:'[]';
        $labels = json_decode((string) ($product['labels_json'] ?? '[]'), true);
        $product['labels_text'] = is_array($labels) ? implode(', ', array_filter($labels, 'is_string')) : '';
        $categoryStmt = $this->db()->prepare('SELECT category_id FROM favorite_shop_product_category_map WHERE product_id = ?');
        $categoryStmt->execute([(int)$product['id']]);
        $product['category_ids'] = array_map('intval', $categoryStmt->fetchAll(\PDO::FETCH_COLUMN));
        $product['categories'] = $this->db()->query('SELECT id,name FROM favorite_shop_product_categories ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
        return $this->view('products/form', [
            'product'=>$product,'csrfToken'=>$this->csrf(),'isEdit'=>$id > 0,
            'flashError'=>$_SESSION['flash_error'] ?? null,
        ]);
    }

    private function save(Request $request): Response
    {
        if (!hash_equals((string) ($_SESSION['_token'] ?? ''), (string) $request->post('_token', ''))) {
            $_SESSION['flash_error'] = 'Security token expired. Please try again.';
            return Response::redirect('/admin/page/favorite-shop-products');
        }
        $id = (int) $request->post('id', 0);
        try {
            $name = trim((string) $request->post('name', ''));
            if ($name === '' || strlen($name) > 255) throw new \InvalidArgumentException('Product name is required (maximum 255 characters).');
            $unit = ProductMeasurement::normalize(
                (string) $request->post('unit_type', 'piece'),
                $request->post('unit_quantity', '1'),
                (string) $request->post('unit_label', ''),
                $request->post('weight_grams', '')
            );
            $stockRaw = $request->post('stock_quantity', '0');
            if (!is_numeric($stockRaw) || !is_finite((float)$stockRaw) || (float)$stockRaw < 0 || (float)$stockRaw > 100000000000) throw new \InvalidArgumentException('Stock must be zero or a positive finite quantity.');
            $quantity = rtrim(rtrim(number_format((float)$stockRaw, 3, '.', ''), '0'), '.');
            if ($quantity === '') $quantity = '0';
            if ($unit['unit'] === 'piece' && floor((float) $quantity) !== (float) $quantity) throw new \InvalidArgumentException('Piece stock must be a whole number.');
            $thresholdRaw = $request->post('low_stock_threshold', '0');
            if (!is_numeric($thresholdRaw) || !is_finite((float)$thresholdRaw) || (float)$thresholdRaw < 0) throw new \InvalidArgumentException('Low-stock threshold must be non-negative.');
            $threshold = rtrim(rtrim(number_format((float)$thresholdRaw, 3, '.', ''), '0'), '.');
            if ($threshold === '') $threshold = '0';
            if ($unit['unit'] === 'piece' && floor((float)$threshold) !== (float)$threshold) throw new \InvalidArgumentException('Piece-based low-stock threshold must be a whole number.');
            $allowBackorder = $request->post('allow_backorder') ? 1 : 0;
            $manageStock = $request->post('manage_stock') ? 1 : 0;
            $stockStatus = StockStatus::resolve($quantity, (bool)$manageStock, (bool)$allowBackorder, $threshold);
            $price = $this->moneyToCents($request->post('price', '0'));
            $saleRaw = trim((string) $request->post('sale_price', ''));
            $sale = $saleRaw === '' ? null : $this->moneyToCents($saleRaw);
            if ($sale !== null && $sale > $price) throw new \InvalidArgumentException('Sale price cannot be higher than regular price.');
            $costRaw = trim((string) $request->post('cost', ''));
            $cost = $costRaw === '' ? null : $this->moneyToCents($costRaw);
            $status = in_array((string) $request->post('status', 'draft'), ['draft','published','archived'], true) ? (string) $request->post('status', 'draft') : 'draft';
            $type = (string)$request->post('product_type','simple');
            if(!in_array($type,['simple','variable'],true))throw new \InvalidArgumentException('Choose simple or variable product type.');
            $variants=$this->normalizeVariants((string)$request->post('variants_json','[]'),$unit['unit'],$type);
            if($type==='variable'&&count(array_filter($variants,static fn($v)=>$v['status']==='active'))<1)throw new \InvalidArgumentException('A variable product needs at least one active variant.');
            $slug = $this->slug((string) $request->post('slug', ''), $name);
            $sku = trim((string) $request->post('sku', ''));
            $sku = $sku === '' ? null : substr($sku, 0, 100);
            $cover = $this->safeImageUrl((string) $request->post('cover_image_url', ''));
            $coverUpload = $request->file('cover_image');
            if (is_array($coverUpload) && (int)($coverUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ((int)($coverUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new \InvalidArgumentException('The product image upload did not complete. Please try again.');
                }
                $tmpName = (string)($coverUpload['tmp_name'] ?? '');
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    throw new \InvalidArgumentException('The product image upload is invalid.');
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = (string)$finfo->file($tmpName);
                if (!str_starts_with($mime, 'image/')) {
                    throw new \InvalidArgumentException('Please upload an image file (JPEG, PNG, GIF, WebP, or an allowed safe SVG).');
                }
                $media = $this->app->make(MediaService::class)->upload($coverUpload, $userId);
                if (!isset($media->url) || !is_string($media->url) || $media->url === '') {
                    throw new \RuntimeException('The media library did not return an image URL.');
                }
                $cover = $this->safeImageUrl($media->url);
            }
            $scope = OfferPricing::normalizeScope([], (string)$request->post('labels', ''));
            $categoryIds = OfferPricing::normalizeScope((array)$request->post('category_ids', []), '')['category_ids'];
            if ($categoryIds) { $check = $this->db()->prepare('SELECT COUNT(*) FROM favorite_shop_product_categories WHERE id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ')'); $check->execute($categoryIds); if ((int)$check->fetchColumn() !== count($categoryIds)) throw new \InvalidArgumentException('One or more selected categories no longer exist.'); }
            $gallery = [];
            foreach (preg_split('/\R/', (string) $request->post('gallery_text', '')) ?: [] as $url) {
                $url = $this->safeImageUrl(trim($url));
                if ($url !== null) $gallery[] = $url;
            }
            $fields = [
                'sku'=>$sku,'slug'=>$slug,'name'=>$name,
                'description'=>trim((string) $request->post('description', '')),
                'short_description'=>trim((string) $request->post('short_description', '')),
                'product_type'=>$type,'unit_type'=>$unit['unit'],'unit_quantity'=>$unit['unit_quantity'],
                'unit_label'=>$unit['unit_label'],'status'=>$status,'price_cents'=>$price,
                'sale_price_cents'=>$sale,'cost_cents'=>$cost,'stock_quantity'=>$quantity,
                'stock_status'=>$stockStatus,
                'manage_stock'=>$manageStock,'low_stock_threshold'=>$threshold,'allow_backorder'=>$allowBackorder,
                'weight_grams'=>$unit['weight_grams'],'cover_image_url'=>$cover,
                'gallery_json'=>json_encode(array_values(array_unique($gallery)), JSON_UNESCAPED_SLASHES),
                'labels_json'=>json_encode($scope['labels'], JSON_UNESCAPED_UNICODE),
                'metadata_json'=>null,
            ];
            $pdo = $this->db();
            $pdo->beginTransaction();
            if ($id > 0) {
                $set = [];
                foreach ($fields as $column => $_) $set[] = $column . ' = ?';
                $values = array_values($fields); $values[] = $id;
                $stmt = $pdo->prepare('UPDATE favorite_shop_products SET ' . implode(', ', $set) . ' WHERE id = ?');
                $stmt->execute($values);
            } else {
                $columns = array_keys($fields);
                $stmt = $pdo->prepare('INSERT INTO favorite_shop_products (' . implode(',', $columns) . ') VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')');
                $stmt->execute(array_values($fields));
                $id = (int) $pdo->lastInsertId();
            }
            $pdo->prepare('DELETE FROM favorite_shop_product_category_map WHERE product_id = ?')->execute([$id]);
            $pdo->prepare("UPDATE favorite_shop_product_variants SET status='archived' WHERE product_id=?")->execute([$id]);
            foreach($variants as $variant){
                $variantId=$variant['id'];$data=[$variant['sku'],json_encode($variant['options'],JSON_UNESCAPED_UNICODE),$variant['unit_type'],$variant['unit_quantity'],$variant['unit_label'],$variant['price_cents'],$variant['sale_price_cents'],$variant['stock_quantity'],$variant['stock_status'],$variant['low_stock_threshold'],$variant['allow_backorder'],$variant['weight_grams'],$variant['image_url'],$variant['status']];
                if($variantId>0){
                    $check=$pdo->prepare('SELECT id FROM favorite_shop_product_variants WHERE id=? AND product_id=?');$check->execute([$variantId,$id]);if(!$check->fetchColumn())throw new \InvalidArgumentException('A selected variant does not belong to this product.');
                    $pdo->prepare('UPDATE favorite_shop_product_variants SET sku=?,option_values_json=?,unit_type=?,unit_quantity=?,unit_label=?,price_cents=?,sale_price_cents=?,stock_quantity=?,stock_status=?,low_stock_threshold=?,allow_backorder=?,weight_grams=?,image_url=?,status=? WHERE id=? AND product_id=?')->execute([...$data,$variantId,$id]);
                }else{
                    $pdo->prepare('INSERT INTO favorite_shop_product_variants (product_id,sku,option_values_json,unit_type,unit_quantity,unit_label,price_cents,sale_price_cents,stock_quantity,stock_status,low_stock_threshold,allow_backorder,weight_grams,image_url,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$id,...$data]);
                }
            }

            if ($categoryIds) { $map = $pdo->prepare('INSERT INTO favorite_shop_product_category_map (product_id,category_id) VALUES (?,?)'); foreach ($categoryIds as $categoryId) $map->execute([$id, $categoryId]); }
            $pdo->commit();
            unset($_SESSION['old_input'], $_SESSION['flash_error']);
            $_SESSION['flash_success'] = 'Product saved successfully.';
            return Response::redirect('/admin/page/favorite-shop-products?action=edit&id=' . $id);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof \PDO && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error'] = $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Could not save product. Check SKU/slug uniqueness and database configuration.';
            // Keep the current screen stable; error details are shown without dumping raw request data.
            return Response::redirect('/admin/page/favorite-shop-products' . ($id > 0 ? '?action=edit&id=' . $id : '?action=create'));
        }
    }

    private function normalizeVariants(string $json,string $parentUnit,string $productType):array
    {
        if($productType==='simple')return [];
        try{$rows=json_decode($json,true,512,JSON_THROW_ON_ERROR);}catch(\Throwable){throw new \InvalidArgumentException('Variants must be a valid JSON array.');}
        if(!is_array($rows)||!array_is_list($rows)||count($rows)>500)throw new \InvalidArgumentException('Variants must be a JSON array with at most 500 rows.');
        $out=[];
        foreach($rows as $i=>$row){
            if(!is_array($row))throw new \InvalidArgumentException('Variant row '.($i+1).' must be an object.');
            $options=$row['options']??[];if(!is_array($options)||!$options)throw new \InvalidArgumentException('Each variant needs option values such as Color and Size.');
            $cleanOptions=[];foreach($options as $key=>$value){$key=trim((string)$key);$value=trim((string)$value);if($key===''||$value===''||strlen($key)>80||strlen($value)>120)throw new \InvalidArgumentException('Variant option names and values must be non-empty and short.');$cleanOptions[$key]=$value;}
            $unit=(string)($row['unit_type']??$parentUnit);if(!in_array($unit,ProductMeasurement::UNITS,true))throw new \InvalidArgumentException('Variant selling unit is invalid.');
            $priceRaw=$row['price']??'';$price=$priceRaw===''?null:$this->moneyToCents($priceRaw);$saleRaw=$row['sale_price']??'';$sale=$saleRaw===''?null:$this->moneyToCents($saleRaw);
            if($price!==null&&$sale!==null&&$sale>$price)throw new \InvalidArgumentException('Variant sale price cannot exceed its regular price.');
            $stock=$row['stock_quantity']??0;if(!is_numeric($stock)||(float)$stock<0||!is_finite((float)$stock))throw new \InvalidArgumentException('Variant stock must be non-negative.');
            $stock=number_format((float)$stock,3,'.','');if($unit==='piece'&&floor((float)$stock)!==(float)$stock)throw new \InvalidArgumentException('Piece variant stock must be a whole number.');
            $threshold=$row['low_stock_threshold']??0;if(!is_numeric($threshold)||(float)$threshold<0||!is_finite((float)$threshold))throw new \InvalidArgumentException('Variant low-stock threshold must be non-negative.');
            $backorder=filter_var($row['allow_backorder']??false,FILTER_VALIDATE_BOOL);
            $stockStatus=StockStatus::resolve((float)$stock,true,$backorder,(float)$threshold);
            $weight=$row['weight_grams']??null;if($weight!==null&&$weight!==''&&(!is_numeric($weight)||(float)$weight<0||(float)$weight>2147483647))throw new \InvalidArgumentException('Variant shipping weight must be non-negative grams.');
            $status=(string)($row['status']??'active');if(!in_array($status,['active','archived'],true))$status='active';
            $id=filter_var($row['id']??0,FILTER_VALIDATE_INT);if($id===false||$id<0)throw new \InvalidArgumentException('Variant ID is invalid.');
            $sku=trim((string)($row['sku']??''));$out[]=['id'=>(int)$id,'sku'=>$sku===''?null:substr($sku,0,100),'options'=>$cleanOptions,'unit_type'=>$unit,'unit_quantity'=>Quantity::normalize($row['unit_quantity']??1),'unit_label'=>isset($row['unit_label'])?substr(trim((string)$row['unit_label']),0,80):null,'price_cents'=>$price,'sale_price_cents'=>$sale,'stock_quantity'=>rtrim(rtrim($stock,'0'),'.')?:'0','stock_status'=>$stockStatus,'low_stock_threshold'=>number_format((float)$threshold,3,'.',''),'allow_backorder'=>$backorder?1:0,'weight_grams'=>$weight===''||$weight===null?null:(int)$weight,'image_url'=>$this->safeImageUrl((string)($row['image_url']??'')),'status'=>$status];
        }
        return $out;
    }

    private function moneyToCents(mixed $value): int
    {
        if (!is_numeric($value) || (float) $value < 0 || (float) $value > 999999999999) throw new \InvalidArgumentException('Enter a valid non-negative price.');
        return (int) round((float) $value * 100);
    }

    private function safeImageUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        // Local URLs are accepted only for CMS-managed media and never for traversal paths.
        if (str_starts_with($value, '/uploads/')) {
            $relativePath = substr($value, strlen('/uploads/'));
            if ($relativePath === '' || preg_match('~[^A-Za-z0-9._/-]~', $relativePath)
                || str_contains($relativePath, '..') || str_contains($relativePath, '//')
                || str_contains($relativePath, '\\') || preg_match('/[\x00-\x1F]/', $relativePath)) {
                throw new \InvalidArgumentException('The local media URL is invalid.');
            }
            return substr($value, 0, 2048);
        }
        if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http','https'], true)) {
            throw new \InvalidArgumentException('Images must use a valid HTTP/HTTPS URL or a CMS Media Library image.');
        }
        return substr($value, 0, 2048);
    }

    private function slug(string $requested, string $name): string
    {
        $slug = strtolower(trim($requested !== '' ? $requested : $name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') throw new \InvalidArgumentException('Please provide a valid product slug.');
        return substr($slug, 0, 190);
    }

    private function csrf(): string
    {
        $token = (string) ($_SESSION['_token'] ?? '');
        if ($token === '' && function_exists('csrf_token')) $token = (string) csrf_token();
        return $token;
    }

    private function view(string $name, array $data): string
    {
        $path = __DIR__ . '/../../views/admin/' . $name . '.php';
        if (!is_file($path)) return '<div class="notice notice-error">Favorite Shop view is missing.</div>';
        extract($data, EXTR_SKIP);
        ob_start(); include $path; return (string) ob_get_clean();
    }
}
