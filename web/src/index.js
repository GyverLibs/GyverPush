import './index.css'

document.getElementById('subscribe').onclick = subscribe;
document.getElementById('unsubscribe').onclick = unsubscribe;
const output = document.getElementById('output');
const output_p = document.getElementById('output_p');

function showPopup(text, x, y) {
    popup.textContent = text;
    popup.style.left = x + 'px';
    popup.style.top = y + 'px';
    popup.style.opacity = '1';

    clearTimeout(showPopup._timer);
    showPopup._timer = setTimeout(() => {
        popup.style.opacity = '0';
    }, 900);
}

async function copyOut(e) {
    const text = e.target.textContent || '';
    try {
        await navigator.clipboard.writeText(text);
        showPopup('Copied', e.clientX, e.clientY);
    } catch (err) {
        showPopup('Copy failed', e.clientX, e.clientY);
    }
}

output.onclick = copyOut;
output_p.onclick = copyOut;

function setOut(out, out_p = '') {
    output.innerText = out;
    output_p.innerText = out_p;
}

async function subscribe() {
    function B64toUINT8(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        const output = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            output[i] = rawData.charCodeAt(i);
        }

        return output;
    }

    const reg = await navigator.serviceWorker.register('sw.js', { updateViaCache: 'none' });
    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        setOut('Permission denied');
        return;
    }

    let subscription = await reg.pushManager.getSubscription();

    if (!subscription) {
        subscription = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: B64toUINT8(VAPID),
        });
    }

    const token = btoa(JSON.stringify(subscription))

    setOut(token, 'static const char push_token[] PROGMEM = "' + token + '";');
}

async function unsubscribe() {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        setOut('No subscription');
        return;
    }

    const endpoint = subscription.endpoint;
    const ok = await subscription.unsubscribe();

    setOut(ok ? 'Done' : 'Unsubscribe error');
}