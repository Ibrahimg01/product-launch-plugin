/* PL per-file guard */
if (typeof window.__PL_FILE_GUARDS === 'undefined') { window.__PL_FILE_GUARDS = {}; }
if (window.__PL_FILE_GUARDS['assets/js/plc-memory-bridge.js']) { console.warn('Duplicate JS skipped:', 'assets/js/plc-memory-bridge.js'); }
else { window.__PL_FILE_GUARDS['assets/js/plc-memory-bridge.js'] = 1;
;(function(){
'use strict';
function log(){ try { if (window.console && console.log) console.log.apply(console, arguments); } catch(e){} }
function collectPhases(){
  var phases = {}; try {
    for (var i=0;i<localStorage.length;i++){
      var k = localStorage.key(i) || '';
      if (k.indexOf('pl_context_')===0 || k.indexOf('pl_progress_')===0){
        try{
          var raw = localStorage.getItem(k); var obj = JSON.parse(raw || 'null');
          if (obj && typeof obj==='object' && Object.keys(obj).length){
            var slug = k.replace(/^pl_(context|progress)_/, '');
            if (!phases[slug]) phases[slug] = {};
            for (var p in obj){ if (Object.prototype.hasOwnProperty.call(obj,p)) phases[slug][p]=obj[p]; }
          }
        }catch(e){}
      }
    }
  } catch(e){}
  return phases;
}
function bannerText(){
  try{
    var ctx = { phases: collectPhases(), now: new Date().toISOString() };
    if (!ctx.phases || !Object.keys(ctx.phases).length) return '';
    var json = JSON.stringify(ctx);
    if (json.length>20000) json = json.slice(0,20000) + '...';
    return 'MEMORY_CONTEXT (previous phases)\n' + json + '\n-- END MEMORY --\n\n';
  }catch(e){ return ''; }
}
function already(s){ return (typeof s==='string' && s.indexOf('MEMORY_CONTEXT (previous phases)')===0); }
function shouldAttach(u, d){
  u = u || ''; d = d || '';
  if (d.indexOf('action=product_launch_chat')!==-1) return true;
  if (d.indexOf('action=product_launch_generate_ai')!==-1) return true;
  if (u.indexOf('product_launch_chat')!==-1) return true;
  if (u.indexOf('product_launch_generate_ai')!==-1) return true;
  return false;
}
function attachToData(data, url){
  var banner = bannerText(); if (!banner) return data;
  try{
    if (typeof FormData!=='undefined' && data instanceof FormData){
      var keys=['message','prompt','input']; for (var i=0;i<keys.length;i++){ var key=keys[i]; var v=data.get&&data.get(key); if (v){ v=String(v); if(!already(v)){ data.set(key, banner+v); data.set('pl_mem_injected','1'); log('PL: injected FormData.'+key); } return data; } }
      data.append('message', banner); data.set('pl_mem_injected','1'); log('PL: injected FormData.message(add)'); return data;
    }
    if (data && typeof data==='object'){
      if (data.message){ if(!already(String(data.message))){ data.message=banner+String(data.message); data.pl_mem_injected='1'; log('PL: injected object.message'); } return data; }
      if (data.prompt){ if(!already(String(data.prompt))){ data.prompt=banner+String(data.prompt); data.pl_mem_injected='1'; log('PL: injected object.prompt'); } return data; }
      if (data.input){ if(!already(String(data.input))){ data.input=banner+String(data.input); data.pl_mem_injected='1'; log('PL: injected object.input'); } return data; }
      data.message=banner; data.pl_mem_injected='1'; log('PL: injected object.message(add)'); return data;
    }
    if (typeof data==='string'){
      var ds=data;
      if (shouldAttach(url, ds)){
        function enc(s){ try{ return encodeURIComponent(s); }catch(e){ return s; } }
        function addOrPrepend(key){
          var rx=new RegExp('(^|&)'+key+'=([^&]*)'); var m=ds.match(rx);
          if (m){ var val=decodeURIComponent(m[2]||''); if(!already(val)){ var newVal=enc(banner+val); ds=ds.replace(rx, function(_,pre){ return pre+key+'='+newVal; }); log('PL: injected qs.'+key); } }
          else { ds += (ds.indexOf('?')===-1?'?':'&') + key + '=' + enc(banner); log('PL: injected qs add '+key); }
        }
        if (/(^|&)message=/.test(ds)) addOrPrepend('message');
        else if (/(^|&)prompt=/.test(ds)) addOrPrepend('prompt');
        else if (/(^|&)input=/.test(ds)) addOrPrepend('input');
        else addOrPrepend('message');
        return ds;
      }
    }
  }catch(e){}
  return data;
}
// jQuery
if (window.jQuery && jQuery.ajaxPrefilter){
  jQuery.ajaxPrefilter(function(options, originalOptions, jqXHR){
    try{
      var url=(options&&options.url)?options.url:'';
      var d=(options&&options.data)?(typeof options.data==='string'?options.data:jQuery.param(options.data)):'';
      if (!shouldAttach(url, d)) return;
      options.data = attachToData(options.data, url);
      log('PL: banner via jQuery.ajax');
    }catch(e){}
  });
}
// fetch
if (window.fetch){
  var _fetch = window.fetch;
  window.fetch = function(input, init){
    try{
      init = init || {};
      var url=(typeof input==='string')?input:((input&&input.url)?input.url:'');
      var method=(init.method||'GET').toUpperCase();
      var body=init.body; var bodyStr='';
      if (typeof body==='string') bodyStr=body; else if (body && typeof URLSearchParams!=='undefined' && body instanceof URLSearchParams) bodyStr=body.toString();
      if (method!=='GET' && shouldAttach(url, bodyStr)){
        init.body = attachToData(body, url);
        log('PL: banner via fetch');
      }
    }catch(e){}
    return _fetch.apply(this, arguments);
  };
}
// wp.ajax
if (window.wp && wp.ajax){
  var _send=wp.ajax.send, _post=wp.ajax.post;
  wp.ajax.send=function(action, options){
    try{ options=options||{}; options.data=options.data||{};
      if((''+action).indexOf('product_launch_chat')!==-1||(''+action).indexOf('product_launch_generate_ai')!==-1){
        options.data = attachToData(options.data, '');
        log('PL: banner via wp.ajax.send');
      } }catch(e){}
    return _send.call(this, action, options);
  };
  wp.ajax.post=function(action, data){
    try{
      if((''+action).indexOf('product_launch_chat')!==-1||(''+action).indexOf('product_launch_generate_ai')!==-1){
        data = attachToData(data||{}, '');
        log('PL: banner via wp.ajax.post');
      } }catch(e){}
    return _post.call(this, action, data);
  };
}
})();
}
