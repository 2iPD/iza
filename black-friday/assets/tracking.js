(function () {
  'use strict';
  const pixelId = '427796161815093';
  const storageKey = 'black_criminal_attribution';
  const allowed = key => /^utm_[a-z0-9_]+$/i.test(key) || ['fbclid', 'gclid', 'wbraid', 'gbraid', 'msclkid', 'ttclid', 'src', 'sck'].includes(key);
  const query = new URLSearchParams(location.search);
  let campaign = {};
  try { campaign = JSON.parse(sessionStorage.getItem(storageKey) || '{}'); } catch (_) {}
  if (!campaign || typeof campaign !== 'object' || Array.isArray(campaign)) campaign = {};
  const incoming = Array.from(query).filter(([key, value]) => allowed(key) && value);
  if (incoming.length) campaign = Object.fromEntries(incoming);
  try { sessionStorage.setItem(storageKey, JSON.stringify(campaign)); } catch (_) {}
  const cookie = name => {
    const entry = document.cookie.split(';').map(v => v.trim()).find(v => v.startsWith(name + '='));
    if (!entry) return '';
    try { return decodeURIComponent(entry.slice(name.length + 1)); } catch (_) { return ''; }
  };
  // A click ID must originate from an actual Meta click, never a fabricated value.
  const clickId = query.get('fbclid');
  if (clickId) {
    const existing = cookie('_fbc');
    if (!existing || !existing.endsWith('.' + clickId)) {
      document.cookie = '_fbc=' + encodeURIComponent('fb.1.' + Date.now() + '.' + clickId) + '; Max-Age=7776000; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    }
  }
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=true;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=true;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  window.fbq('init', pixelId);
  // Meta's browser library manages _fbp and sends _fbp/_fbc as native identifiers.
  const context = extra => Object.assign({content_name:'Formação de Criminalistas',content_category:'Black Criminal',content_type:'product',content_ids:['K104703066T'],currency:'BRL'}, campaign, extra || {});
  const track = (name, extra, custom) => {
    try { window.fbq(custom ? 'trackSingleCustom' : 'trackSingle', pixelId, name, context(extra)); } catch (_) {}
  };
  track('PageView');
  track('ViewContent');
  const redeem = document.getElementById('redeem');
  const baseCheckout = redeem.href;
  function updateCheckout() {
    const url = new URL(baseCheckout);
    Object.entries(campaign).forEach(([key,value]) => { if (allowed(key) && typeof value === 'string') url.searchParams.set(key,value); });
    // Cookies cannot cross domains; forward available attribution identifiers explicitly.
    const fbc = cookie('_fbc'), fbp = cookie('_fbp');
    if (fbc) url.searchParams.set('fbc',fbc);
    if (fbp) url.searchParams.set('fbp',fbp);
    redeem.href = url.href;
  }
  updateCheckout();
  redeem.addEventListener('pointerdown', updateCheckout);
  redeem.addEventListener('click', () => {
    updateCheckout();
    track('InitiateCheckout',{num_items:1,discount_percent:40});
  });
  document.getElementById('spin').addEventListener('click', () => track('WheelSpin',{discount_percent:40},true));
  const prize = document.getElementById('prize');
  new MutationObserver(records => {
    if (records.some(record => record.attributeName === 'open') && prize.open) {
      updateCheckout();
      track('PrizeRevealed',{discount_percent:40},true);
    }
  }).observe(prize,{attributes:true,attributeFilter:['open']});
})();
