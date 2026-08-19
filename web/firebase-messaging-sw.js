importScripts("https://www.gstatic.com/firebasejs/12.7.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/12.7.0/firebase-messaging-compat.js");

firebase.initializeApp({
    apiKey: "AIzaSyCKwO4a_kghNp06yvgjzkqYuAGfR622iKg",
    authDomain: "catrina-fresh-mex---app.firebaseapp.com",
    projectId: "catrina-fresh-mex---app",
    storageBucket: "catrina-fresh-mex---app.firebasestorage.app",
    messagingSenderId: "1058533763543",
    appId: "1:1058533763543:web:6b28f0c07f181131bac392",
    measurementId: "G-CPBCWG0BEY",
});

const messaging = firebase.messaging();

// Optional:
messaging.onBackgroundMessage((message) => {
    const data = message.data || {};
    const notification = message.notification || {};
    const title = data.title || notification.title || "Notification";
    const body = data.body || data.description || data.message || notification.body || "";
    const image = data.image_url || data.image || notification.image;

    self.registration.showNotification(title, {
        body: body,
        icon: image || "/icons/Icon-192.png",
        image: image,
        data: {
            ...data,
            click_action: data.click_action || "/notification",
        },
    });
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.click_action || "/notification";
    event.waitUntil(
        clients.matchAll({ type: "window", includeUncontrolled: true }).then((windows) => {
            for (const client of windows) {
                if ("focus" in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            return clients.openWindow(targetUrl);
        }),
    );
});
