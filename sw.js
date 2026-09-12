const CACHE_NAME = "aurelius-pwa-v1";
const ASSETS = [
  "Principal.php",
  "hospedagem.php",
  "video.php",
  "BarbeariaBranca.php",
  "uploads/OIP (6).webp"
];

// Instala o cache inicial em segundo plano
self.addEventListener("install", (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

// Limpa caches antigos quando atualiza o sistema
self.addEventListener("activate", (e) => {
  e.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) return caches.delete(key);
        })
      );
    })
  );
});

// Carrega do cache se a rede local falhar ou estiver lenta
self.addEventListener("fetch", (e) => {
  e.respondWith(
    caches.match(e.request).then((cachedResponse) => {
      return cachedResponse || fetch(e.request);
    })
  );
});