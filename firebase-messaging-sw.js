importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');

firebase.initializeApp({
  apiKey: "AIzaSyDSN0VzHK1ZW-9Eo5s_OUpj8K1kJsWxCWQ",
  authDomain: "fcm-3-e0206.firebaseapp.com",
  projectId: "fcm-3-e0206",
  storageBucket: "fcm-3-e0206.firebasestorage.app",
  messagingSenderId: "216842738805",
  appId: "1:216842738805:web:438f601a4fc60bd9c72c3c",
  measurementId: "G-H45PQVECQQ"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  console.log('Background message received:', payload);
  
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/icon.png'
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});