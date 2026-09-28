<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
final class Graph {
    public static function boot(): void {
        add_action('wp_head',[self::class,'head'],5);
        add_filter('get_canonical_url',static fn($url,$post)=>self::canonical($post->ID),10,2);
        add_filter('wp_robots',static function($robots){ if(wp_get_environment_type()!=='production') { $robots['noindex']=true; $robots['nofollow']=true; unset($robots['max-image-preview']); } return $robots; });
        add_filter('wp_sitemaps_post_types',static function($types){ foreach(array_keys(Model::TYPES) as $type) unset($types[$type]); return $types; });
    }
    public static function settings(): array { return (array)get_option('sj_practice',[]); }
    public static function origin(): string { return rtrim(self::settings()['origin']??'https://www.swatijha.com','/'); }
    public static function canonical(int $id): string {
        $url=get_permalink($id); $path=wp_parse_url($url,PHP_URL_PATH)?:'/'; $query=wp_parse_url($url,PHP_URL_QUERY);
        return self::origin().$path.($query?'?'.$query:'');
    }
    public static function entity_id(int $id): string { return self::origin().'/#entity-'.Model::get($id,'uuid'); }
    private static function refs(int $id,string $field): array { return array_values(array_map(static fn($target)=>['@id'=>self::entity_id((int)$target)],array_filter((array)Model::get($id,$field,[]),static fn($target)=>Model::verified((int)$target)))); }
    public static function node(int $id): ?array {
        if(!Model::verified($id)||!Model::get($id,'uuid')) return null;
        $p=get_post($id); $types=['sj_clinician'=>'Person','sj_condition'=>'MedicalCondition','sj_treatment'=>Model::get($id,'schema_type','MedicalTherapy'),'sj_service'=>'Service','sj_location'=>Model::get($id,'location_type','Place'),'sj_publication'=>Model::get($id,'work_type','CreativeWork'),'sj_research'=>'CreativeWork','sj_reference'=>'CreativeWork','sj_resource'=>'CreativeWork'];
        if(!isset($types[$p->post_type])) return null;
        $n=['@type'=>$types[$p->post_type],'@id'=>self::entity_id($id),'name'=>$p->post_title];
        if(Model::get($id,'summary')) $n['description']=Model::get($id,'summary');
        if($page=(int)Model::get($id,'landing_page_id')) { if(get_post_status($page)==='publish') $n['url']=self::canonical($page); }
        if($p->post_type==='sj_clinician') {
            if(Model::get($id,'professional_title')) $n['jobTitle']=Model::get($id,'professional_title');
            if(Model::get($id,'identity_urls')) $n['sameAs']=Model::get($id,'identity_urls');
            foreach((array)Model::get($id,'credentials',[]) as $credential) if(($credential['verification']??'')==='verified') $n['hasCredential'][]=['@type'=>'EducationalOccupationalCredential','name'=>$credential['title'],'recognizedBy'=>['@type'=>'Organization','name'=>$credential['organisation']??'']];
            if(Model::get($id,'gmc')) $n['identifier']=['@type'=>'PropertyValue','propertyID'=>'GMC','value'=>Model::get($id,'gmc')];
            foreach((array)Model::get($id,'roles',[]) as $role) if(($role['verification']??'')==='verified'&&empty($role['ended_on'])) $n['hasOccupation'][]=['@type'=>'Occupation','name'=>$role['title']];
        }
        if($p->post_type==='sj_service') {
            if($refs=self::refs($id,'clinician_ids')) $n['provider']=$refs;
            if($refs=self::refs($id,'location_ids')) $n['availableChannel']=array_map(static fn($ref)=>['@type'=>'ServiceChannel','serviceLocation'=>$ref],$refs);
        }
        if($p->post_type==='sj_location') {
            $address=['@type'=>'PostalAddress']; foreach(['street'=>'streetAddress','locality'=>'addressLocality','region'=>'addressRegion','postcode'=>'postalCode','country'=>'addressCountry'] as $field=>$property) if(Model::get($id,$field)) $address[$property]=Model::get($id,$field);
            if(count($address)>1) $n['address']=$address;
            if(Model::get($id,'telephone')) $n['telephone']=Model::get($id,'telephone');
            if(Model::get($id,'latitude')!==''&&Model::get($id,'longitude')!=='') $n['geo']=['@type'=>'GeoCoordinates','latitude'=>(float)Model::get($id,'latitude'),'longitude'=>(float)Model::get($id,'longitude')];
        }
        if($p->post_type==='sj_condition') {
            foreach(Model::inverse($id) as $related) if(get_post_type($related)==='sj_treatment'&&Model::verified($related)&&Model::get($related,'schema_type','MedicalTherapy')==='MedicalTherapy') $n['possibleTreatment'][]=['@id'=>self::entity_id($related)];
        }
        if(in_array($p->post_type,['sj_publication','sj_research','sj_resource','sj_reference'],true)) {
            if($refs=self::refs($id,'clinician_ids')) $n['author']=$refs;
            if(Model::get($id,'published_on')) $n['datePublished']=Model::get($id,'published_on');
            if(Model::get($id,'source_url')) $n['url']=Model::get($id,'source_url');
            if(Model::get($id,'doi')) $n['identifier']=Model::get($id,'doi');
            if($refs=self::refs($id,'publication_ids')) $n['citation']=$refs;
        }
        return $n;
    }
    public static function graph(int $page): array {
        if(get_post_status($page)!=='publish') return [];
        $url=self::canonical($page); $p=get_post($page); $clinical=(bool)Model::get($page,'clinical');
        $main=['@type'=>$clinical?'MedicalWebPage':'WebPage','@id'=>$url.'#webpage','url'=>$url,'name'=>$p->post_title,'inLanguage'=>'en-GB'];
        if($description=Model::get($page,'seo_description')) $main['description']=$description;
        $ids=array_merge((array)Model::get($page,'author_entity_ids',[]),(array)Model::get($page,'reference_ids',[]));
        if($authors=self::refs($page,'author_entity_ids')) $main['author']=$authors;
        if($refs=self::refs($page,'reference_ids')) $main['citation']=$refs;
        $reviewer=(int)Model::get($page,'reviewer_entity_id');
        if(Model::get($page,'review_state')==='approved'&&Model::verified($reviewer)&&Model::get($page,'medically_reviewed_on')) { $main['reviewedBy']=['@id'=>self::entity_id($reviewer)]; $main['lastReviewed']=Model::get($page,'medically_reviewed_on'); $ids[]=$reviewer; }
        if($bound=Model::bound($page)) { if(Model::verified($bound->ID)) { $main['about']=['@id'=>self::entity_id($bound->ID)]; $ids[]=$bound->ID; } }
        $collect=function(array $blocks) use (&$collect,&$ids) { foreach($blocks as $block) { if(str_starts_with($block['blockName']??'','sj/')) $ids=array_merge($ids,$block['attrs']['entityIds']??[]); if(!empty($block['innerBlocks'])) $collect($block['innerBlocks']); } };
        $collect(parse_blocks($p->post_content));
        if($p->post_type==='post') {
            $article=['@type'=>'Article','@id'=>$url.'#article','headline'=>$p->post_title,'mainEntityOfPage'=>['@id'=>$url.'#webpage'],'datePublished'=>get_post_time('c',true,$p),'dateModified'=>get_post_modified_time('c',true,$p)];
            if($authors) $article['author']=$authors;
        }
        $nodes=[$main]; $seen=[];
        if(isset($article)) $nodes[]=$article;
        $practice=self::settings();
        if(!empty($practice['practice_verified'])&&!empty($practice['practice_name'])&&!empty($practice['practice_source_url'])&&!empty($practice['practice_verified_on'])) {
            $practice_node=['@type'=>'MedicalOrganization','@id'=>self::origin().'/#practice','name'=>$practice['practice_name'],'url'=>self::origin().'/'];
            if(!empty($practice['telephone'])) $practice_node['telephone']=$practice['telephone'];
            if(!empty($practice['email'])) $practice_node['email']=$practice['email'];
            $nodes[]=$practice_node;
        }
        $questions=[];
        $faq=function(array $blocks,bool $marked=false) use (&$faq,&$questions) {
            foreach($blocks as $block) {
                $inside=$marked||str_contains($block['attrs']['className']??'','sj-faq');
                if($inside&&($block['blockName']??'')==='core/details'&&preg_match('~<summary[^>]*>(.*?)</summary>~s',$block['innerHTML'],$match)) {
                    $answer=trim(wp_strip_all_tags(implode('',array_map('render_block',$block['innerBlocks']))));
                    if($answer) $questions[]=['@type'=>'Question','name'=>wp_strip_all_tags($match[1]),'acceptedAnswer'=>['@type'=>'Answer','text'=>$answer]];
                }
                if(!empty($block['innerBlocks'])) $faq($block['innerBlocks'],$inside);
            }
        };
        $faq(parse_blocks($p->post_content));
        if($questions) $nodes[]=['@type'=>'FAQPage','@id'=>$url.'#faq','mainEntity'=>$questions];
        while($ids && count($seen)<100) {
            $id=(int)array_shift($ids); if(isset($seen[$id])) continue; $seen[$id]=true;
            if($node=self::node($id)) {
                $nodes[]=$node;
                // Follow only references actually emitted in this node, not the entire catalogue.
                $serialized=wp_json_encode($node);
                foreach(Model::catalogue() as $candidate) if(str_contains($serialized,self::entity_id($candidate->ID))) $ids[]=$candidate->ID;
            }
        }
        $nodes[]=['@type'=>'BreadcrumbList','@id'=>$url.'#breadcrumb','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>self::origin().'/'],...((int)get_option('page_on_front')===$page?[]:[['@type'=>'ListItem','position'=>2,'name'=>$p->post_title,'item'=>$url]])]];
        return ['@context'=>'https://schema.org','@graph'=>$nodes];
    }
    public static function head(): void {
        if(!is_singular(['page','post'])) return;
        $id=get_queried_object_id(); $description=Model::get($id,'seo_description');
        if($description) echo '<meta name="description" content="'.esc_attr($description).'">'."\n";
        echo '<meta property="og:title" content="'.esc_attr(get_the_title($id)).'">'."\n";
        echo '<meta property="og:url" content="'.esc_url(self::canonical($id)).'">'."\n";
        echo '<meta property="og:type" content="website">'."\n";
        if($description) echo '<meta property="og:description" content="'.esc_attr($description).'">'."\n";
        $graph=self::graph($id);
        if($graph) echo '<script type="application/ld+json">'.wp_json_encode($graph,JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>'."\n";
    }
}
