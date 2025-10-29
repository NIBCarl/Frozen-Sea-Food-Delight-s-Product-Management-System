// Register Service Worker for PWA
export function registerServiceWorker() {
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker
        .register('/service-worker.js')
        .then((registration) => {
          console.log('Service Worker registered successfully:', registration.scope);

          // Check for updates periodically
          setInterval(() => {
            registration.update();
          }, 60000); // Check every minute

          // Handle updates
          registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New service worker available
                console.log('New content available; please refresh.');
                
                // Optionally show a notification to the user
                if (window.confirm('New version available! Refresh to update?')) {
                  window.location.reload();
                }
              }
            });
          });
        })
        .catch((error) => {
          console.error('Service Worker registration failed:', error);
        });

      // Handle service worker messages
      navigator.serviceWorker.addEventListener('message', (event) => {
        console.log('Message from Service Worker:', event.data);
        
        if (event.data.type === 'CACHE_UPDATED') {
          console.log('Cache has been updated');
        }
      });
    });

    // Handle offline/online events
    window.addEventListener('online', () => {
      console.log('Back online!');
      // Trigger background sync if supported
      if ('sync' in registration) {
        registration.sync.register('sync-orders').catch((err) => {
          console.error('Background sync registration failed:', err);
        });
      }
    });

    window.addEventListener('offline', () => {
      console.log('Gone offline');
    });
  } else {
    console.warn('Service Worker not supported in this browser');
  }
}

// Check if app is running as PWA
export function isPWA() {
  return window.matchMedia('(display-mode: standalone)').matches ||
         window.navigator.standalone === true;
}

// Prompt user to install PWA
export function showInstallPrompt() {
  let deferredPrompt;

  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    
    // Show install button/prompt to user
    console.log('PWA install prompt available');
    
    // You can dispatch a custom event here to show your install UI
    window.dispatchEvent(new Event('pwa-install-available'));
  });

  // Handle install button click
  window.addEventListener('pwa-install-click', async () => {
    if (!deferredPrompt) return;

    // Show the install prompt
    deferredPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.userChoice;
    console.log(`User response to install prompt: ${outcome}`);
    
    // Clear the deferredPrompt
    deferredPrompt = null;
  });

  // Track installation
  window.addEventListener('appinstalled', () => {
    console.log('PWA installed successfully');
    deferredPrompt = null;
  });
}

