import {precacheAndRoute} from 'workbox-precaching';
import {clientsClaim} from 'workbox-core';

// Automatically take control of clients
self.skipWaiting();
clientsClaim();

const manifest = self.__WB_MANIFEST;

precacheAndRoute(manifest);


console.log('✅ Service Worker Loaded & Ready');
