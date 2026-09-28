<?php
require '/wordpress/wp-load.php';
use SwatiJha\Model;
use SwatiJha\Editorial;
use SwatiJha\Graph;
use SwatiJha\Migration;
use SwatiJha\Forms;
wp_set_current_user(1);
$tests=[];
function check($name,$condition,$detail='') { global $tests; $tests[]=['name'=>$name,'passed'=>(bool)$condition,'detail'=>$condition?'':$detail]; }
function good($result){return !is_wp_error($result);}
function entity($type,$title) { $id=wp_insert_post(['post_type'=>$type,'post_status'=>'draft','post_title'=>$title]); update_post_meta($id,'_sj_source_url','https://example.org/evidence');update_post_meta($id,'_sj_verified_on','2026-09-01');update_post_meta($id,'_sj_verification','verified');return $id; }
function release($id,$reviewer){Editorial::act($id,'request');$approved=Editorial::act($id,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$reviewer]);if(is_wp_error($approved))return $approved;return Editorial::act($id,'release');}
check('Theme is active',get_stylesheet()==='swatijha-theme');
check('All 10 entity types registered',count(array_filter(array_keys(Model::TYPES),'post_type_exists'))===10);
check('All dynamic blocks registered',count(array_filter(array_keys(SwatiJha\Blocks::BLOCKS),fn($name)=>WP_Block_Type_Registry::get_instance()->is_registered('sj/'.$name)))===14);
$clinician=entity('sj_clinician','Synthetic test clinician');
check('Stable UUID generated',wp_is_uuid(Model::get($clinician,'uuid')));
$r=release($clinician,$clinician);check('Initial verified clinician can be approved and released',good($r),is_wp_error($r)?$r->get_error_message():wp_json_encode($r));
$condition=entity('sj_condition','Synthetic condition');$r=release($condition,$clinician);check('Entity release requires and accepts verified clinician',good($r),is_wp_error($r)?$r->get_error_message():'');
$clinical_fixture_id=wp_insert_post(['post_type'=>'page','post_title'=>'Clinical fixture','post_status'=>'draft','post_content'=>'<!-- wp:paragraph --><p>Approved original.</p><!-- /wp:paragraph -->']);
update_post_meta($clinical_fixture_id,'_sj_clinical',true);update_post_meta($clinical_fixture_id,'_sj_author_entity_ids',[$clinician]);
$r=Editorial::act($clinical_fixture_id,'release');check('Unreviewed page cannot be released',is_wp_error($r));
$r=release($clinical_fixture_id,$clinician);check('Reviewed page can be released',good($r),is_wp_error($r)?$r->get_error_message():'');
wp_update_post(['ID'=>$clinical_fixture_id,'post_content'=>'Unsafe direct overwrite']);check('Direct published content edit is blocked',str_contains(get_post($clinical_fixture_id)->post_content,'Approved original'));
update_post_meta($clinical_fixture_id,'_sj_clinical',false);check('Published clinical flag cannot bypass protection',(bool)Model::get($clinical_fixture_id,'clinical'));
check('Published clinical deletion is blocked',wp_trash_post($clinical_fixture_id)===false);
$change=Editorial::act($clinical_fixture_id,'change');check('Change draft created',good($change));$draft=$change['id']??0;
if($draft){
 wp_update_post(['ID'=>$draft,'post_content'=>'<!-- wp:paragraph --><p>Reviewed replacement.</p><!-- /wp:paragraph -->']);
 check('Draft edit leaves published content intact',str_contains(get_post($clinical_fixture_id)->post_content,'Approved original'));
 Editorial::act($draft,'request');$r=Editorial::act($draft,'approve',['reviewed_on'=>'2099-01-01','reviewer_id'=>$clinician]);check('Future medical review date rejected',is_wp_error($r));
 $r=Editorial::act($draft,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician]);check('Draft approval succeeds',good($r));
 wp_update_post(['ID'=>$draft,'post_content'=>'Modified after approval']);$r=Editorial::act($draft,'release');check('Changed revision invalidates approval',is_wp_error($r));
 wp_update_post(['ID'=>$draft,'post_content'=>'<!-- wp:paragraph --><p>Reviewed replacement.</p><!-- /wp:paragraph -->']);$r=release($draft,$clinician);check('Approved replacement promoted',good($r),is_wp_error($r)?$r->get_error_message():'');
 check('Promotion retains original URL and page ID',str_contains(get_post($clinical_fixture_id)->post_content,'Reviewed replacement')&&get_post_status($clinical_fixture_id)==='publish');
}
$graph=Graph::graph($clinical_fixture_id);$encoded=wp_json_encode($graph);check('Graph generated from approved entities',str_contains($encoded,'MedicalWebPage')&&str_contains($encoded,'Synthetic test clinician'));
check('Graph has no localhost identifiers',!str_contains($encoded,'127.0.0.1')&&!str_contains($encoded,'localhost'));
check('No automatic review stars',!str_contains($encoded,'aggregateRating'));
check('Wrong relationship target rejected',is_wp_error(Model::validate($condition,'sj_condition','_sj_parent_id',$clinician)));
check('Unsafe source scheme rejected',is_wp_error(Model::validate($condition,'sj_condition','_sj_source_url','javascript:alert(1)')));
check('Unapproved URL map blocks migration',is_wp_error(Migration::run(['version'=>1,'records'=>[]])));
check('Enquiry disabled in local environment',!Forms::enabled()&&is_wp_error(Forms::validate([])));
$editor=wp_insert_user(['user_login'=>'fixture_editor','user_pass'=>wp_generate_password(32),'role'=>'sj_content_editor']);wp_set_current_user($editor);
$r=Editorial::act($clinical_fixture_id,'approve');check('Content editor cannot approve clinical records',is_wp_error($r));
wp_set_current_user(0);$request=new WP_REST_Request('GET','/swatijha/v1/catalogue');$response=rest_do_request($request);check('Anonymous catalogue access denied',$response->get_status()===401||$response->get_status()===403);
$request=new WP_REST_Request('GET','/wp/v2/sj_clinician');$response=rest_do_request($request);check('Anonymous entity REST access denied',$response->get_status()===403);
$request=new WP_REST_Request('GET','/wp/v2/pages/'.$clinical_fixture_id);$response=rest_do_request($request);$json=wp_json_encode($response->get_data());check('Private editorial audit not in public REST',!str_contains($json,'_sj_audit')&&!str_contains($json,'_sj_approved_by'));
wp_set_current_user(1);
// Additional tests use synthetic records only; the real URL map remains untouched.
$clone=Editorial::act($condition,'change');
check('Entity can be revised without losing its UUID',good($clone)&&Model::get($clone['id'],'uuid')===Model::get($condition,'uuid'));
$term=wp_insert_term('Synthetic specialty','sj_specialty');
wp_set_object_terms($condition,[(int)$term['term_id']],'sj_specialty');
check('Published specialty changes cannot bypass review',count(wp_get_object_terms($condition,'sj_specialty'))===0);
$uuid=wp_generate_uuid4();
$manifest=['version'=>1,'approval'=>['approved_by'=>'Synthetic fixture only','approved_on'=>'2026-09-01'],'url_map'=>[['old_url'=>'https://example.org/synthetic','new_url'=>'https://example.org/synthetic','action'=>'KEEP','approval'=>'APPROVED']],'records'=>[['uuid'=>$uuid,'type'=>'sj_condition','title'=>'Synthetic imported draft','content'=>'<!-- wp:paragraph --><p>Fixture.</p><!-- /wp:paragraph -->','meta'=>['summary'=>'Synthetic test']]]];
$dry=Migration::run($manifest);check('Approved synthetic dry run proposes without writes',good($dry)&&$dry['creates']===1&&$dry['writes']===[]);
$import=Migration::run($manifest,false);check('Synthetic import creates drafts only',good($import));
$repeat=Migration::run($manifest,false);check('Synthetic import is idempotent',good($repeat)&&$repeat['creates']===0&&$repeat['updates']===1);
$bad=$manifest;$bad['records'][0]['uuid']=wp_generate_uuid4();$bad['records'][0]['type']='sj_treatment';$bad['records'][0]['meta']=['condition_ids'=>[wp_generate_uuid4()]];
$before=wp_count_posts('sj_treatment')->draft;$result=Migration::run($bad,false);
check('Unresolved import relationship rolls back',is_wp_error($result)&&wp_count_posts('sj_treatment')->draft===$before);
$all=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/wordpress/wp-content/plugins/swatijha-core'));
$syntax=true;foreach($all as $file) if($file->isFile()&&$file->getExtension()==='php'){try{token_get_all(file_get_contents($file->getPathname()),TOKEN_PARSE);}catch(Throwable $error){$syntax=false;}}
check('All plugin PHP parses on PHP 8.3',$syntax);
$revision=(int)Model::get($clinical_fixture_id,'approved_revision_id');
check('Approved revision identifies the published narrative',$revision>0&&get_post($revision)->post_content===get_post($clinical_fixture_id)->post_content,wp_json_encode(['revision'=>$revision,'enabled'=>wp_revisions_enabled(get_post($clinical_fixture_id)),'constant'=>defined('WP_POST_REVISIONS')?WP_POST_REVISIONS:'unset','content'=>$revision?get_post($revision)->post_content:'none','expected'=>get_post($clinical_fixture_id)->post_content]));
$notes=new WP_REST_Request('POST','/swatijha/v1/notes/'.$clone['id']);$notes->set_param('notes','Synthetic private evidence');$notes_result=rest_do_request($notes);check('Private evidence can be saved on a change draft',$notes_result->get_status()===200);
wp_set_current_user(0);$notes_read=rest_do_request(new WP_REST_Request('GET','/swatijha/v1/notes/'.$clone['id']));check('Anonymous private evidence access denied',$notes_read->get_status()===401||$notes_read->get_status()===403);wp_set_current_user(1);
$create_request=new WP_REST_Request('POST','/wp/v2/sj_treatment');$create_request->set_body_params(['title'=>'Synthetic REST treatment','status'=>'draft','meta'=>['_sj_category'=>'conservative','_sj_condition_ids'=>[$condition],'_sj_summary'=>'Synthetic REST save']]);
$create_response=rest_do_request($create_request);check('Native REST editor can save typed relationships',$create_response->get_status()===201,wp_json_encode($create_response->get_data()));
$failed=count(array_filter($tests,fn($test)=>!$test['passed']));echo wp_json_encode(['passed'=>count($tests)-$failed,'failed'=>$failed,'tests'=>$tests]);
