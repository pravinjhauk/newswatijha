<?php
namespace SwatiJha;
defined('ABSPATH') || exit;

final class Model {
    public const TYPES = [
        'sj_clinician' => 'Clinicians', 'sj_condition' => 'Conditions',
        'sj_treatment' => 'Treatments', 'sj_service' => 'Services',
        'sj_location' => 'Locations', 'sj_publication' => 'Publications',
        'sj_research' => 'Research', 'sj_reference' => 'References',
        'sj_resource' => 'Resources', 'sj_review' => 'Reviews',
    ];
    public const SCHEMA_VERSION = 2;
    public const ADMIN_CAPS = ['sj_edit_entities','sj_review_clinical','sj_publish_clinical','sj_migrate'];
    /** Role definitions are reconciled on every schema upgrade, not only on activation. */
    public const ROLES = [
        'sj_content_editor' => ['Clinical content editor', ['read','edit_posts','edit_others_posts','edit_published_posts','edit_pages','edit_others_pages','edit_published_pages','upload_files','sj_edit_entities']],
        'sj_clinical_reviewer' => ['Clinical reviewer', ['read','edit_posts','edit_others_posts','edit_pages','edit_others_pages','sj_edit_entities','sj_review_clinical']],
        'sj_publisher' => ['Clinical publisher', ['read','edit_posts','edit_others_posts','edit_published_posts','edit_pages','edit_others_pages','edit_published_pages','publish_pages','publish_posts','upload_files','sj_edit_entities','sj_publish_clinical']],
        'sj_migration_operator' => ['Migration operator', ['read','sj_migrate']],
    ];
    /** Fields written only by the approval workflow; hidden from editors and importers. */
    public const WORKFLOW_FIELDS = ['reviewer_entity_id','medically_reviewed_on'];
    /** Page-level fields an approved migration manifest may carry. */
    public const IMPORTABLE_PAGE_FIELDS = ['seo_title','seo_description','robots'];
    private static ?array $landing = null;
    public static function fields(string $type): array {
        $common = [
            'uuid' => ['string', 'Stable identifier'],
            'source_url' => ['url', 'Evidence source'],
            'verified_on' => ['date', 'Verified on'],
            'verification' => ['enum:unverified,verified', 'Verification'],
            'landing_page_id' => ['id:page', 'Public landing page'],
            'summary' => ['string', 'Patient-friendly summary'],
        ];
        $specific = [
            'sj_clinician' => ['professional_title'=>['string','Professional title'], 'gmc'=>['string','GMC number'], 'portrait_id'=>['id:attachment','Portrait'], 'identity_urls'=>['urls','Verified identity links'], 'credentials'=>['credentials','Credentials'], 'roles'=>['roles','Professional roles']],
            'sj_condition' => ['parent_id'=>['id:sj_condition','Broader condition'], 'terminology_code'=>['string','Verified terminology code']],
            'sj_treatment' => ['category'=>['enum:conservative,pessary,medicine,surgical','Category'], 'schema_type'=>['enum:MedicalTherapy,MedicalProcedure,SurgicalProcedure','Reviewed classification'], 'condition_ids'=>['ids:sj_condition','Conditions']],
            'sj_service' => ['clinician_ids'=>['ids:sj_clinician','Clinicians'], 'condition_ids'=>['ids:sj_condition','Conditions'], 'treatment_ids'=>['ids:sj_treatment','Treatments'], 'location_ids'=>['ids:sj_location','Locations'], 'booking_url'=>['url','Booking link'], 'modality'=>['enum:in-person,video,telephone','Consultation format'], 'active'=>['boolean','Available'], 'fee'=>['string','Verified fee'], 'currency'=>['enum:GBP','Currency'], 'fee_effective_on'=>['date','Fee effective date']],
            'sj_location' => ['location_type'=>['enum:Place,Hospital,MedicalClinic','Location type'], 'street'=>['string','Street'], 'locality'=>['string','Town or city'], 'region'=>['string','County'], 'postcode'=>['string','Postcode'], 'country'=>['string','Country code'], 'telephone'=>['string','Public telephone'], 'directions_url'=>['url','Directions'], 'latitude'=>['string','Latitude'], 'longitude'=>['string','Longitude'], 'availability'=>['string','Availability']],
            'sj_publication' => ['work_type'=>['enum:ScholarlyArticle,Book,CreativeWork','Work type'], 'authors'=>['string','Authors in publication order'], 'clinician_ids'=>['ids:sj_clinician','Local authors'], 'condition_ids'=>['ids:sj_condition','Topics'], 'doi'=>['string','DOI'], 'pmid'=>['string','PMID'], 'isbn'=>['string','ISBN'], 'publisher'=>['string','Publisher or journal'], 'published_on'=>['date','Publication date']],
            'sj_research' => ['clinician_ids'=>['ids:sj_clinician','Researchers'], 'condition_ids'=>['ids:sj_condition','Topics'], 'publication_ids'=>['ids:sj_publication','Publications'], 'status'=>['enum:planned,active,completed','Status'], 'research_role'=>['string','Verified role'], 'collaborators'=>['string','Collaborators'], 'funder'=>['string','Funder'], 'grant'=>['string','Public grant details'], 'started_on'=>['date','Start date'], 'ended_on'=>['date','End date']],
            'sj_reference' => ['organisation'=>['string','Source organisation'], 'doi'=>['string','DOI'], 'published_on'=>['date','Published'], 'checked_on'=>['date','Last checked']],
            'sj_resource' => ['kind'=>['enum:leaflet,video,audio,article','Resource type'], 'attachment_id'=>['id:attachment','Local file'], 'resource_url'=>['url','Authorised external resource'], 'transcript'=>['string','Transcript'], 'captions_id'=>['id:attachment','Captions file'], 'audience'=>['enum:patients,professionals,students','Audience'], 'condition_ids'=>['ids:sj_condition','Topics'], 'clinician_ids'=>['ids:sj_clinician','Contributors']],
            'sj_review' => ['source_id'=>['string','Source review ID'], 'display_name'=>['string','Permitted display name'], 'excerpt'=>['string','Permitted excerpt'], 'published_on'=>['date','Review date'], 'permission'=>['enum:unconfirmed,confirmed','Display permission'], 'moderation'=>['enum:pending,approved,rejected','Moderation'], 'rating'=>['string','Rating'], 'rating_scale'=>['string','Rating scale']],
        ];
        return isset(self::TYPES[$type]) ? array_merge($common, $specific[$type] ?? []) : [];
    }
    public static function clinical_fields(): array {
        return ['clinical'=>['boolean','Clinical information'], 'author_entity_ids'=>['ids:sj_clinician','Clinical authors'], 'reviewer_entity_id'=>['id:sj_clinician','Clinical reviewer'], 'medically_reviewed_on'=>['date','Medical review date'], 'review_due_on'=>['date','Next review due'], 'reference_ids'=>['ids:sj_reference,sj_publication','References'], 'reference_sections'=>['references','Section citations'], 'seo_description'=>['string','Search description'], 'seo_title'=>['string','Search title'], 'robots'=>['enum:index,noindex','Search indexing'], 'social_image_id'=>['id:attachment','Social sharing image']];
    }
    public static function schema(string $kind): array {
        if (str_starts_with($kind, 'enum:')) return ['type'=>'string','enum'=>array_merge([''],explode(',',substr($kind,5)))];
        if (str_starts_with($kind,'ids:')) return ['type'=>'array','items'=>['type'=>'integer','minimum'=>1],'uniqueItems'=>true];
        if (str_starts_with($kind,'id:')) return ['type'=>'integer','minimum'=>0];
        if ($kind === 'boolean') return ['type'=>'boolean'];
        if ($kind === 'urls') return ['type'=>'array','items'=>['type'=>'string','format'=>'uri']];
        if (in_array($kind,['credentials','roles','references'],true)) {
            $keys = $kind === 'credentials' ? ['uuid','title','organisation','awarded_on','expires_on','source_url','verified_on','verification'] : ($kind === 'roles' ? ['uuid','title','organisation','organisation_url','role_type','started_on','ended_on','source_url','verified_on','verification'] : ['anchor','label']);
            $properties = array_fill_keys($keys,['type'=>'string']);
            if ($kind === 'references') $properties['entity_id'] = ['type'=>'integer','minimum'=>1];
            return ['type'=>'array','items'=>['type'=>'object','properties'=>$properties,'additionalProperties'=>false]];
        }
        return ['type'=>'string'];
    }
    public static function boot(): void {
        add_action('init',[self::class,'upgrade'],1);
        add_action('init',[self::class,'register']);
        add_filter('pre_option_show_avatars','__return_zero');
        remove_action('wp_head','print_emoji_detection_script',7);
        remove_action('wp_enqueue_scripts','wp_enqueue_emoji_styles');
        add_action('save_post',[self::class,'identify'],10,3);
        add_filter('rest_pre_insert_page',[self::class,'validate_rest'],10,2);
        add_filter('rest_pre_insert_post',[self::class,'validate_rest'],10,2);
        foreach (array_keys(self::TYPES) as $type) add_filter('rest_pre_insert_'.$type,[self::class,'validate_rest'],10,2);
        add_action('save_post', [self::class,'invalidate']);
        add_action('trashed_post', [self::class,'invalidate']);
        add_action('untrashed_post', [self::class,'invalidate']);
    }
    public static function activate(): void {
        self::sync_roles();
        add_option('sj_practice',['version'=>1,'origin'=>'https://www.swatijha.com','primary_clinician'=>0,'contact_page'=>0,'booking_page'=>0,'telephone'=>'','email'=>'','practice_name'=>'','practice_verified'=>false,'allow_delegated_review'=>false], '', false);
        update_option('sj_schema_version',self::SCHEMA_VERSION,false);
    }
    /** Runs on every request; does work only when the stored schema version is behind the code. */
    public static function upgrade(): void {
        if ((int)get_option('sj_schema_version',0) >= self::SCHEMA_VERSION) return;
        self::sync_roles();
        update_option('sj_schema_version',self::SCHEMA_VERSION,false);
    }
    public static function sync_roles(): void {
        $admin = get_role('administrator');
        foreach (self::ADMIN_CAPS as $cap) $admin?->add_cap($cap);
        foreach (self::ROLES as $slug=>[$label,$caps]) {
            $role = get_role($slug);
            if (!$role) { add_role($slug,$label,array_fill_keys($caps,true)); continue; }
            foreach ($caps as $cap) if (empty($role->capabilities[$cap])) $role->add_cap($cap);
            foreach (array_keys($role->capabilities) as $cap) if (!in_array($cap,$caps,true)) $role->remove_cap($cap);
        }
    }
    public static function register(): void {
        foreach (self::TYPES as $type=>$label) {
            register_post_type($type,['labels'=>['name'=>$label,'singular_name'=>rtrim($label,'s')], 'public'=>false,'show_ui'=>true,'show_in_rest'=>true,'show_in_menu'=>'sj-practice','rewrite'=>false,'query_var'=>false,'supports'=>['title','editor','revisions','custom-fields'], 'capabilities'=>self::entity_caps(),'map_meta_cap'=>true]);
        }
        register_taxonomy('sj_specialty',array_keys(self::TYPES),['label'=>'Specialties','public'=>false,'show_ui'=>true,'show_in_rest'=>true,'hierarchical'=>true,'rewrite'=>false,'capabilities'=>['manage_terms'=>'sj_edit_entities','edit_terms'=>'sj_edit_entities','delete_terms'=>'sj_edit_entities','assign_terms'=>'sj_edit_entities']]);
        foreach (array_merge(['page','post'],array_keys(self::TYPES)) as $type) {
            add_post_type_support($type,'custom-fields');
            foreach (array_merge(self::fields($type),self::clinical_fields()) as $field=>[$kind,$label]) {
                $schema=self::schema($kind);
                register_post_meta($type,'_sj_'.$field,['single'=>true,'type'=>$schema['type'],'show_in_rest'=>['schema'=>$schema+['context'=>['edit']]],'revisions_enabled'=>true,'auth_callback'=>static fn($allowed,$key,$id)=>current_user_can('edit_post',$id),'sanitize_callback'=>static fn($value)=>self::sanitize($value)]);
            }
        }
    }
    /** Every primitive post capability maps to an owned capability, so custom roles can work on published records. */
    public static function entity_caps(): array {
        $caps = array_fill_keys(['edit_posts','edit_others_posts','edit_private_posts','edit_published_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','create_posts'],'sj_edit_entities');
        $caps['publish_posts'] = 'sj_publish_clinical';
        $caps['read'] = 'read';
        return $caps;
    }
    public static function sanitize($value) {
        if (is_array($value)) return array_map([self::class,'sanitize'],$value);
        if (is_bool($value)||is_int($value)) return $value;
        return sanitize_textarea_field((string)$value);
    }
    public static function get(int $id,string $field,$default='') { $value=get_post_meta($id,'_sj_'.$field,true); return $value === '' ? $default : $value; }
    public static function verified(int $id): bool { return get_post_status($id)==='publish' && self::get($id,'verification')==='verified'; }
    public static function identify(int $id,\WP_Post $post,bool $update): void {
        if (!isset(self::TYPES[$post->post_type]) || wp_is_post_revision($id)) return;
        if (!self::get($id,'uuid')) update_post_meta($id,'_sj_uuid',wp_generate_uuid4());
        if (!self::get($id,'verification')) update_post_meta($id,'_sj_verification','unverified');
    }
    public static function validate_rest($prepared,\WP_REST_Request $request) {
        $id=(int)$request['id']; $type=$id ? get_post_type($id) : ($prepared->post_type ?? 'page');
        foreach (($request['meta']??[]) as $key=>$value) {
            $error=self::validate($id,$type,$key,$value);
            if (is_wp_error($error)) return $error;
        }
        return $prepared;
    }
    public static function validate(int $id,string $type,string $key,$value) {
        $fields=array_merge(self::fields($type),self::clinical_fields()); $field=preg_replace('/^_sj_/','',$key);
        if (!isset($fields[$field])) return true;
        $kind=$fields[$field][0]; $invalid=static fn($message)=>new \WP_Error('sj_invalid',$message,['status'=>400]);
        $schema=rest_validate_value_from_schema($value,self::schema($kind),$field);
        if(is_wp_error($schema)) return $schema;
        if ($kind==='url' && $value && !preg_match('~^https?://[^\s]+$~i',$value)) return $invalid('Use a complete HTTP or HTTPS source URL.');
        if ($kind==='date' && $value && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$value) || !checkdate((int)substr($value,5,2),(int)substr($value,8,2),(int)substr($value,0,4)))) return $invalid('Enter a valid date as YYYY-MM-DD.');
        if (in_array($field,['medically_reviewed_on','verified_on'],true) && $value > current_time('Y-m-d')) return $invalid('A verification or review cannot be dated in the future.');
        if ($field==='uuid' && $id && self::get($id,'uuid') && $value!==self::get($id,'uuid')) return $invalid('The stable identifier cannot be changed.');
        if ($field==='gmc' && $value && !preg_match('/^\d{7}$/',$value)) return $invalid('Enter a seven-digit GMC number.');
        if ($field==='doi' && $value && !preg_match('~^10\.\d{4,9}/\S+$~',$value)) return $invalid('Enter a DOI beginning 10.');
        if ($field==='latitude' && $value!=='' && (!is_numeric($value)||abs((float)$value)>90)) return $invalid('Latitude must be between -90 and 90.');
        if ($field==='longitude' && $value!=='' && (!is_numeric($value)||abs((float)$value)>180)) return $invalid('Longitude must be between -180 and 180.');
        if (str_starts_with($kind,'id:')||str_starts_with($kind,'ids:')) {
            $targets=explode(',',substr($kind,strpos($kind,':')+1));
            foreach ((array)$value as $target) if ($target && (!in_array(get_post_type((int)$target),$targets,true)||get_post_status((int)$target)==='trash'||(int)$target===$id)) return $invalid('Choose an existing record of the correct type.');
        }
        if ($field==='landing_page_id' && $value) {
            foreach(self::catalogue(false) as $other) if($other->ID!==$id && $other->ID!==(int)self::get($id,'origin_id') && (int)self::get($other->ID,'landing_page_id')===(int)$value && !self::get($other->ID,'origin_id')) return $invalid('This page already has a primary entity.');
        }
        if ($field==='parent_id' && $value) {
            $seen=[$id]; $cursor=(int)$value;
            while($cursor) { if(in_array($cursor,$seen,true)) return $invalid('A condition cannot be its own ancestor.'); $seen[]=$cursor; $cursor=(int)self::get($cursor,'parent_id'); }
        }
        $item_ids=[];
        if (in_array($kind,['credentials','roles'],true)) foreach($value as $item) {
            if(empty($item['uuid'])||!wp_is_uuid($item['uuid'])||in_array($item['uuid'],$item_ids,true)) return $invalid('Each credential or role needs its own unique identifier.');
            $item_ids[]=$item['uuid'];
            if($kind==='roles'&&!empty($item['role_type'])&&!in_array($item['role_type'],['NHS','private','academic','honorary','national'],true)) return $invalid('Choose a supported professional role type.');
            foreach(['source_url','organisation_url'] as $urlkey) if(!empty($item[$urlkey])&&!preg_match('~^https?://[^\s]+$~i',$item[$urlkey])) return $invalid('Use HTTP(S) evidence links.');
            foreach(['awarded_on','expires_on','started_on','ended_on','verified_on'] as $datekey) if(!empty($item[$datekey]) && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$item[$datekey])||!checkdate((int)substr($item[$datekey],5,2),(int)substr($item[$datekey],8,2),(int)substr($item[$datekey],0,4)))) return $invalid('A credential or role date is invalid.');
            if(!empty($item['started_on'])&&!empty($item['ended_on'])&&$item['ended_on']<$item['started_on']) return $invalid('Role end date precedes its start.');
            if(!empty($item['verified_on'])&&$item['verified_on']>current_time('Y-m-d')) return $invalid('A verification date cannot be in the future.');
            if(!empty($item['awarded_on'])&&!empty($item['expires_on'])&&$item['expires_on']<$item['awarded_on']) return $invalid('Credential expiry precedes its award.');
            if(($item['verification']??'')==='verified' && (empty($item['source_url'])||empty($item['verified_on']))) return $invalid('Verified credentials and roles need evidence and a verification date.');
        }
        if ($kind==='references') foreach($value as $ref) if(!in_array(get_post_type((int)($ref['entity_id']??0)),['sj_reference','sj_publication'],true)||(!empty($ref['anchor'])&&!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/',$ref['anchor']))) return $invalid('Choose a reference and a valid section anchor.');
        return true;
    }
    public static function catalogue(bool $published=true): array {
        return get_posts(['post_type'=>array_keys(self::TYPES),'post_status'=>$published?'publish':['publish','draft','pending','private'],'numberposts'=>1000,'orderby'=>'title','order'=>'ASC']);
    }
    public static function bound(int $page): ?\WP_Post {
        foreach(self::catalogue() as $entity) if((int)self::get($entity->ID,'landing_page_id')===$page) return $entity;
        return null;
    }
    /** Entities (any status) that name this page as their landing page. */
    public static function landing_entities(int $page): array {
        if (self::$landing === null) {
            self::$landing = [];
            foreach (self::catalogue(false) as $entity) if ($landing=(int)self::get($entity->ID,'landing_page_id')) self::$landing[$landing][] = $entity->ID;
        }
        return self::$landing[$page] ?? [];
    }
    public static function inverse(int $target): array {
        $index=get_transient('sj_relation_index');
        if(!is_array($index)) { $index=[]; foreach(self::catalogue() as $entity) foreach(self::fields($entity->post_type) as $field=>[$kind]) if(str_starts_with($kind,'ids:')||str_starts_with($kind,'id:')) foreach((array)self::get($entity->ID,$field,[]) as $id) if($id) $index[(int)$id][]=$entity->ID; set_transient('sj_relation_index',$index,HOUR_IN_SECONDS); }
        return array_values(array_unique($index[$target]??[]));
    }
    public static function invalidate(): void { delete_transient('sj_relation_index'); self::$landing = null; }
}
