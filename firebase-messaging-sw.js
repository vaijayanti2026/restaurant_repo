importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');
firebase.initializeApp({apiKey: "AIzaSyCKwO4a_kghNp06yvgjzkqYuAGfR622iKg",authDomain: "catrina-fresh-mex---app.firebaseapp.com",projectId: "catrina-fresh-mex---app",storageBucket: "catrina-fresh-mex---app.appspot.com", messagingSenderId: "1058533763543", appId: "1:1058533763543:web:6b28f0c07f181131bac392"});
const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) { return self.registration.showNotification(payload.data.title, { body: payload.data.body ? payload.data.body : '', icon: payload.data.icon ? payload.data.icon : '' }); });
