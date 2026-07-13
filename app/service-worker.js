// service-worker.js

// The cache version/name (update this to invalidate old caches)
const CACHE_NAME = 'appgini-cache-v20260713190152'; // vYYYYMMDDHHMMSS

// List of URLs/pages to cache for offline use
const urlsToCache = [
	'common.js',
	'dynamic.css',
	'loading.gif',
	'manifest.json.php',

	'resources/images/appgini-icon.png',
	'resources/images/app-icon-72.png',
	'resources/images/app-icon-96.png',
	'resources/images/app-icon-144.png',
	'resources/images/app-icon-192.png',
	'resources/images/app-icon-384.png',
	'resources/images/app-icon-512.png',

	'resources/initializr/fonts/glyphicons-halflings-regular.eot',
	'resources/initializr/fonts/glyphicons-halflings-regular.svg',
	'resources/initializr/fonts/glyphicons-halflings-regular.ttf',
	'resources/initializr/fonts/glyphicons-halflings-regular.woff',
	'resources/initializr/fonts/glyphicons-halflings-regular.woff2',
	'resources/initializr/fonts/DroidKufi-Bold.eot',
	'resources/initializr/fonts/DroidKufi-Bold.svg',
	'resources/initializr/fonts/DroidKufi-Bold.ttf',
	'resources/initializr/fonts/DroidKufi-Bold.woff',
	'resources/initializr/fonts/DroidKufi-Bold.woff2',
	'resources/initializr/fonts/DroidKufi-Regular.eot',
	'resources/initializr/fonts/DroidKufi-Regular.svg',
	'resources/initializr/fonts/DroidKufi-Regular.ttf',
	'resources/initializr/fonts/DroidKufi-Regular.woff',
	'resources/initializr/fonts/DroidKufi-Regular.woff2',

	'resources/select2/select2.css',
	'resources/timepicker/bootstrap-timepicker.min.css',
	'resources/datepicker/css/datepicker.css',
	'resources/bootstrap-datetimepicker/bootstrap-datetimepicker.css',
	'resources/moment/moment-with-locales.min.js',
	'resources/jquery/js/jquery.mark.min.js',
	'resources/initializr/js/vendor/bootstrap.min.js',
	'resources/select2/select2.min.js',
	'resources/timepicker/bootstrap-timepicker.min.js',
	'resources/datepicker/js/datepicker.packed.js',
	'resources/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js',
	'resources/hotkeys/jquery.hotkeys.min.js',

	'resources/initializr/css/bootstrap-desktop.png',
	'resources/initializr/css/bootstrap-mobile.png',
	'resources/initializr/css/bootstrap-theme.css',
	'resources/initializr/css/bootstrap.css',
	'resources/initializr/css/cerulean-desktop.png',
	'resources/initializr/css/cerulean-mobile.png',
	'resources/initializr/css/cerulean.css',
	'resources/initializr/css/cosmo-desktop.png',
	'resources/initializr/css/cosmo-mobile.png',
	'resources/initializr/css/cosmo.css',
	'resources/initializr/css/cyborg-desktop.png',
	'resources/initializr/css/cyborg-mobile.png',
	'resources/initializr/css/cyborg.css',
	'resources/initializr/css/darkly-desktop.png',
	'resources/initializr/css/darkly-mobile.png',
	'resources/initializr/css/darkly.css',
	'resources/initializr/css/flatly-desktop.png',
	'resources/initializr/css/flatly-mobile.png',
	'resources/initializr/css/flatly.css',
	'resources/initializr/css/index.html',
	'resources/initializr/css/journal-desktop.png',
	'resources/initializr/css/journal-mobile.png',
	'resources/initializr/css/journal.css',
	'resources/initializr/css/paper-desktop.png',
	'resources/initializr/css/paper-mobile.png',
	'resources/initializr/css/paper.css',
	'resources/initializr/css/readable-desktop.png',
	'resources/initializr/css/readable-mobile.png',
	'resources/initializr/css/readable.css',
	'resources/initializr/css/rtl.css',
	'resources/initializr/css/sandstone-desktop.png',
	'resources/initializr/css/sandstone-mobile.png',
	'resources/initializr/css/sandstone.css',
	'resources/initializr/css/simplex-desktop.png',
	'resources/initializr/css/simplex-mobile.png',
	'resources/initializr/css/simplex.css',
	'resources/initializr/css/slate-desktop.png',
	'resources/initializr/css/slate-mobile.png',
	'resources/initializr/css/slate.css',
	'resources/initializr/css/spacelab-desktop.png',
	'resources/initializr/css/spacelab-mobile.png',
	'resources/initializr/css/spacelab.css',
	'resources/initializr/css/superhero-desktop.png',
	'resources/initializr/css/superhero-mobile.png',
	'resources/initializr/css/superhero.css',
	'resources/initializr/css/united-desktop.png',
	'resources/initializr/css/united-mobile.png',
	'resources/initializr/css/united.css',
	'resources/initializr/css/yeti-desktop.png',
	'resources/initializr/css/yeti-mobile.png',
	'resources/initializr/css/yeti.css',

	'nicEdit.js',
	'shortcuts.js',
	'spinner.gif',
	'nicEditorIcons.gif',
	'pwa-offline.php', // Ensure fallback is included here for consistency
];

// Install event: Cache specified resources, robustly
self.addEventListener('install', (event) => {
	self.skipWaiting();
	event.waitUntil((async () => {
		const cache = await caches.open(CACHE_NAME);
		const results = await Promise.allSettled(urlsToCache.map(u => cache.add(u)));
		results.forEach(r => {
			if (r.status === 'rejected') {
				console.warn('[Service Worker] Pre-cache miss:', r.reason);
			}
		});
	})());
});

// Activate event: Cleanup old caches, enable navigation preload, and claim clients
self.addEventListener('activate', (event) => {
	event.waitUntil((async () => {
		const cacheNames = await caches.keys();
		await Promise.all(
			cacheNames.map((cacheName) => {
				if (cacheName !== CACHE_NAME) {
					console.log('[Service Worker] Deleting old cache:', cacheName);
					return caches.delete(cacheName);
				}
			})
		);
		// Enable navigation preload for faster navigations
		if (self.registration.navigationPreload) {
			await self.registration.navigationPreload.enable();
			console.log('[Service Worker] Navigation preload enabled');
		}
		await self.clients.claim();
	})());
});

// Fetch event: Security, navigation preload, opaque handling
self.addEventListener('fetch', (event) => {
	const req = event.request;

	// Only handle GET requests
	if (req.method !== 'GET') return;

	const url = new URL(req.url);

	// SECURITY: Only handle requests for our own origin
	if (url.origin !== self.location.origin) {
		console.log('[Service Worker] Ignoring request to foreign origin:', url.origin);
		return;
	}

	const dest = req.destination;

	// 1) Navigations: network-preload-first, fallback to offline page
	if (req.mode === 'navigate') {
		event.respondWith((async () => {
			// Try navigation preload first
			const preloadResponse = await event.preloadResponse;
			if (preloadResponse) {
				return preloadResponse;
			}
			try {
				return await fetch(req);
			} catch (e) {
				// Offline: show cached offline page
				const cachedOffline = await caches.match('pwa-offline.php', { ignoreSearch: true });
				return cachedOffline || Response.error();
			}
		})());
		return;
	}

	// 2) Static assets: cache-first, do not cache opaque responses
	const isStatic = ['script', 'style', 'image', 'font'].includes(dest);
	if (isStatic) {
		event.respondWith((async () => {
			const cache = await caches.open(CACHE_NAME);
			const cached = await cache.match(req, {});
			if (cached) return cached;

			try {
				const fresh = await fetch(req);
				// OPAQUE RESPONSE HANDLING
				if (fresh && fresh.ok && fresh.type !== 'opaque') {
					// Only cache non-opaque responses
					cache.put(req, fresh.clone());
				} else if (fresh && fresh.type === 'opaque') {
					console.warn('[Service Worker] Opaque response not cached:', req.url);
				}
				return fresh;
			} catch (e) {
				return cached || Response.error();
			}
		})());
		return;
	}

	// 3) Other requests to our own origin: pass through
});
