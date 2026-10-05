<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
final class Admin {
    public static function boot(): void {
        add_action('admin_menu',[self::class,'menu']);
        add_action('admin_init',[self::class,'settings']);
        add_action('enqueue_block_editor_assets',[self::class,'editor']);
        foreach(['show_user_profile','edit_user_profile'] as $hook) add_action($hook,[self::class,'user_link_field']);
        foreach(['personal_options_update','edit_user_profile_update'] as $hook) add_action($hook,[self::class,'save_user_link']);
        add_action('rest_api_init',[self::class,'routes']);
        add_filter('rest_pre_dispatch',static function($result,$server,$request) {
            if(preg_match('~^/wp/v2/sj_~',$request->get_route())&&!current_user_can('sj_edit_entities')) return new \WP_Error('sj_private','Entity administration requires permission.',['status'=>403]);
            return $result;
        },10,3);
        add_action('admin_notices',static function() { if(defined('RANK_MATH_VERSION')||defined('WPSEO_VERSION')) echo '<div class="notice notice-error"><p>Swati Jha Core owns canonical and structured data. Configure one metadata owner before acceptance; another SEO plugin is active.</p></div>'; });
    }
    public static function menu(): void {
        add_menu_page('Practice content','Practice content','sj_edit_entities','sj-practice',[self::class,'screen'],'dashicons-heart',25);
        add_submenu_page('sj-practice','Practice settings','Practice settings','manage_options','sj-settings',[self::class,'settings_screen']);
    }
    public static function screen(): void {
        echo '<div class="wrap"><h1>Practice content</h1><p>Maintain reusable clinical records here. Pages retain their public addresses. Use a clinical change draft for updates to published clinical information.</p><ul>';
        foreach(Model::TYPES as $type=>$label) echo '<li><a href="'.esc_url(admin_url('edit.php?post_type='.$type)).'">'.esc_html($label).'</a></li>';
        echo '</ul><p>Content migration is locked until the URL map is explicitly approved. Enquiry delivery is disabled until a verified transport is configured.</p></div>';
    }
    public static function settings(): void {
        register_setting('sj_practice','sj_practice',['type'=>'object','sanitize_callback'=>static function($input) {
            $old=Graph::settings(); $out=['version'=>1];
            foreach(['origin','telephone','email','practice_name','practice_source_url','practice_verified_on'] as $key) $out[$key]=sanitize_text_field($input[$key]??'');
            if(!preg_match('~^https://[a-z0-9.-]+$~i',rtrim($out['origin'],'/'))) { add_settings_error('sj_practice','origin','Use the verified HTTPS canonical origin.'); return $old; }
            $out['origin']=rtrim($out['origin'],'/'); $out['email']=sanitize_email($out['email']);
            foreach(['primary_clinician','contact_page','booking_page'] as $key) $out[$key]=absint($input[$key]??0);
            $out['practice_verified']=!empty($input['practice_verified'])&&current_user_can('sj_review_clinical');
            $out['allow_delegated_review']=!empty($input['allow_delegated_review']);
            if($out['practice_verified']&&(!wp_http_validate_url($out['practice_source_url'])||!$out['practice_verified_on']||is_wp_error(Model::validate(0,'sj_clinician','_sj_verified_on',$out['practice_verified_on'])))) { add_settings_error('sj_practice','evidence','Verified practice identity needs a valid evidence URL and actual verification date.'); return $old; }
            return $out;
        }]);
    }
    public static function settings_screen(): void {
        if(!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>Practice settings</h1><form method="post" action="options.php">'; settings_fields('sj_practice');
        foreach(['origin'=>'Production canonical origin','practice_name'=>'Verified practice name','telephone'=>'Public telephone','email'=>'Public email','practice_source_url'=>'Practice identity evidence URL','practice_verified_on'=>'Practice verification date (YYYY-MM-DD)'] as $key=>$label) echo '<p><label>'.esc_html($label).'<br><input class="regular-text" name="sj_practice['.esc_attr($key).']" value="'.esc_attr(Graph::settings()[$key]??'').'"></label></p>';
        echo '<p><label><input type="checkbox" name="sj_practice[practice_verified]" value="1" '.checked(Graph::settings()['practice_verified']??false,true,false).'> Practice identity verified for structured data</label></p>';
        echo '<p><label><input type="checkbox" name="sj_practice[allow_delegated_review]" value="1" '.checked(Graph::settings()['allow_delegated_review']??false,true,false).'> Allow delegated review attestation</label><br><span class="description">Off: a clinical reviewer can approve only as the clinician linked to their own account. On: a reviewer may record, with a written attestation, a review carried out by another clinician. Every approval records which route was used.</span></p>';
        foreach(['primary_clinician'=>['sj_clinician','Primary clinician'],'contact_page'=>['page','Contact page'],'booking_page'=>['page','Booking page']] as $key=>[$type,$label]) {
            echo '<p><label>'.esc_html($label).'<br><select name="sj_practice['.esc_attr($key).']"><option value="0">Not configured</option>';
            foreach(get_posts(['post_type'=>$type,'numberposts'=>1000]) as $post) echo '<option value="'.(int)$post->ID.'" '.selected(Graph::settings()[$key]??0,$post->ID,false).'>'.esc_html($post->post_title).'</option>';
            echo '</select></label></p>';
        }
        submit_button(); echo '</form></div>';
    }
    public static function editor(): void {
        wp_enqueue_script('sj-editor');
        $post=get_post();
        // Reviewer and review date are written by the approval action only, never typed into the sidebar.
        $fields=$post?array_diff_key(array_merge(Model::fields($post->post_type),Model::clinical_fields()),array_flip(Model::WORKFLOW_FIELDS)):[];
        wp_add_inline_script('sj-editor','window.sjEditor='.wp_json_encode(['fields'=>$fields,'blocks'=>Blocks::BLOCKS,'canReview'=>current_user_can('sj_review_clinical'),'canPublish'=>current_user_can('sj_publish_clinical'),'canOverride'=>current_user_can('manage_options'),'linkedClinician'=>Editorial::linked_clinician(),'allowDelegated'=>!empty(Graph::settings()['allow_delegated_review'])]).';','before');
    }
    public static function user_link_field(\WP_User $user): void {
        if(!current_user_can('manage_options')) return;
        $current=Editorial::linked_clinician($user->ID);
        echo '<h2>Clinical reviewer identity</h2><table class="form-table"><tr><th><label for="sj_clinician_id">Linked clinician record</label></th><td><select id="sj_clinician_id" name="sj_clinician_id"><option value="0">Not linked</option>';
        foreach(get_posts(['post_type'=>'sj_clinician','post_status'=>['publish','draft','pending'],'numberposts'=>200,'orderby'=>'title','order'=>'ASC']) as $clinician) if(!Model::get($clinician->ID,'origin_id')) echo '<option value="'.(int)$clinician->ID.'" '.selected($current,$clinician->ID,false).'>'.esc_html($clinician->post_title).'</option>';
        echo '</select><p class="description">Clinical approvals by this account are recorded as reviews by this clinician. Link only the clinician who personally uses this account. Every change is logged.</p></td></tr></table>';
    }
    public static function save_user_link(int $user_id): void {
        if(!current_user_can('manage_options')||!isset($_POST['sj_clinician_id'])) return;
        self::link_user($user_id,absint(wp_unslash($_POST['sj_clinician_id'])));
    }
    /** Links an account to a clinician record and keeps a permanent log of who changed the link. */
    public static function link_user(int $user_id,int $clinician): bool {
        if($clinician && get_post_type($clinician)!=='sj_clinician') return false;
        $previous=Editorial::linked_clinician($user_id);
        if($previous===$clinician) return true;
        $clinician ? update_user_meta($user_id,'sj_clinician_id',$clinician) : delete_user_meta($user_id,'sj_clinician_id');
        $log=(array)get_option('sj_identity_log',[]); $log[]=['user'=>$user_id,'clinician'=>$clinician,'previous'=>$previous,'by'=>get_current_user_id(),'at'=>gmdate('c')];
        update_option('sj_identity_log',$log,false);
        return true;
    }
    public static function routes(): void {
        register_rest_route('swatijha/v1','/notes/(?P<id>\d+)',[
            ['methods'=>'GET','permission_callback'=>static fn($r)=>current_user_can('edit_post',(int)$r['id']),'callback'=>static fn($r)=>['notes'=>Model::get((int)$r['id'],'evidence_notes')]],
            ['methods'=>'POST','permission_callback'=>static fn($r)=>current_user_can('edit_post',(int)$r['id'])&&!Editorial::locked((int)$r['id']),'callback'=>static function($r){ update_post_meta((int)$r['id'],'_sj_evidence_notes',sanitize_textarea_field($r['notes']??'')); return ['saved'=>true]; }],
        ]);
        register_rest_route('swatijha/v1','/catalogue',['methods'=>'GET','permission_callback'=>static fn()=>current_user_can('edit_pages')||current_user_can('sj_edit_entities'),'callback'=>static function() {
            $posts=get_posts(['post_type'=>array_merge(array_keys(Model::TYPES),['page','attachment']),'post_status'=>['publish','draft','pending','inherit'],'numberposts'=>1000]);
            return array_map(static fn($p)=>['id'=>$p->ID,'title'=>$p->post_title,'type'=>$p->post_type,'status'=>$p->post_status,'verified'=>Model::verified($p->ID)],$posts);
        }]);
        register_rest_route('swatijha/v1','/editorial/(?P<id>\d+)',['methods'=>'GET','permission_callback'=>static fn($r)=>current_user_can('edit_post',(int)$r['id']),'callback'=>static fn($r)=>['state'=>Model::get((int)$r['id'],'review_state','draft'),'locked'=>Editorial::locked((int)$r['id']),'origin'=>(int)Model::get((int)$r['id'],'origin_id')]]);
    }
}
