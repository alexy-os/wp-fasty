<?php
/** NON-LIVE: wp --path=OUR_ISOLATED_WP eval-file tools/sdk/builder-wp-contract.php. Never call on a customer site. */
require_once dirname(__DIR__, 2).'/plugins/wpfasty-builder/wpfasty-builder.php';
use WPFastyBuilder\{Chat,DraftGuard,Sources,Store};
function contract(bool $ok, string $label): void { if (!$ok) { throw new RuntimeException('NON-LIVE FAIL: '.$label); } echo 'PASS '.$label."\n"; }
$engine = static fn() => 'latte';
add_filter('wpfasty_builder_engine',$engine);
$originalUser = get_current_user_id();
$admin = get_users(['role'=>'administrator','number'=>1,'fields'=>'ID']);
if (!$admin) { throw new RuntimeException('Own WP needs an administrator test account.'); }
$page = wp_insert_post(['post_type'=>'page','post_status'=>'draft','post_title'=>'NON-LIVE Builder worker contract']);
if (!$page || is_wp_error($page)) { throw new RuntimeException('Cannot create local disposable fixture'); }
$request = static function(string $action, array $body=[]) use ($page): WP_REST_Request {
    $r = new WP_REST_Request('history' === $action ? 'GET' : 'POST', '/wpfasty-builder/v1/chat/'.$page.'/'.$action);
    $r['id']=$page; $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest')); $r->set_header('Content-Type','application/json');$r->set_body(wp_json_encode($body)); return $r;
};
$settings = static fn() => ['worker_token'=>str_repeat('nonlive-',5), 'cursor_key'=>'TEST_ONLY_NO_PROVIDER_KEY'];
$http = static function($pre,$args,$url) {
    if (!str_starts_with($url,'http://127.0.0.1:18991/')) return $pre;
    if (str_ends_with($url,'/history')) return ['response'=>['code'=>200],'body'=>'{"turns":[]}','headers'=>[]];
    $body=json_decode($args['body'],true); contract(($body['credentials']['cursor']??'')==='TEST_ONLY_NO_PROVIDER_KEY','credential server-side only');
    contract(($args['redirection']??-1)===0,'no worker redirects');
    $block=$body['context']['catalog'][0];
    return ['response'=>['code'=>200],'headers'=>[],'body'=>wp_json_encode(['answer'=>'NON-LIVE structural proposal','proposal'=>[['id'=>'fixture-about','block'=>$block['id'],'variant'=>$block['variants'][0]['id'],'props'=>(object)[],'bindings'=>(object)[]]],'baseVersion'=>$body['context']['version']])];
};
try {
    wp_set_current_user((int)$admin[0]);
    contract(Chat::allowed($request('history')),'administrator with nonce permitted');
    $deny=static fn()=>['do_not_allow'];add_filter('map_meta_cap',$deny);
    contract(!Chat::allowed($request('history')),'valid nonce without edit capability rejected');remove_filter('map_meta_cap',$deny);
    $bad=$request('history');$bad->set_header('X-WP-Nonce','bad');contract(!Chat::allowed($bad),'bad nonce rejected');
    wp_set_current_user(0);contract(!Chat::allowed($request('history')),'anonymous rejected');wp_set_current_user((int)$admin[0]);
    $none=static fn()=>[];add_filter('pre_option_'.Chat::OPTION,$none);
    $result=Chat::history($request('history'));contract(is_wp_error($result)&&$result->get_error_code()==='wpfb_chat_unconfigured','missing config deterministic 503');remove_filter('pre_option_'.Chat::OPTION,$none);
    add_filter('pre_option_'.Chat::OPTION,$settings);if (!getenv('WPFB_CONTRACT_HTTP')) add_filter('pre_http_request',$http,10,3);
    ob_start();Chat::settings();$html=ob_get_clean();contract(!str_contains($html,'TEST_ONLY_NO_PROVIDER_KEY')&&str_contains($html,'Configured (masked)'),'settings never print saved credential');
    $res=Chat::turn($request('turn',['requestId'=>'fixture-turn-0001','content'=>'NON-LIVE test draft']));
    if (is_wp_error($res)) echo 'Fixture error code: '.$res->get_error_code()."\n";
    contract($res instanceof WP_REST_Response,'catalog turn proxy response');
    contract(Store::get($page,false)===null,'turn did not publish');
    $apply=Chat::apply($request('apply',['requestId'=>'fixture-turn-0001']));
    if (is_wp_error($apply)) echo wp_json_encode($apply->get_error_data())."\n";
    contract($apply instanceof WP_REST_Response,'explicit apply saves actual catalog draft');
    $version=DraftGuard::version($page);contract($version===1,'draft revision incremented');
    contract(Store::get($page,false)===null,'apply never publishes');
    $again=Chat::apply($request('apply',['requestId'=>'fixture-turn-0001']));contract($again instanceof WP_REST_Response && DraftGuard::version($page)===$version,'apply retry idempotent');
    $replay=Chat::turn($request('turn',['requestId'=>'fixture-turn-0001','content'=>'NON-LIVE test draft']));contract($replay instanceof WP_REST_Response,'turn replay after revision change no provider call');
    $conflict=Chat::turn($request('turn',['requestId'=>'fixture-turn-0001','content'=>'different']));contract(is_wp_error($conflict)&&$conflict->get_error_data()['status']===409,'request content conflict 409');
    $stale=Chat::turn($request('turn',['requestId'=>'fixture-turn-0002','content'=>'NON-LIVE stale proposal']));
    $save=DraftGuard::save($page,['source'=>'ui8kit','blocks'=>[]],false,$version);contract(!is_wp_error($save),'visual draft shares revision lock');
    $stale=Chat::apply($request('apply',['requestId'=>'fixture-turn-0002']));contract(is_wp_error($stale)&&$stale->get_error_data()['status']===409,'stale proposal rejected');
    $save=DraftGuard::save($page,['source'=>'ui8kit','blocks'=>[]],false,$version);contract(is_wp_error($save)&&$save->get_error_data()['status']===409,'stale visual save rejected');
    add_post_meta($page,'_wpfb_chat_invalid-0001',wp_slash(wp_json_encode(['source'=>'ui8kit','version'=>DraftGuard::version($page),'blocks'=>[['id'=>'wrong','block'=>'does-not-exist','variant'=>'fake','props'=>[],'bindings'=>[]]]])),true);
    $invalid=Chat::apply($request('apply',['requestId'=>'invalid-0001']));contract(is_wp_error($invalid)&&$invalid->get_error_data()['status']===400,'unknown catalog proposal rejected');
    remove_filter('pre_http_request',$http,10);
    $offline=static fn()=>new WP_Error('offline','NON-LIVE offline fixture');add_filter('pre_http_request',$offline,10,3);
    $error=Chat::history($request('history'));contract(is_wp_error($error)&&$error->get_error_code()==='wpfb_worker_offline','worker offline clear 503');remove_filter('pre_http_request',$offline,10);
} finally {
    remove_filter('wpfasty_builder_engine',$engine);remove_filter('pre_option_'.Chat::OPTION,$settings);remove_filter('pre_http_request',$http,10);delete_option('wpfb_draft_lock_'.$page);wp_delete_post($page,true);wp_set_current_user($originalUser);
}
echo "NON-LIVE WordPress contracts complete; no real credentials or models used.\n";
