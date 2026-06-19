import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY;

if (key && !window.Echo) {
    window.Echo = new Echo({
        broadcaster: import.meta.env.VITE_BROADCAST_DRIVER || 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

export function realtimeConnection() {
    return window.Echo?.connector?.pusher?.connection ?? null;
}

export function listenToFeedback(channel, callback, statusCallback = () => {}) {
    if (!window.Echo || !channel) return () => {};

    const name = `feedback.${channel}`;
    const connection = realtimeConnection();
    const connected = () => statusCallback('connected');
    const connecting = () => statusCallback('connecting');
    const unavailable = () => statusCallback('unavailable');
    const failed = () => statusCallback('failed');

    connection?.bind('connected', connected);
    connection?.bind('connecting', connecting);
    connection?.bind('unavailable', unavailable);
    connection?.bind('failed', failed);
    statusCallback(connection?.state === 'connected' ? 'connected' : 'connecting');
    window.Echo.channel(name).listen('.feedback.message', callback);

    return () => {
        window.Echo.leave(name);
        connection?.unbind('connected', connected);
        connection?.unbind('connecting', connecting);
        connection?.unbind('unavailable', unavailable);
        connection?.unbind('failed', failed);
    };
}

export function listenToCommunication(channel, callback, statusCallback = () => {}) {
    if (!window.Echo || !channel) return () => {};

    const name = `communication.${channel}`;
    const connection = realtimeConnection();
    const connected = () => statusCallback('connected');
    const connecting = () => statusCallback('connecting');
    const unavailable = () => statusCallback('unavailable');
    const failed = () => statusCallback('failed');

    connection?.bind('connected', connected);
    connection?.bind('connecting', connecting);
    connection?.bind('unavailable', unavailable);
    connection?.bind('failed', failed);
    statusCallback(connection?.state === 'connected' ? 'connected' : 'connecting');
    window.Echo.channel(name).listen('.communication.message', callback);

    return () => {
        window.Echo.leave(name);
        connection?.unbind('connected', connected);
        connection?.unbind('connecting', connecting);
        connection?.unbind('unavailable', unavailable);
        connection?.unbind('failed', failed);
    };
}
