/* PL per-file guard */
if (typeof window.__PL_FILE_GUARDS === 'undefined') { window.__PL_FILE_GUARDS = {}; }
if (window.__PL_FILE_GUARDS['assets/js/pl-indicators.js']) { console.warn('Duplicate JS skipped:', 'assets/js/pl-indicators.js'); }
else { window.__PL_FILE_GUARDS['assets/js/pl-indicators.js'] = 1;
// pl-indicators.js — minimal, self-contained
(function(){
  if (typeof jQuery === 'undefined') return;
  function classify(val){
    var len = (val||'').toString().trim().length;
    if (len === 0) return {cls:'field-empty', color:'#ef4444', icon:'\u26A0\uFE0F', msg:'Required field'};
    if (len < 100) return {cls:'field-thin',  color:'#f59e0b', icon:'\u26A1',      msg:'Add more detail'};
    return {cls:'field-good', color:'#10b981', icon:'\u2713',   msg:'Well detailed'};
  }
  function ensureTag($el){ if (!$el.hasClass('ai-fillable')) $el.addClass('ai-fillable'); }
  function updateIndicator($el){
    var q = classify($el.val());
    $el.removeClass('field-empty field-thin field-good').addClass(q.cls);
    var $n = $el.siblings('.field-quality-indicator');
    if ($n.length === 0) { $n = jQuery('<div class="field-quality-indicator" />'); $el.after($n); }
    $n.css({color:q.color, display:'flex', alignItems:'center', gap:'6px'})
      .html('<span>'+q.icon+'</span><span style="font-weight:600;">'+q.msg+'</span>');
  }
  function bind($el){
    if ($el.data('plQualityBound')) return;
    $el.on('input.plQuality change.plQuality', function(){ updateIndicator($el); });
    $el.data('plQualityBound', true);
    updateIndicator($el);
  }
  function init(){
    var $fields = jQuery('#wpbody-content').find('textarea, input[type="text"], input[type="search"], input[type="url"], input[type="email"]').filter(':visible');
    $fields.each(function(){ var $el = jQuery(this); ensureTag($el); bind($el); });
  }
  jQuery(init);
  setTimeout(init, 600);
  setTimeout(init, 1500);
})();
}
