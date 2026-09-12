import Echo from 'laravel-echo';

import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

const showWhatsAppNotification = async (data) => {
    if (!('Notification' in window)) {
        return;
    }

    if (Notification.permission === 'default') {
        try {
            await Notification.requestPermission();
        } catch {
            return;
        }
    }

    if (Notification.permission !== 'granted') {
        return;
    }

    const title = data.customer_name
        ? `WhatsApp — ${data.customer_name}`
        : 'New WhatsApp Message';

    const body = data.body || 'You have a new WhatsApp message.';

    try {
        const registration = await navigator.serviceWorker.ready;

        await registration.showNotification(title, {
            body,
            icon: '/img/logo.png',
            badge: '/img/logo.png',
            tag: `whatsapp-message-${data.message_id}`,
            renotify: true,
            data: {
                url: data.url || '/admin/inbox',
            },
        });
    } catch {
        new Notification(title, {
            body,
            icon: '/img/logo.png',
        });
    }
};

const subscribeToWhatsAppNotifications = () => {
    if (!window.Echo) {
        return;
    }

    window.Echo
        .channel('admin-notifications')
        .listen('.whatsapp.message.received', (data) => {
            showWhatsAppNotification(data);
        });
};

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        subscribeToWhatsAppNotifications,
        { once: true }
    );
} else {
    subscribeToWhatsAppNotifications();
}