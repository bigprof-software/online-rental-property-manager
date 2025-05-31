// service-worker.js

// The cache version/name (update this to invalidate old caches)
const CACHE_NAME = 'appgini-cache-v20250531132005'; // vYYYYMMDDHHMMSS

// List of URLs/pages to cache for offline use
const urlsToCache = [
	'common.js',
	'dynamic.css',
	'lang.js.php',
	'loading.gif',
	'resources/images/appgini-icon.png',
	'resources/initializr/fonts/glyphicons-halflings-regular.eot',
	'resources/initializr/fonts/glyphicons-halflings-regular.svg',
	'resources/initializr/fonts/glyphicons-halflings-regular.ttf',
	'resources/initializr/fonts/glyphicons-halflings-regular.woff',
	'resources/initializr/fonts/glyphicons-halflings-regular.woff2',
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

// Install event: Cache specified resources
self.addEventListener('install', (event) => {
	event.waitUntil(
		caches.open(CACHE_NAME).then((cache) => {
			console.log('[Service Worker] Caching resources');
			return cache.addAll(urlsToCache);
		}).catch((error) => {
			console.error('[Service Worker] Failed to cache resources during install:', error);
		})
	);
});

// Activate event: Cleanup old caches
self.addEventListener('activate', (event) => {
	event.waitUntil(
		caches.keys().then((cacheNames) => {
			return Promise.all(
				cacheNames.map((cacheName) => {
					if (cacheName !== CACHE_NAME) {
						console.log(`[Service Worker] Deleting old cache: ${cacheName}`);
						return caches.delete(cacheName);
					}
				})
			);
		}).then(() => {
			console.log('[Service Worker] Activation complete');
		}).catch((error) => {
			console.error('[Service Worker] Error during cache cleanup:', error);
		})
	);
});

// Fetch event: Serve cached content or fallback
self.addEventListener('fetch', (event) => {
	event.respondWith(
		caches.match(event.request).then((cachedResponse) => {
			if (cachedResponse) {
				// Serve the cached resource
				return cachedResponse;
			}

			// Fetch from the network as a fallback
			return fetch(event.request).catch(() => {
				// Handle offline fallback for navigation requests
				if (event.request.mode === 'navigate' || event.request.destination === 'document') {
					return caches.match('pwa-offline.php');
				}

				// Optional: Handle specific cases for other resources (e.g., images)
				if (event.request.destination === 'image') {
					return new Response('', { status: 404, statusText: 'Image not found' });
				}

				// Return an empty response for other cases
				return new Response('');
			});
		}).catch((error) => {
			console.error('[Service Worker] Fetch handler error:', error);
			return new Response('Service Worker fetch error', { status: 500 });
		})
	);
});
