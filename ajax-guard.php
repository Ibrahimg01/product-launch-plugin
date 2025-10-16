<?php
if (!class_exists('PL_Ajax_Guard')) {
    class PL_Ajax_Guard {
        public static function guard($action_slug, $nonce_field = 'nonce', $rate_limit = 20, $window_secs = 60) {
            $fields = is_array($nonce_field) ? $nonce_field : array($nonce_field, 'security', '_ajax_nonce');
            $used = null;
            foreach ($fields as $f) { if (isset($_POST[$f])) { $used = $f; break; } }
            if (!$used) { wp_send_json_error(array('message'=>'Missing security token.'), 403); }
            $cands = array($action_slug);
            if (defined('PL_NONCE_ACTION') && PL_NONCE_ACTION) array_unshift($cands, PL_NONCE_ACTION);
            if (isset($_POST['action'])) $cands[] = sanitize_key($_POST['action']);
            $ok=false; foreach(array_unique($cands) as $c){ if(check_ajax_referer($c,$used,false)){ $ok=true; break; } }
            if(!$ok){ wp_send_json_error(array('message'=>'Invalid security token.'),403); }
            $uid = function_exists('get_current_user_id') ? get_current_user_id() : 0;
            $ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
            $key = 'pl_rl_'.md5($action_slug.'|'.$uid.'|'.$ip);
            $now=time(); $bucket=get_transient($key); if(!is_array($bucket)) $bucket=array();
            $bucket = array_values(array_filter(array_map('intval',$bucket), function($t) use($now,$window_secs){return ($now-$t)<$window_secs;}));
            if(count($bucket)>= $rate_limit){ $retry=$window_secs-($now-$bucket[0]); wp_send_json_error(array('message' => 'Rate limit exceeded.','retry_after' => max(1,$retry)),429); }
            $bucket[]=$now; set_transient($key,$bucket,$window_secs);
            foreach($_POST as $k=>$v){
                if(is_string($v)){
                    if(preg_match('/(_json)$/i',$k)){
                        if(strlen($v)>100*1024){ wp_send_json_error(array('message'=>'Payload too large for '.sanitize_text_field($k)),413); }
                        $t=ltrim($v); if($t!==''&&($t[0]=='{'||$t[0]=='[')){ json_decode($v,true); if(json_last_error()!==JSON_ERROR_NONE){ wp_send_json_error(array('message'=>'Invalid JSON for '.sanitize_text_field($k)),400); } }
                    }
                    if(strlen($v)>512*1024){ wp_send_json_error(array('message'=>'Input too large.'),413); }
                }
            }
        }
    }
}
