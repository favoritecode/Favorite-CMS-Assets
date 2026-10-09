<?php
declare(strict_types=1);

namespace FavoriteCMS\Shop\Controllers;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
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
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return Response::make('<h1>403 Access Denied</h1>', 403);
        }
        if ($request->method() === 'POST') return $this->save($request);
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
            'csrfToken'=>$this->csrf(),'flashSuccess'=>$_SESSION['flash_success'] ?? null,'flashError'=>$_SESSION['flash_error'] ?? null,
        ]);
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
        $labels = json_decode((string) ($product['labels_json'] ?? '[]'), true);\n        $product['labels_text'] = is_array($labels) ? implode(', ', array_filter($labels, 'is_string')) : '';
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
            $type = 'simple';
            $slug = $this->slug((string) $request->post('slug', ''), $name);
            $sku = trim((string) $request->post('sku', ''));
            $sku = $sku === '' ? null : substr($sku, 0, 100);
            $cover = $this->safeImageUrl((string) $request->post('cover_image_url', ''));\n            $scope = OfferPricing::normalizeScope([], (string)$request->post('labels', ''));
            $categoryIds = OfferPricing::normalizeScope((array)$request->post('category_ids', []), '')['category_ids'];
            if ($categoryIds) { $check = $this->db()->prepare('SELECT COUNT(*) FROM favorite_shop_product_categories WHERE id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ')'); $check->execute($categoryIds); if ((int)$check->fetchColumn() !== count($categoryIds)) throw new \\InvalidArgumentException('One or more selected categories no longer exist.'); }
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

    private function moneyToCents(mixed $value): int
    {
        if (!is_numeric($value) || (float) $value < 0 || (float) $value > 999999999999) throw new \InvalidArgumentException('Enter a valid non-negative price.');
        return (int) round((float) $value * 100);
    }

    private function safeImageUrl(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http','https'], true)) {
            throw new \InvalidArgumentException('Images must use a valid HTTP or HTTPS URL.');
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
