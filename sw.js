const SCOPE = self.registration.scope;
const BASE = new URL(SCOPE).pathname.replace(/\/$/, '');
const APP_VERSION = 'v5';
const CACHE_NAME = 'gestaodev-' + APP_VERSION;
const OFFLINE_URL = BASE + '/offline.html';

self.addEventListener('install', e => {
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k.startsWith('gestaodev-') && k !== CACHE_NAME).map(k => caches.delete(k))
    )).then(() => self.clients.claim()).then(() => {
      self.clients.matchAll({type:'window'}).then(clients => {
        clients.forEach(c => c.postMessage({type:'SW_UPDATED',version:APP_VERSION}));
      });
    })
  );
});

self.addEventListener('fetch', e => {
  if(!e.request.url.startsWith('http')) return;
  const u = new URL(e.request.url);
  if (u.pathname.includes('api.php') || u.pathname.includes('login.php')) return;
  if (u.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff2?)$/)) {
    e.respondWith(
      fetch(e.request).then(r => {
        if(r.ok){const cl=r.clone();caches.open(CACHE_NAME).then(ca=>ca.put(e.request,cl));}
        return r;
      }).catch(() => caches.match(e.request))
    );
    return;
  }
  if (e.request.mode === 'navigate') {
    e.respondWith(
      fetch(e.request).catch(() => caches.match(OFFLINE_URL) || new Response('<html><body style="background:#0a0a1a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif"><div style="text-align:center"><h2>Sem conexão</h2><p>Verifique sua internet.</p></div></body></html>',{headers:{'Content-Type':'text/html'}}))
    );
  }
});

self.addEventListener('push', e => {
  let d={title:'GestãoDev ASSEGO',body:'',icon:BASE+'/assets/img/favicon.png'};
  try{const p=e.data.json();d.title=p.title||d.title;d.body=p.message||p.body||'';d.icon=p.icon||d.icon;d.data={url:p.url||'/'}}catch(x){}
  e.waitUntil(self.registration.showNotification(d.title,{body:d.body,icon:d.icon,badge:d.icon,data:d.data,vibrate:[200,100,200]}));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();

  // Extrair a página do link da notificação
  // url pode vir como: "demand:123", "solicitation", "notice:5", "/index.php#solicitacoes", etc.
  const rawUrl = e.notification.data?.url || '/';

  // Montar URL completa para o index.php do app
  const appUrl = SCOPE + 'index.php';

  e.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {

      // Procurar uma janela do app já aberta
      for (const client of windowClients) {
        if (client.url.includes('index.php') && 'focus' in client) {
          // Janela existe — foca nela e manda o link para navegar internamente
          client.focus();
          client.postMessage({ type: 'NOTIFICATION_CLICK', url: rawUrl });
          return;
        }
      }

      // Nenhuma janela aberta — abrir o app com hash correto
      // Converter formatos: "demand:123" → hash para showPage + openDetail
      // "solicitation" → #solicitacoes, etc.
      let hash = '';
      if (rawUrl.includes('#')) {
        // Já tem hash — ex: "/index.php#solicitacoes"
        hash = rawUrl.split('#')[1] || '';
      } else if (rawUrl.includes(':')) {
        // Formato "demand:123", "notice:5", etc.
        const [type] = rawUrl.split(':');
        const pageMap = {
          demand: 'demandas',
          solicitation: 'solicitacoes',
          notice: 'avisos',
          meeting: 'reunioes',
        };
        hash = pageMap[type] || type;
      } else if (rawUrl !== '/') {
        hash = rawUrl.replace(/^\//, '');
      }

      const openUrl = hash ? appUrl + '#' + hash : appUrl;
      return clients.openWindow(openUrl);
    })
  );
});