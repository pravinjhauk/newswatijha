<?php
namespace SwatiJha;
defined('ABSPATH') || exit;

final class Editorial {
    private static bool $promoting=false;
    public static function boot(): void {
        add_action('rest_api_init', [self::class,'routes']);
        add_filter('wp_insert_post_data',[self::class,'guard_post'],99,4);
        foreach(['add','update','delete'] as $operation) add_filter($operation.'_post_metadata',[self::class,'guard_meta'],99,5);
        add_filter('pre_trash_post',[self::class,'guard_delete'],10,2);
        add_filter('pre_delete_post',[self::class,'guard_delete'],10,2);
        add_action('post_updated',[self::class,'invalidate_approval'],10,3);
        add_action('set_object_terms',[self::class,'guard_terms'],99,6);
        foreach(['added','updated','deleted'] as $operation) add_action($operation.'_post_meta',[self::class,'changed_meta'],10,4);
        foreach(array_merge(['page','post'],array_keys(Model::TYPES)) as $type) add_filter('rest_pre_insert_'.$type,[self::class,'rest_guard'],99,2);
    }
    public static function clinical(int $id): bool { return isset(Model::TYPES[get_post_type($id)]) || (bool)Model::get($id,'clinical'); }
    public static function locked(int $id): bool { return self::clinical($id) && get_post_status($id)==='publish'; }
    public static function guard_terms(int $id,$terms,array $tt_ids,string $taxonomy,bool $append,array $old_tt_ids): void {
        if(self::$promoting || $taxonomy!=='sj_specialty') return;
        if(self::locked($id)) {
            global $wpdb;
            $old_ids=[];
            foreach($old_tt_ids as $tt_id) $old_ids[]=(int)$wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id = %d",$tt_id));
            self::internal(static fn()=>wp_set_object_terms($id,$old_ids,$taxonomy));
        } else self::internal(static fn()=>update_post_meta($id,'_sj_review_state','draft'));
    }
    public static function guard_delete($check,$post) { return self::locked($post->ID) ? false : $check; }
    public static function guard_post(array $data,array $postarr,array $unsanitized,bool $update): array {
        if(self::$promoting || ($data['post_type']??'')==='revision') return $data;
        $id=(int)($postarr['ID']??0); $old=$id?get_post($id):null;
        if($old && self::locked($id)) foreach(['post_title','post_content','post_excerpt','post_name','post_status','post_parent','post_date','post_date_gmt'] as $key) $data[$key]=wp_slash($old->$key);
        $clinical=isset(Model::TYPES[$data['post_type']??'']) || ($id && self::clinical($id)) || !empty($postarr['meta_input']['_sj_clinical']);
        if($clinical && ($data['post_status']??'')==='publish' && (!$old || $old->post_status!=='publish')) $data['post_status']='pending';
        return $data;
    }
    public static function guard_meta($check,int $id,string $key,$value,$unused=null) {
        if(self::$promoting || wp_is_post_revision($id) || !str_starts_with($key,'_sj_')) return $check;
        if(self::locked($id)) return false;
        if(in_array($key,['_sj_review_state','_sj_approved_hash','_sj_approved_by','_sj_origin_id','_sj_base_hash','_sj_audit','_sj_approved_revision_id'],true)) return false;
        if($key==='_sj_verification' && $value==='verified' && !current_user_can('sj_review_clinical')) return false;
        if(in_array($key,['_sj_credentials','_sj_roles'],true) && !current_user_can('sj_review_clinical')) foreach((array)$value as $item) if(($item['verification']??'')==='verified') return false;
        $valid=Model::validate($id,get_post_type($id),$key,$value);
        return is_wp_error($valid) ? false : $check;
    }
    public static function rest_guard($prepared,\WP_REST_Request $request) {
        if(is_wp_error($prepared)) return $prepared;
        $id=(int)$request['id'];
        if($id && self::locked($id)) return new \WP_Error('sj_published_locked','Create a clinical change draft to edit the published record.',['status'=>409]);
        if(($request['meta']['_sj_verification']??'')==='verified'&&!current_user_can('sj_review_clinical')) return new \WP_Error('sj_review_permission','Only a clinical reviewer can verify clinical facts.',['status'=>403]);
        if($request['status']==='publish' && (($id&&self::clinical($id))||isset(Model::TYPES[$prepared->post_type??''])||!empty($request['meta']['_sj_clinical']))) return new \WP_Error('sj_review_required','Use clinical review and release for this record.',['status'=>409]);
        return $prepared;
    }
    public static function snapshot(int $id): array {
        $post=get_post($id); $meta=[];
        foreach(array_keys(array_merge(Model::fields($post->post_type),Model::clinical_fields())) as $field) $meta['_sj_'.$field]=Model::get($id,$field);
        $terms=wp_get_object_terms($id,'sj_specialty',['fields'=>'ids']);
        ksort($meta);
        return ['title'=>$post->post_title,'content'=>$post->post_content,'excerpt'=>$post->post_excerpt,'slug'=>$post->post_name,'meta'=>$meta,'specialties'=>is_wp_error($terms)?[]:$terms];
    }
    public static function hash(int $id): string { return hash('sha256',wp_json_encode(self::snapshot($id))); }
    public static function internal(callable $action) { $previous=self::$promoting; self::$promoting=true; try { return $action(); } finally { self::$promoting=$previous; } }
    public static function invalidate_approval(int $id,\WP_Post $after,\WP_Post $before): void {
        if(!self::$promoting && self::clinical($id) && self::hash($id)!==Model::get($id,'approved_hash')) self::internal(static fn()=>update_post_meta($id,'_sj_review_state','draft'));
    }
    public static function changed_meta($meta_id,int $id,string $key,$value): void {
        Model::invalidate();
        if(!self::$promoting && str_starts_with($key,'_sj_') && !in_array($key,['_sj_review_state','_sj_audit'],true)) self::internal(static fn()=>update_post_meta($id,'_sj_review_state','draft'));
    }
    public static function routes(): void {
        register_rest_route('swatijha/v1','/editorial/(?P<id>\d+)/(?P<action>change|request|approve|release)',[
            'methods'=>'POST','permission_callback'=>static function($request) {
                $id=(int)$request['id']; $action=$request['action'];
                if(!get_post($id)||!current_user_can('edit_post',$id)) return false;
                return $action==='approve'?current_user_can('sj_review_clinical'):($action==='release'?current_user_can('sj_publish_clinical'):true);
            },'callback'=>static fn($request)=>self::act((int)$request['id'],$request['action'],$request->get_json_params()??[]),
        ]);
    }
    public static function act(int $id,string $action,array $input=[]) {
        if(!get_post($id)||!current_user_can('edit_post',$id)) return new \WP_Error('sj_forbidden','You cannot edit this record.',['status'=>403]);
        if($action==='approve'&&!current_user_can('sj_review_clinical')||$action==='release'&&!current_user_can('sj_publish_clinical')) return new \WP_Error('sj_forbidden','This action requires a clinical review or publishing capability.',['status'=>403]);
        if(!self::clinical($id)) return new \WP_Error('sj_not_clinical','Mark the page as clinical information first.',['status'=>400]);
        return self::internal(function() use($id,$action,$input) {
            if($action==='change') {
                if(get_post_status($id)!=='publish') return new \WP_Error('sj_not_published','Edit the existing draft.',['status'=>400]);
                $p=get_post($id); $clone=wp_insert_post(['post_type'=>$p->post_type,'post_status'=>'draft','post_title'=>$p->post_title,'post_content'=>$p->post_content,'post_excerpt'=>$p->post_excerpt,'post_author'=>get_current_user_id()],true);
                if(is_wp_error($clone)) return $clone;
                foreach(self::snapshot($id)['meta'] as $key=>$value) update_post_meta($clone,$key,$value);
                wp_set_object_terms($clone,self::snapshot($id)['specialties'],'sj_specialty');
                update_post_meta($clone,'_sj_origin_id',$id);
                update_post_meta($clone,'_sj_base_hash',self::hash($id));
                update_post_meta($clone,'_sj_review_state','draft');
                update_post_meta($clone,'_sj_proposed_slug',$p->post_name);
                return ['id'=>$clone,'edit_url'=>get_edit_post_link($clone,'raw'),'state'=>'draft'];
            }
            if(self::locked($id)) return new \WP_Error('sj_locked','Create a clinical change draft first.',['status'=>409]);
            if($action==='request') {
                update_post_meta($id,'_sj_review_state','awaiting'); self::audit($id,'requested');
                return ['id'=>$id,'state'=>'awaiting'];
            }
            if($action==='approve') {
                if(Model::get($id,'review_state')!=='awaiting') return new \WP_Error('sj_request_first','Request clinical review before approval.',['status'=>409]);
                $date=$input['reviewed_on']??''; $reviewer=(int)($input['reviewer_id']??0);
                if(!$date || is_wp_error(Model::validate($id,get_post_type($id),'_sj_medically_reviewed_on',$date))) return new \WP_Error('sj_review_date','Enter the actual medical review date.',['status'=>400]);
                if(!Model::verified($reviewer)||get_post_type($reviewer)!=='sj_clinician') {
                    // The initial clinician identity can be verified and reviewed in the same audited release.
                    if(get_post_type($id)!=='sj_clinician'||$reviewer!==$id||Model::get($id,'verification')!=='verified') return new \WP_Error('sj_reviewer','Choose a published verified clinician.',['status'=>400]);
                }
                if(isset(Model::TYPES[get_post_type($id)]) && (Model::get($id,'verification')!=='verified'||!Model::get($id,'source_url')||!Model::get($id,'verified_on'))) return new \WP_Error('sj_evidence','Verify the entity and supply its evidence source and verification date.',['status'=>400]);
                if(get_post_type($id)==='sj_treatment'&&(!Model::get($id,'category')||!Model::get($id,'schema_type'))) return new \WP_Error('sj_classification','Select and review the treatment category and schema classification.',['status'=>400]);
                update_post_meta($id,'_sj_reviewer_entity_id',$reviewer); update_post_meta($id,'_sj_medically_reviewed_on',$date);
                update_post_meta($id,'_sj_review_state','approved'); update_post_meta($id,'_sj_approved_hash',self::hash($id)); update_post_meta($id,'_sj_approved_by',get_current_user_id()); self::audit($id,'approved');
                return ['id'=>$id,'state'=>'approved'];
            }
            if($action==='release') {
                if(Model::get($id,'review_state')!=='approved'||!hash_equals((string)Model::get($id,'approved_hash'),self::hash($id))) return new \WP_Error('sj_stale_approval','The current content has not been approved.',['status'=>409]);
                $origin=(int)Model::get($id,'origin_id'); $target=$origin?:$id;
                if($origin && (!get_post($origin)||!hash_equals((string)Model::get($id,'base_hash'),self::hash($origin)))) return new \WP_Error('sj_conflict','The published record changed after this draft was created.',['status'=>409]);
                // Database advisory lock is an atomic option insertion and works on MySQL and SQLite.
                $lock='sj_release_lock_'.$target;
                if(!add_option($lock,time(),'',false)) return new \WP_Error('sj_busy','A release is already in progress.',['status'=>409]);
                global $wpdb;
                $wpdb->query('START TRANSACTION');
                try {
                    clean_post_cache($target);
                    if($origin && !hash_equals((string)Model::get($id,'base_hash'),self::hash($origin))) throw new \RuntimeException('The published record changed while acquiring the release lock.');
                    wp_save_post_revision($target); $snapshot=self::snapshot($id); $draft=get_post($id);
                    $result=wp_update_post(['ID'=>$target,'post_title'=>$draft->post_title,'post_content'=>$draft->post_content,'post_excerpt'=>$draft->post_excerpt,'post_status'=>'publish'],true);
                    if(is_wp_error($result)) throw new \RuntimeException($result->get_error_message());
                    if($origin) foreach($snapshot['meta'] as $key=>$value) update_post_meta($target,$key,$value);
                    wp_set_object_terms($target,$snapshot['specialties'],'sj_specialty');
                    foreach(['review_state','approved_by','medically_reviewed_on','reviewer_entity_id'] as $field) update_post_meta($target,'_sj_'.$field,Model::get($id,$field));
                    update_post_meta($target,'_sj_approved_hash',self::hash($target));
                    $revision=wp_save_post_revision($target);
                    if(!$revision) { $revisions=wp_get_post_revisions($target,['numberposts'=>1]); $revision=$revisions?(int)array_key_first($revisions):0; }
                    update_post_meta($target,'_sj_approved_revision_id',(int)$revision);
                    self::audit($target,'released');
                    if($origin) { wp_update_post(['ID'=>$id,'post_status'=>'private']); update_post_meta($id,'_sj_review_state','released'); }
                    if($wpdb->last_error) throw new \RuntimeException('Database error during release.');
                    $wpdb->query('COMMIT');
                    self::flag_dependants($target); Model::invalidate();
                    return ['id'=>$target,'state'=>'published','url'=>get_permalink($target)];
                } catch(\Throwable $error) { $wpdb->query('ROLLBACK'); clean_post_cache($target); return new \WP_Error('sj_release_failed','Release was rolled back. Review the local database health.',['status'=>500]); }
                finally { delete_option($lock); clean_post_cache($target); }
            }
            return new \WP_Error('sj_action','Unknown editorial action.',['status'=>400]);
        });
    }
    private static function audit(int $id,string $event): void {
        $log=Model::get($id,'audit',[]); $log[]=['event'=>$event,'user'=>get_current_user_id(),'at'=>gmdate('c'),'hash'=>self::hash($id)]; update_post_meta($id,'_sj_audit',$log);
    }
    private static function flag_dependants(int $id): void {
        foreach(get_posts(['post_type'=>['page','post'],'post_status'=>'publish','numberposts'=>1000]) as $page) {
            if($page->ID===$id) continue;
            $linked=array_merge((array)Model::get($page->ID,'author_entity_ids',[]),(array)Model::get($page->ID,'reference_ids',[]),[(int)Model::get($page->ID,'reviewer_entity_id')]);
            $bound=Model::bound($page->ID); if($bound) $linked[]=$bound->ID;
            $blocks=function(array $items) use (&$blocks,&$linked) { foreach($items as $item) { if(str_starts_with($item['blockName']??'','sj/')) $linked=array_merge($linked,$item['attrs']['entityIds']??[]); if(!empty($item['innerBlocks'])) $blocks($item['innerBlocks']); } };
            $blocks(parse_blocks($page->post_content));
            $queue=$linked; $seen=[];
            while($queue&&count($seen)<1000) {
                $next=(int)array_shift($queue); if(!$next||isset($seen[$next])) continue; $seen[$next]=true;
                foreach(Model::fields(get_post_type($next)?:'') as $field=>[$kind]) if(str_starts_with($kind,'ids:')||str_starts_with($kind,'id:')) $queue=array_merge($queue,(array)Model::get($next,$field,[]));
            }
            $linked=array_map('intval',array_keys($seen));
            if(in_array($id,$linked,true)) update_post_meta($page->ID,'_sj_review_state','rereview');
        }
    }
}
