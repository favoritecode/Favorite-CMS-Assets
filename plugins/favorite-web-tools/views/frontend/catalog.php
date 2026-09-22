<?php
/**
 * Frontend Tool Catalog View
 *
 * Variables:
 * - $tools            : array<Tool>
 * - $total            : int
 * - $page             : int
 * - $perPage          : int
 * - $totalPages       : int
 * - $categories       : array<ToolCategory>
 * - $categoryCounts   : array<int, int>
 * - $selectedCategory : ?ToolCategory
 * - $currentSearch    : string
 * - $currentCategory  : string
 * - $currentEngine    : string
 * - $currentAccess    : string
 * - $currentSort      : string
 * - $userId           : int
 * - $accessControl    : AccessControlService
 */
?>
<div class="fwt-catalog-wrap" style="max-width: 1200px; margin: 0 auto; padding: 32px 16px;">
    <!-- Catalog Header / Search Hero -->
    <div class="fwt-hero-section" style="text-align: center; margin-bottom: 40px; padding: 20px 0;">
        <h1 class="fwt-hero-title" style="font-size: 36px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 12px;">
            <?php echo $selectedCategory ? htmlspecialchars($selectedCategory->name, ENT_QUOTES, 'UTF-8') . ' Tools' : 'Developer & Web Tools'; ?>
        </h1>
        <p class="fwt-hero-subtitle" style="font-size: 17px; color: var(--text-muted, #64748b); max-width: 600px; margin: 0 auto 28px;">
            <?php echo $selectedCategory ? htmlspecialchars($selectedCategory->description ?? '', ENT_QUOTES, 'UTF-8') : 'Fast, reliable, free online developer utilities, formatters, converters, and generators.'; ?>
        </p>

        <!-- Search Bar -->
        <form method="GET" action="/tools" class="fwt-search-form" style="max-width: 580px; margin: 0 auto; position: relative;">
            <?php if (!empty($currentCategory)): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($currentCategory, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 16px; color: #94a3b8; font-size: 18px; pointer-events: none;">🔍</span>
                <input type="text" name="search" id="fwt-catalog-search-input"
                       value="<?php echo htmlspecialchars($currentSearch, ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="Search tools by name, tag, or description..."
                       autocomplete="off"
                       style="width: 100%; padding: 14px 44px 14px 46px; font-size: 16px; border: 2px solid var(--border-color, #e2e8f0); border-radius: 50px; background: var(--card-bg, #ffffff); color: var(--text-main, inherit); box-shadow: 0 4px 12px rgba(0,0,0,0.04); outline: none; transition: all 0.2s ease;">
                <?php if ($currentSearch !== ''): ?>
                    <a href="/tools<?php echo $currentCategory !== '' ? '?category=' . urlencode($currentCategory) : ''; ?>"
                       style="position: absolute; right: 16px; color: #94a3b8; text-decoration: none; font-size: 18px; font-weight: bold;" title="Clear search">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category Filter Chips -->
    <?php
    $allToolsCount = isset($totalAllTools) ? (int)$totalAllTools : (int)$total;
    ?>
    <div class="fwt-category-chips">
        <a href="/tools<?php echo $currentSearch !== '' ? '?search=' . urlencode($currentSearch) : ''; ?>"
           class="fwt-chip <?php echo empty($currentCategory) ? 'fwt-chip--active' : ''; ?>">
            <?php echo \FavoriteCMS\Tools\Support\IconRenderer::render('grid', 'fwt-cat-icon'); ?>
            <span>All Tools</span>
            <span class="fwt-chip-count"><?php echo $allToolsCount; ?></span>
        </a>

        <?php foreach ($categories as $cat): ?>
            <?php $isActiveCat = ($currentCategory === $cat->slug); ?>
            <a href="/tools?category=<?php echo urlencode($cat->slug); ?><?php echo $currentSearch !== '' ? '&search=' . urlencode($currentSearch) : ''; ?>"
               class="fwt-chip <?php echo $isActiveCat ? 'fwt-chip--active' : ''; ?>">
                <?php echo \FavoriteCMS\Tools\Support\IconRenderer::render($cat->icon ?: $cat->slug, 'fwt-cat-icon'); ?>
                <span><?php echo htmlspecialchars($cat->name, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php if (isset($categoryCounts[$cat->id])): ?>
                    <span class="fwt-chip-count"><?php echo (int)$categoryCounts[$cat->id]; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Secondary Filters & Sort Bar -->
    <div class="fwt-filter-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color, #e2e8f0);">
        <div style="font-size: 14px; color: var(--text-muted, #64748b);">
            Showing <strong><?php echo count($tools); ?></strong> of <strong><?php echo (int)$total; ?></strong> tools
            <?php if ($currentSearch !== ''): ?>
                for "<em><?php echo htmlspecialchars($currentSearch, ENT_QUOTES, 'UTF-8'); ?></em>"
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Access Mode Filter -->
            <select onchange="location.href=this.value;" style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color, #cbd5e1); border-radius: 6px; background: var(--card-bg, #fff); color: inherit;">
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['access_mode' => ''])); ?>">All Access Modes</option>
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['access_mode' => 'FREE'])); ?>" <?php echo $currentAccess === 'FREE' ? 'selected' : ''; ?>>Free Only</option>
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['access_mode' => 'LOGIN_REQUIRED'])); ?>" <?php echo $currentAccess === 'LOGIN_REQUIRED' ? 'selected' : ''; ?>>Login Required</option>
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['access_mode' => 'MEMBERSHIP_REQUIRED'])); ?>" <?php echo $currentAccess === 'MEMBERSHIP_REQUIRED' ? 'selected' : ''; ?>>Membership Required</option>
            </select>

            <!-- Sort Filter -->
            <select onchange="location.href=this.value;" style="padding: 6px 12px; font-size: 13px; border: 1px solid var(--border-color, #cbd5e1); border-radius: 6px; background: var(--card-bg, #fff); color: inherit;">
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['sort' => 'order'])); ?>" <?php echo $currentSort === 'order' ? 'selected' : ''; ?>>Featured Order</option>
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['sort' => 'name'])); ?>" <?php echo $currentSort === 'name' ? 'selected' : ''; ?>>Name (A-Z)</option>
                <option value="<?php echo '/tools?' . http_build_query(array_merge($_GET, ['sort' => 'newest'])); ?>" <?php echo $currentSort === 'newest' ? 'selected' : ''; ?>>Newest Added</option>
            </select>

            <?php if ($currentSearch !== '' || $currentCategory !== '' || $currentEngine !== '' || $currentAccess !== ''): ?>
                <a href="/tools" style="font-size: 13px; color: var(--primary, #2563eb); text-decoration: none; font-weight: 500;">Clear All</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tool Grid -->
    <?php if (empty($tools)): ?>
        <div class="fwt-empty-state" style="text-align: center; padding: 60px 20px; background: var(--card-bg, #f8fafc); border-radius: 16px; border: 1px dashed var(--border-color, #cbd5e1); margin: 30px 0;">
            <div style="font-size: 48px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 8px;">No tools found</h3>
            <p style="color: var(--text-muted, #64748b); font-size: 15px; margin-bottom: 20px;">
                No tools match your current search or filter criteria. Try adjusting your query.
            </p>
            <a href="/tools" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 8px; background: var(--primary, #2563eb); color: #fff; text-decoration: none; font-weight: 600; font-size: 14px;">
                Reset Filters
            </a>
        </div>
    <?php else: ?>
        <div class="fwt-tool-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <?php foreach ($tools as $tool): ?>
                <?php
                $toolCat = null;
                if ($tool->category_id !== null) {
                    foreach ($categories as $c) {
                        if ($c->id === $tool->category_id) {
                            $toolCat = $c;
                            break;
                        }
                    }
                }
                ?>
                <div class="fwt-card" style="display: flex; flex-direction: column; justify-content: space-between; background: var(--card-bg, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; padding: 22px; transition: transform 0.15s ease, box-shadow 0.15s ease; position: relative;">
                    <div>
                        <!-- Top Meta: Category & Access Badge -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                            <?php if ($toolCat): ?>
                                <span style="font-size: 12px; font-weight: 600; color: var(--text-muted, #64748b); text-transform: uppercase; letter-spacing: 0.05em;">
                                    <?php echo htmlspecialchars($toolCat->name, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>

                            <!-- Access Badge -->
                            <?php if ($tool->access_mode === 'FREE'): ?>
                                <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; background: #e6f4ea; color: #137333;">Free</span>
                            <?php elseif ($tool->access_mode === 'LOGIN_REQUIRED'): ?>
                                <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; background: #e8f0fe; color: #1a73e8;">Login Required</span>
                            <?php elseif ($tool->access_mode === 'MEMBERSHIP_REQUIRED'): ?>
                                <span style="font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; background: #fef7e0; color: #b06000;">⭐ Member</span>
                            <?php endif; ?>
                        </div>

                        <!-- Icon & Title -->
                        <div style="display: flex; gap: 14px; align-items: flex-start; margin-bottom: 12px;">
                            <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--icon-bg, #eff6ff); display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
                                <?php echo \FavoriteCMS\Tools\Support\IconRenderer::render($tool->icon ?: '🛠️', 'fwt-card-icon', 22); ?>
                            </div>
                            <div>
                                <h3 style="font-size: 17px; font-weight: 700; margin: 0 0 4px 0; line-height: 1.3;">
                                    <a href="/tools/<?php echo urlencode($tool->slug); ?>" style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($tool->name, ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </h3>
                                <div style="font-size: 11px; color: var(--text-muted, #94a3b8);">
                                    Engine: <?php echo htmlspecialchars($tool->engine, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p style="font-size: 14px; color: var(--text-muted, #64748b); line-height: 1.5; margin: 0 0 20px 0;">
                            <?php echo htmlspecialchars($tool->description ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>

                    <!-- Footer Action -->
                    <div style="padding-top: 14px; border-top: 1px solid var(--border-color, #f1f5f9); display: flex; justify-content: space-between; align-items: center;">
                        <a href="/tools/<?php echo urlencode($tool->slug); ?>" class="fwt-card-action" style="font-size: 14px; font-weight: 600; color: var(--primary, #2563eb); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <span>Use Tool</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="fwt-pagination" style="display: flex; justify-content: center; gap: 8px; align-items: center; margin-top: 30px;">
                <?php if ($page > 1): ?>
                    <a href="/tools?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" style="padding: 8px 14px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; text-decoration: none; color: inherit; font-size: 14px;">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p === $page): ?>
                        <span style="padding: 8px 14px; border-radius: 6px; background: var(--primary, #2563eb); color: #fff; font-weight: 600; font-size: 14px;">
                            <?php echo $p; ?>
                        </span>
                    <?php elseif ($p <= 3 || $p >= $totalPages - 1 || abs($p - $page) <= 1): ?>
                        <a href="/tools?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>" style="padding: 8px 14px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; text-decoration: none; color: inherit; font-size: 14px;">
                            <?php echo $p; ?>
                        </a>
                    <?php elseif ($p === 4 && $page > 4): ?>
                        <span style="color: #94a3b8;">…</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="/tools?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" style="padding: 8px 14px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; text-decoration: none; color: inherit; font-size: 14px;">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

