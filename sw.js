const SCOPE = self.registration.scope;
const BASE = new URL(SCOPE).pathname.replace(/\/$/, '');
const CACHE_NAME = 'gestaodev-v3';
const OFFLINE_URL = BASE + '/offline.html';
const PRECACHE = [BASE+'/assets/css/app.css',BASE+'/assets/js/app.js',BASE+'/assets/img/favicon.png'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE_NAME).then(c => c.addAll(PRECACHE).catch(()=>{})));
  self.skipWaiting();
});
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(k => Promise.all(k.filter(x=>x!==CACHE_NAME).map(x=>caches.delete(x)))));
  self.clients.claim();
});
self.addEventListener('fetch', e => {
  if(!e.request.url.startsWith('http')) return;
  const u = new URL(e.request.url);
  if (u.pathname.includes('api.php')||u.pathname.includes('login.php')) return;
  if (u.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff2?)$/)) {
    e.respondWith(caches.match(e.request).then(c => c||fetch(e.request).then(r => {
      if(r.ok){const cl=r.clone();caches.open(CACHE_NAME).then(ca=>ca.put(e.request,cl));}return r;
    })));
    return;
  }
  if (e.request.mode==='navigate') {
    e.respondWith(fetch(e.request).catch(()=>caches.match(OFFLINE_URL)||new Response('<html><body style="background:#0a0a1a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif"><div style="text-align:center"><h2>Sem conexão</h2><p>Verifique sua internet.</p></div></body></html>',{headers:{'Content-Type':'text/html'}})));
  }
});
self.addEventListener('push', e => {
  let d={title:'GestãoDev ASSEGO',body:'',icon:BASE+'/assets/img/favicon.png'};
  if(e.data){try{Object.assign(d,e.data.json())}catch(x){d.body=e.data.text()}}
  e.waitUntil(self.registration.showNotification(d.title,{body:d.body||'',icon:d.icon||BASE+'/assets/img/favicon.png',badge:BASE+'/assets/img/favicon.png',tag:d.tag||'gd-'+Date.now(),renotify:true,vibrate:[200,100,200],data:{url:d.url||BASE+'/index.php',id:d.id||null}}));
});
self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url=e.notification.data?.url||BASE+'/index.php';
  e.waitUntil(clients.matchAll({type:'window',includeUncontrolled:true}).then(wc=>{
    for(const c of wc){if(c.url.includes('/index.php')&&'focus' in c){c.focus();c.postMessage({type:'NOTIFICATION_CLICK',url,id:e.notification.data?.id});return;}}
    return clients.openWindow(url);
  }));
});
