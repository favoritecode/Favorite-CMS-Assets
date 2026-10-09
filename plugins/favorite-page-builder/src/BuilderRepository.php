<?php
declare(strict_types=1);
namespace FavoriteCMS\PageBuilder;
use FavoriteCMS\Core\Database;
final class BuilderRepository {
    public function __construct(private Database $db) { $this->db->registerPrefixableTables(['favorite_page_builder_pages']); }
    public function ensureSchema(): void {
        $sqlite=$this->db->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='sqlite';
        $id=$sqlite?'INTEGER PRIMARY KEY AUTOINCREMENT':'BIGINT AUTO_INCREMENT PRIMARY KEY';
        $suffix=$sqlite?'':' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $this->db->execute("CREATE TABLE IF NOT EXISTS `favorite_page_builder_pages` (`id` {$id},`title` VARCHAR(255) NOT NULL,`slug` VARCHAR(190) NOT NULL UNIQUE,`page_type` VARCHAR(24) NOT NULL DEFAULT 'landing',`document_json` LONGTEXT NOT NULL,`published` TINYINT(1) NOT NULL DEFAULT 0,`updated_by` BIGINT NULL,`created_at` DATETIME NOT NULL,`updated_at` DATETIME NOT NULL){$suffix}");
    }
    public function all(): array {
        $rows=$this->db->select('SELECT * FROM `favorite_page_builder_pages` ORDER BY updated_at DESC,id DESC');
        return array_map(fn($r)=>$this->shape((array)$r),$rows);
    }
    public function find(int $id): ?array {
        $r=$this->db->selectOne('SELECT * FROM `favorite_page_builder_pages` WHERE id=? LIMIT 1',[$id]);
        return $r?$this->shape((array)$r):null;
    }
    public function findBySlug(string $slug): ?array {
        $r=$this->db->selectOne('SELECT * FROM `favorite_page_builder_pages` WHERE slug=? LIMIT 1',[$slug]);
        return $r?$this->shape((array)$r):null;
    }
    public function save(int $id,string $title,string $slug,string $type,array $doc,int $user): array {
        $now=date('Y-m-d H:i:s'); $json=json_encode($doc,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $published=!empty($doc['published'])?1:0;
        $data=['title'=>$title,'slug'=>$slug,'page_type'=>$type,'document_json'=>$json,'published'=>$published,'updated_by'=>$user,'updated_at'=>$now];
        if($id>0 && $this->find($id)) { $this->db->update('favorite_page_builder_pages',$data,['id'=>$id]); }
        else { $data['created_at']=$now; $id=$this->db->insert('favorite_page_builder_pages',$data); }
        return $this->find($id) ?? throw new \RuntimeException('Saved page could not be reloaded.');
    }
    public function delete(int $id): void { $this->db->delete('favorite_page_builder_pages',['id'=>$id]); }
    private function shape(array $r): array {
        $doc=json_decode((string)($r['document_json']??''),true);
        return ['id'=>(int)$r['id'],'title'=>(string)$r['title'],'slug'=>(string)$r['slug'],'page_type'=>(string)$r['page_type'],'published'=>(bool)$r['published'],'updated_at'=>(string)$r['updated_at'],'document'=>is_array($doc)?$doc:[]];
    }
}
