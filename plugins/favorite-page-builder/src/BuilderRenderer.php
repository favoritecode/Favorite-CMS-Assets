<?php
declare(strict_types=1);
namespace FavoriteCMS\PageBuilder;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Themes\BuilderElementRegistry;
final class BuilderRenderer {
    public function __construct(private Database $db) {}
    public function render(array $doc): string {
        $out='<div class="fpb-page">';
        foreach(array_slice((array)($doc['sections']??[]),0,100) as $section) {
            if(!is_array($section))continue;
            $out.='<section class="fpb-section" style="'.$this->style((array)($section['settings']??[])).'"><div class="fpb-container">';
            $cols=(array)($section['columns']??[]);
            if(!$cols && isset($section['rows'][0]['columns']))$cols=(array)$section['rows'][0]['columns'];
            $out.='<div class="fpb-row">';
            foreach(array_slice($cols,0,6) as $col) {
                if(!is_array($col))continue;
                $out.='<div class="fpb-col" style="flex-basis:'.max(10,min(100,(int)($col['width']??100))).'%;max-width:'.max(10,min(100,(int)($col['width']??100))).'%">';
                foreach(array_slice((array)($col['elements']??[]),0,100) as $el)if(is_array($el))$out.=$this->element($el);
                $out.='</div>';
            }
            $out.='</div></div></section>';
        }
        return $out.'</div>';
    }
    private function element(array $el): string {
        $type=(string)($el['type']??'');$s=(array)($el['settings']??[]);
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
        switch($type) {
            case 'heading': $tag=in_array(($s['tag']??'h2'),['h1','h2','h3','h4','h5','h6'],true)?$s['tag']:'h2';return '<'.$tag.' class="fpb-heading" style="'.$this->textStyle($s).'">'.$e($s['text']??'Your headline').'</'.$tag.'>';
            case 'text': return '<div class="fpb-text" style="'.$this->textStyle($s).'">'.nl2br($e($s['text']??'' )).'</div>';
            case 'image': $url=$this->safeUrl((string)($s['url']??''));return $url?'<figure class="fpb-image"><img src="'.$e($url).'" alt="'.$e($s['alt']??'').'" style="border-radius:'.$this->length($s['radius']??'0px').'"></figure>':'';
            case 'button': $url=$this->safeUrl((string)($s['url']??'#'))??'#';return '<div class="fpb-button-wrap"><a class="fpb-button" href="'.$e($url).'" style="background:'.$this->color($s['background']??'#2563eb').';color:'.$this->color($s['color']??'#fff').'">'.$e($s['text']??'Learn more').'</a></div>';
            case 'divider':return '<hr class="fpb-divider" style="border-color:'.$this->color($s['color']??'#e2e8f0').'">';
            case 'spacer':return '<div aria-hidden="true" style="height:'.$this->length($s['height']??'32px').'"></div>';
            case 'post_grid':return $this->postGrid($s);
            case 'product_grid':return $this->productGrid($s);
            case 'checkout_cta': $url=$this->safeUrl((string)($s['checkout_url']??''));if(!$url && (int)($s['product_id']??0)>0)$url=site_path('/checkout?product_id='.(int)$s['product_id']);return '<div class="fpb-button-wrap"><a class="fpb-button" href="'.$e($url??'#').'">'.$e($s['text']??'Order now').'</a></div>';
            case 'order_confirmation':return '<section class="fpb-confirmation"><div class="fpb-confirm-icon">✓</div><h2>'.$e($s['heading']??'Thank you for your order').'</h2><p>'.$e($s['text']??'Your order has been received.').'</p><p class="fpb-muted">Order details are displayed by the active checkout integration when supported.</p></section>';
            case 'html': return '<div class="fpb-custom-html">'.\FavoriteCMS\Themes\BuilderElementRegistry::sanitizeHtml((string)($s['html']??'')).'</div>';
            default: return '';
        }
    }
    private function postGrid(array $s): string {
        try {
            $limit=max(1,min(24,(int)($s['limit']??6)));$mode=(string)($s['mode']??'recent');
            $sql="SELECT id,title,slug,excerpt,featured_image_id FROM `posts` WHERE status='published'";
            $args=[];
            if($mode==='category' || $mode==='tag') {
                $term=trim((string)($s['term']??''));
                if($term!=='') {
                    $tax=$mode==='category'?'category':'tag';
                    $sql="SELECT DISTINCT p.id,p.title,p.slug,p.excerpt,p.featured_image_id FROM `posts` p JOIN `post_taxonomies` pt ON pt.post_id=p.id JOIN `taxonomies` t ON t.id=pt.taxonomy_id WHERE p.status='published' AND t.type=? AND (t.slug=? OR t.name=?)";
                    $args=[$tax,$term,$term];
                }
            }
            $sql.=' ORDER BY id DESC LIMIT '.$limit;$rows=$this->db->select($sql,$args);
            if(!$rows)return '<p class="fpb-empty">No posts found.</p>';
            $cols=max(1,min(4,(int)($s['columns']??3)));$out='<div class="fpb-grid fpb-grid-'.$cols.'">';
            foreach($rows as $r){$title=htmlspecialchars((string)$r->title,ENT_QUOTES,'UTF-8');$url=site_path('/post/'.rawurlencode((string)$r->slug));$out.='<article class="fpb-card"><h3><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">'.$title.'</a></h3><p>'.htmlspecialchars(mb_substr(strip_tags((string)($r->excerpt??'')),0,160),ENT_QUOTES,'UTF-8').'</p></article>';}
            return $out.'</div>';
        } catch(\Throwable){return '<p class="fpb-empty">Post grid is not available.</p>';}
    }
    private function productGrid(array $s): string {
        try {
            if(!$this->db->tableExists('favorite_digital_products'))return '<p class="fpb-empty">Activate a compatible product plugin to show products.</p>';
            $limit=max(1,min(24,(int)($s['limit']??6)));$id=(int)($s['product_id']??0);
            $sql="SELECT id,name,slug,short_description,price,cover_image_url FROM `favorite_digital_products` WHERE status='published'";
            $args=[];
            if($id>0){$sql.=' AND id=?';$args[]=$id;}
            $cat=trim((string)($s['category']??''));if($cat!==''){$sql.=' AND category_id=?';$args[]=(int)$cat;}
            $sql.=' ORDER BY id DESC LIMIT '.$limit;$rows=$this->db->select($sql,$args);
            if(!$rows)return '<p class="fpb-empty">No matching products found.</p>';
            $cols=max(1,min(4,(int)($s['columns']??3)));$out='<div class="fpb-grid fpb-grid-'.$cols.'">';
            foreach($rows as $r){$title=htmlspecialchars((string)($r->name??'Product'),ENT_QUOTES,'UTF-8');$slug=rawurlencode((string)($r->slug??''));$url=site_path('/product/'.$slug);$price=htmlspecialchars((string)($r->price??''),ENT_QUOTES,'UTF-8');$img=(string)($r->cover_image_url??'');$out.='<article class="fpb-card">'.($img!==''?'<img loading="lazy" src="'.htmlspecialchars($img,ENT_QUOTES,'UTF-8').'" alt="'.$title.'">':'').'<h3><a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'">'.$title.'</a></h3><p>'.$price.'</p></article>';}
            return $out.'</div>';
        } catch(\Throwable){return '<p class="fpb-empty">Product grid is not available.</p>';}
    }
    private function textStyle(array $s): string {return 'color:'.$this->color($s['color']??'#172033').';font-size:'.$this->length($s['size']??'16px').';text-align:'.(in_array(($s['alignment']??'left'),['left','center','right'],true)?$s['alignment']:'left').';';}
    private function style(array $s): string {return 'padding:'.$this->length($s['padding']??'48px').' '.$this->length($s['padding_x']??'20px').';background:'.$this->color($s['background']??'#ffffff').';';}
    private function length(mixed $v): string {$v=(string)$v;return preg_match('/^(0|\d+(\.\d+)?(px|rem|em|%|vw|vh))$/i',$v)?$v:'0px';}
    private function color(mixed $v): string {$v=(string)$v;return preg_match('/^(#[0-9a-f]{3,8}|rgba?\([0-9.,%\s]+\)|[a-z]{3,20})$/i',$v)?$v:'#172033';}
    private function safeUrl(string $v): ?string {$v=trim($v);if($v==='' )return null;if(str_starts_with($v,'/'))return $v;return preg_match('#^https?://#i',$v)?$v:null;}
}
