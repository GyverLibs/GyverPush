self.addEventListener('push', event => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = {
            title: 'Notification',
            body: event.data ? event.data.text() : '',
        };
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'Push Message', {
            body: data.body || '',
        })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(clients.openWindow(targetUrl));
});