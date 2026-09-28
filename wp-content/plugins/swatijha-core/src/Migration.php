<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
final class Migration {
    public static function boot(): void {
        if(defined('WP_CLI')&&WP_CLI) {
            \WP_CLI::add_command('sj export',static function($args,$assoc){ if(!current_user_can('sj_migrate')) \WP_CLI::error('Use --user with an authorised migration operator.'); \WP_CLI::line(wp_json_encode(self::export(),JSON_PRETTY_PRINT)); });
            \WP_CLI::add_command('sj migrate',static function($args,$assoc){
                if(empty($assoc['manifest'])) \WP_CLI::error('Supply --manifest=/local/path.json.');
                $manifest=json_decode(file_get_contents($assoc['manifest']),true);
                $result=self::run($manifest??[],!isset($assoc['apply']));
                if(is_wp_error($result)) \WP_CLI::error($result->get_error_message());
                \WP_CLI::line(wp_json_encode($result,JSON_PRETTY_PRINT));
            });
        }
    }
    public static function export(): array {
        $records=[];
        foreach(Model::catalogue(false) as $p) {
            if(Model::get($p->ID,'origin_id')) continue;
            $meta=[];
            foreach(Model::fields($p->post_type) as $field=>[$kind]) {
                $value=Model::get($p->ID,$field);
                if(str_starts_with($kind,'ids:')) $value=array_map(static fn($id)=>Model::get((int)$id,'uuid'),(array)$value);
                elseif(str_starts_with($kind,'id:')&&$value) $value=['source_id'=>(int)$value,'uuid'=>Model::get((int)$value,'uuid'),'url'=>get_permalink((int)$value)];
                $meta[$field]=$value;
            }
            $records[]=['uuid'=>Model::get($p->ID,'uuid'),'type'=>$p->post_type,'title'=>$p->post_title,'content'=>$p->post_content,'meta'=>$meta];
        }
        return ['version'=>1,'exported_at'=>gmdate('c'),'records'=>$records];
    }
    public static function run(array $manifest,bool $dry=true) {
        if(!current_user_can('sj_migrate')) return new \WP_Error('sj_permission','Migration permission is required.');
        if(wp_get_environment_type()==='production') return new \WP_Error('sj_live_blocked','This importer is restricted to local or staging environments.');
        if(($manifest['version']??0)!==1||empty($manifest['approval']['approved_by'])||empty($manifest['approval']['approved_on'])||empty($manifest['url_map'])) return new \WP_Error('sj_map_pending','An explicitly approved complete URL map is required before migration, including dry runs.');
        foreach($manifest['url_map'] as $row) if(($row['approval']??'')!=='APPROVED'||empty($row['old_url'])||!in_array($row['action']??'',['KEEP','301','410'],true)||(($row['action']??'')!=='410'&&empty($row['new_url']))) return new \WP_Error('sj_map_pending','Every URL-map row needs an approved disposition.');
        $records=$manifest['records']??[]; $uuids=[]; $mapping=[];
        foreach(Model::catalogue(false) as $p) if(!Model::get($p->ID,'origin_id')) $mapping[Model::get($p->ID,'uuid')]=$p->ID;
        foreach($records as $record) {
            $uuid=$record['uuid']??'';
            if(!wp_is_uuid($uuid)||isset($uuids[$uuid])||!isset(Model::TYPES[$record['type']??''])||empty($record['title'])) return new \WP_Error('sj_manifest','Invalid or duplicate entity in manifest.');
            if(preg_match('/elementor|\[vc_|<script|liquid-footer/i',$record['content']??'')) return new \WP_Error('sj_legacy_markup','Transform legacy builder markup into reviewed native content first.');
            if(isset($mapping[$uuid])&&get_post_status($mapping[$uuid])==='publish') return new \WP_Error('sj_published_import','Imports cannot overwrite published records.');
            $uuids[$uuid]=true;
        }
        $plan=['dry_run'=>$dry,'count'=>count($records),'creates'=>count(array_diff_key($uuids,$mapping)),'updates'=>count(array_intersect_key($uuids,$mapping)),'writes'=>[]];
        if($dry) return $plan;
        return Editorial::internal(function() use($records,$mapping,$plan) {
            global $wpdb; $wpdb->query('START TRANSACTION'); $created=[]; $before=[];
            try {
                foreach($records as $record) {
                    $id=$mapping[$record['uuid']]??0; if($id) $before[$id]=Editorial::snapshot($id);
                    $saved=wp_insert_post(['ID'=>$id,'post_type'=>$record['type'],'post_status'=>'draft','post_title'=>sanitize_text_field($record['title']),'post_content'=>wp_kses_post($record['content']??'')],true);
                    if(is_wp_error($saved)) throw new \RuntimeException($saved->get_error_message());
                    if(!$id) $created[]=$saved; $mapping[$record['uuid']]=$saved; update_post_meta($saved,'_sj_uuid',$record['uuid']);
                }
                foreach($records as $record) {
                    $id=$mapping[$record['uuid']];
                    foreach(($record['meta']??[]) as $field=>$value) {
                        $kind=Model::fields($record['type'])[$field][0]??null;
                        if(!$kind||in_array($field,['uuid','verification'],true)) continue;
                        if(str_starts_with($kind,'ids:')) $value=array_map(static function($uuid) use($mapping){ if(!isset($mapping[$uuid])) throw new \RuntimeException('Unresolved relationship UUID.'); return $mapping[$uuid]; },$value);
                        elseif(str_starts_with($kind,'id:')&&is_array($value)) { $uuid=$value['uuid']??''; if(!$uuid||!isset($mapping[$uuid])) throw new \RuntimeException('Page/media bindings require an explicit approved local mapping.'); $value=$mapping[$uuid]; }
                        $valid=Model::validate($id,$record['type'],'_sj_'.$field,$value); if(is_wp_error($valid)) throw new \RuntimeException($valid->get_error_message());
                        update_post_meta($id,'_sj_'.$field,Model::sanitize($value));
                    }
                    update_post_meta($id,'_sj_verification','unverified'); update_post_meta($id,'_sj_review_state','draft'); update_post_meta($id,'_sj_import_source_hash',hash('sha256',wp_json_encode($record)));
                }
                if($wpdb->last_error) throw new \RuntimeException('Database write failed.');
                $wpdb->query('COMMIT'); Model::invalidate();
                return array_merge($plan,['writes'=>array_values($mapping),'rollback'=>['created_ids'=>$created,'before'=>$before]]);
            } catch(\Throwable $error) { $wpdb->query('ROLLBACK'); foreach(array_values($mapping) as $id) clean_post_cache($id); return new \WP_Error('sj_import_failed',$error->getMessage()); }
        });
    }
}
