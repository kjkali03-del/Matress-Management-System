const NotificationManager = {
    serviceWorkerRegistration: null,

    async initialize() {
        if (!('Notification' in window)) {
            console.warn('Browser notifications are not supported.');
            return;
        }

        if (!('serviceWorker' in navigator)) {
            console.warn('Service workers are not supported.');
            return;
        }

        try {
            this.serviceWorkerRegistration =
                await navigator.serviceWorker.register('/service-worker.js');

            console.log('Notification service worker registered.');

            await this.requestPermission();
        } catch (error) {
            console.error(
                'Notification system initialization failed:',
                error
            );
        }
    },

    async requestPermission() {
        if (Notification.permission === 'granted') {
            return true;
        }

        if (Notification.permission === 'denied') {
            return false;
        }

        const permission = await Notification.requestPermission();

        return permission === 'granted';
    },

    async show(title, options = {}) {
        const allowed = await this.requestPermission();

        if (!allowed) {
            return false;
        }

        if (!this.serviceWorkerRegistration) {
            this.serviceWorkerRegistration =
                await navigator.serviceWorker.ready;
        }

        await this.serviceWorkerRegistration.showNotification(
            title,
            {
                body: options.body || '',
                icon: options.icon || '/img/logo.png',
                badge: options.badge || '/img/logo.png',
                tag: options.tag || 'wonder-godoro-point',
                data: {
                    url: options.url || '/admin/inbox',
                },
            }
        );

        return true;
    },
};

window.NotificationManager = NotificationManager;

document.addEventListener('DOMContentLoaded', () => {
    NotificationManager.initialize();
});