<?php
declare(strict_types=1);
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($cents): string => '৳' . number_format(((int)$cents)/100, 2);
?>
<div class="fs-admin">
<style>
.fs-admin{--fs-line:#e5e7eb;--fs-muted:#64748b;--fs-ink:#0f172a;color:var(--fs-ink);font:14px/1.5 system-ui,-apple-system,Segoe UI,sans-serif;max-width:1440px;margin:18px auto;padding:0 18px}
.fs-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}.fs-title{font-size:27px;font-weight:750;letter-spacing:-.7px;margin:0}.fs-sub{color:var(--fs-muted);margin:4px 0 0}.fs-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--fs-line);border-radius:10px;padding:10px 14px;text-decoration:none;background:#fff;color:var(--fs-ink);font-weight:650}.fs-primary{background:#1d4ed8;color:#fff;border-color:#1d4ed8}.fs-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}.fs-stat{background:var(--fs-card, #fff);border:1px solid var(--fs-line);border-radius:14px;padding:16px}.fs-stat small{color:var(--fs-muted)}.fs-stat strong{display:block;font-size:26px;margin-top:5px}.fs-panel{border:1px solid var(--fs-line);border-radius:14px;overflow:hidden;background:var(--fs-card,#fff)}.fs-tools{padding:14px;display:flex;gap:10px;flex-wrap:wrap;border-bottom:1px solid var(--fs-line)}.fs-tools input,.fs-tools select{border:1px solid var(--fs-line);border-radius:9px;padding:10px;min-width:170px;background:transparent;color:inherit}.fs-table-wrap{overflow:auto}.fs-table{border-collapse:collapse;width:100%;min-width:850px}.fs-table th,.fs-table td{text-align:left;padding:13px 14px;border-bottom:1px solid var(--fs-line);vertical-align:middle}.fs-table th{font-size:12px;color:var(--fs-muted);text-transform:uppercase;letter-spacing:.04em;background:rgba(148,163,184,.07)}.fs-product{display:flex;align-items:center;gap:12px;min-width:220px}.fs-thumb{width:48px;height:48px;border-radius:9px;object-fit:cover;background:#f1f5f9;border:1px solid var(--fs-line)}.fs-status{font-size:12px;font-weight:700;border-radius:999px;padding:4px 8px;background:#f1f5f9;display:inline-block}.fs-status.published{background:#dcfce7;color:#166534}.fs-status.draft{background:#fef3c7;color:#92400e}.fs-muted{color:var(--fs-muted)}.fs-alert{padding:12px 14px;border-radius:10px;margin-bottom:12px;background:#ecfdf5;color:#065f46}.fs-error{background:#fef2f2;color:#991b1b}@media(max-width:760px){.fs-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.fs-title{font-size:23px}}
</style>
<header class="fs-head"><div><h1 class="fs-title">Products</h1><p class="fs-sub">Manage your physical catalogue, pricing and stock in one place.</p></div><a class="fs-btn fs-primary" href="/admin/page/favorite-shop-products?action=create">＋ Add product</a></header>
<?php if (!empty($flashSuccess)): ?><div class="fs-alert"><?= $e($flashSuccess) ?></div><?php endif ?>
<?php if (!empty($flashError)): ?><div class="fs-alert fs-error"><?= $e($flashError) ?></div><?php endif ?>
<div class="fs-stats">
<div class="fs-stat"><small>All products</small><strong><?= (int)($counts['all']??0) ?></strong></div>
<div class="fs-stat"><small>Published</small><strong><?= (int)($counts['published']??0) ?></strong></div>
<div class="fs-stat"><small>Drafts</small><strong><?= (int)($counts['draft']??0) ?></strong></div>
<div class="fs-stat"><small>Archived</small><strong><?= (int)($counts['archived']??0) ?></strong></div>
</div>
<section class="fs-panel">
<form class="fs-tools" method="get" action="/admin/page/favorite-shop-products">
<input type="search" name="q" value="<?= $e($search) ?>" placeholder="Search product name or SKU">
<select name="status"><option value="">All statuses</option><?php foreach(['published'=>'Published','draft'=>'Draft','archived'=>'Archived'] as $k=>$label): ?><option value="<?= $e($k) ?>" <?= $status===$k?'selected':'' ?>><?= $e($label) ?></option><?php endforeach ?></select>
<button class="fs-btn" type="submit">Filter</button><a class="fs-btn" href="/admin/page/favorite-shop-products">Reset</a>
</form>
<div class="fs-table-wrap"><table class="fs-table"><thead><tr><th>Product</th><th>Price</th><th>Selling unit</th><th>Stock</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if (!$products): ?><tr><td colspan="6" class="fs-muted">No products yet. Add your first product to start building the catalogue.</td></tr><?php endif ?>
<?php foreach($products as $p): ?><tr>
<td><div class="fs-product"><?php if(!empty($p['cover_image_url'])): ?><img class="fs-thumb" src="<?= $e($p['cover_image_url']) ?>" alt=""><?php else: ?><div class="fs-thumb"></div><?php endif ?><div><strong><?= $e($p['name']) ?></strong><div class="fs-muted">SKU: <?= $e($p['sku'] ?: '—') ?></div></div></div></td>
<td><strong><?= $money($p['sale_price_cents'] ?? $p['price_cents']) ?></strong><?php if($p['sale_price_cents']!==null): ?><div class="fs-muted"><s><?= $money($p['price_cents']) ?></s></div><?php endif ?></td>
<td><?= $e($p['unit_type']) ?><?= (float)$p['unit_quantity'] !== 1.0 ? ' · '.$e($p['unit_quantity']) : '' ?><?= !empty($p['unit_label']) ? ' '.$e($p['unit_label']) : '' ?></td>
<td><?= $e($p['stock_quantity']) ?><div class="fs-muted"><?= $e(str_replace('_',' ',$p['stock_status'])) ?></div></td>
<td><span class="fs-status <?= $e($p['status']) ?>"><?= $e(ucfirst($p['status'])) ?></span></td>
<td><a class="fs-btn" href="/admin/page/favorite-shop-products?action=edit&id=<?= (int)$p['id'] ?>">Edit</a></td>
</tr><?php endforeach ?>
</tbody></table></div></section></div>
