This is an automatic translation and may be incorrect in some places. See the source README and examples for authoritative information.

[![latest](https://img.shields.io/github/v/release/GyverLibs/GyverPush.svg?color=brightgreen)](https://github.com/GyverLibs/GyverPush/releases/latest/download/GyverPush.zip)
[![PIO](https://badges.registry.platformio.org/packages/gyverlibs/library/GyverPush.svg)](https://registry.platformio.org/libraries/gyverlibs/GyverPush)
[![Foo](https://img.shields.io/badge/Website-AlexGyver.ru-blue.svg?style=flat-square)](https://alexgyver.ru/)
[![Foo](https://img.shields.io/badge/%E2%82%BD%24%E2%82%AC%20%D0%9F%D0%BE%D0%B4%D0%B4%D0%B5%D1%80%D0%B6%D0%B0%D1%82%D1%8C-%D0%B0%D0%B2%D1%82%D0%BE%D1%80%D0%B0-orange.svg?style=flat-square)](https://alexgyver.ru/support_alex/)
[![Foo](https://img.shields.io/badge/README-ENGLISH-blueviolet.svg?style=flat-square)](https://github-com.translate.goog/GyverLibs/GyverPush?_x_tr_sl=ru&_x_tr_tl=en)  

[![Foo](https://img.shields.io/badge/ПОДПИСАТЬСЯ-НА%20ОБНОВЛЕНИЯ-brightgreen.svg?style=social&logo=telegram&color=blue)](https://t.me/GyverLibs)

# GyverPush
Library for sending PUSH notifications from Arduino

### Compatibility
Compatible with all Arduino platforms (Arduino features are used)

## Contents
- [Use of use](#usage)
- [Versions](#versions)
- [Installation](#install)
- [Bugs and feedback](#feedback)

<a id="usage"></a>

## Use of use
### How it works.
- What we want: ESP8266/ESP32 push notifications on your smartphone or PC
- **Browser** PC or smartphone (hereinafter referred to as the client) subscribes to notifications **website** using the native mechanism of the browser itself (at the JavaScript level, this is not a backend), the result is a "token"
- Using the token, you can send a push notification to the client from any device that can make HTTP requests (the request for a service serving the browser is different from Chrome/Mozilla/Safari, but the mechanism of work is no different). This is a complex process that uses complex encryption and authentication, so the ESP itself cannot cope with it.
- It is proposed to use an intermediate PHP server that accepts the token from ESP and sends with it push. The option is not the most secure, but very simple and workable, not requiring a database of subscriptions on the server.

Out of the box, my website is available.https://push.gyver.ru/- it both gives the browser a subscription and processes requests from ESP (this is completely free), i.e. notifications will come from the person of this site.

In the repository, I attach the source of the web application and php script - you can raise a similar service in just a couple of clicks on your server with a domain.

### Library
```cpp
GyverPush(Client& client);
GyverPushESP();

// send out
bool send(const String& title, const String& body, const char* token);

// Send one client a PROGMEM token.
bool send_P(const String& title, const String& body, const char* token);

// send out to several clients
bool send(const String& title, const String& body, const char** tokens, uint8_t len);

// send to several clients, PROGMEM tokens, RAM list
bool send_P(const String& title, const String& body, const char** tokens, uint8_t len);

// send from the file, the token divider ';' or '\n'
bool send(const String& title, const String& body, Stream& token);
```

### Receiving a token
Let's go.https://push.gyver.ru/,Click to subscribe, allow notifications. A token will be received – it is unique for each device and browser. The token is quite heavy – about 500 bytes, so it is better to store it in PROGMEM (static) or a file (can be changed during operation) – the library supports sending from the file. The site has two windows with a token - one "raw", the second is decorated for PROGMEM.

### Sending.
The token characterizes the client, i.e. you can send the push to someone specific, and everyone:

```cpp
// single
push.send_P("Hello!", "From esp", push_token1);

// several
push.send_P("Hello!", "From esp", tokens, 2);

// File tokens = ...
// push.send("Hello!", "From esp", tokens);
```

- Tokens can be folded into one line with separation`':'`Sending will be for all specified customers.
- Tokens in a file can also be separated by line transfer`'\n'`conveniently maintaining a customer base

### Your server.
- You will need an SSL-enabled domain (like Let’s Encrypt). The task of the domain is only to display the page of the site, so it can be on another server or even on GitHub Pages.
- You will need a hosting or server with PHP version 8.2 (the script is written for it) and the ability to install your own packages and libraries.
- Install a library on the server`web-push-bundle`: https://packagist.org/packages/minishlink/web-push-bundle
- Generate the VAPID keys:https://vapidkeys.com/
- The public key shall be indicated in`package.json`Web applications and collect it through Node.js
- Write both keys in`push-config.php`And put it next to him.`push.php`
- In the program, specify your host, port and path to the script before connecting the library or through a flag.`-D`:

```cpp
#define GYVER_PUSH_HOST "push.gyver.ru"
#define GYVER_PUSH_PATH "/push.php"
#define GYVER_PUSH_PORT 80

#include <GyverPush.h>
#include <GyverPushESP.h>
```

## Examples
```cpp
#include <Arduino.h>
#include <GyverPushESP.h>

// tokenhttps://push.gyver.ru/
static const char push_token1[] PROGMEM = "";
static const char push_token2[] PROGMEM = "";

const char* tokens[] = {
    push_token1,
    push_token2,
};

void setup() {
    Serial.begin(115200);

    WiFi.begin("", "");
    WiFi.waitForConnectResult();
    Serial.println(WiFi.localIP());

    GyverPushESP push;

    // single
    push.send_P("Hello!", "From esp", push_token1);

    // several
    // push.send_P("Hello!", "From esp", tokens, 2);

    // from the file, the token divider is \n or ;
    // File f = ...
    // push.send("Hello!", "From esp", f);
}

void loop() {
}
```

<a id="versions"></a>

## Versions
- v1.0

<a id="install"></a>
## Installation
- The library can be found under the name **GyverPush** and installed through the library manager in:
    - Arduino IDE
    - Arduino IDE v2
    - PlatformIO
- [Download the library](https://github.com/GyverLibs/GyverPush/archive/refs/heads/main.zip).zip archive for manual installation:
    - Unpack and put in *C:\Program Files (x86)\Arduino\libraries* (Windows x64)
    - Unpack and put in *C:\Program Files\Arduino\libraries* (Windows x32)
    - Unpack and put in *Documents/Arduino/libraries/ *
    - (Arduino IDE) Automatic installation from .zip: *Sketch/Connect library/Add .ZIP library...* and specify downloaded archive
- Read more detailed instructions for installing libraries[here](https://alexgyver.ru/arduino-first/#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D0%B1%D0%B8%D0%B1%D0%BB%D0%B8%D0%BE%D1%82%D0%B5%D0%BA)
### Update
- I recommend always updating the library: new versions fix errors and bugs, as well as optimize and add new features.
- Through the library manager IDE: find the library as when installing and click "Update"
- Manually: **Delete the folder with the old version** and then put the new one in its place. “Replacement” can not be done: sometimes new versions delete files that will remain when replaced and can lead to errors!

<a id="feedback"></a>

## Bugs and feedback
If you find bugs, create **Issue**, or better write to the mail immediately.[alex@alexgyver.ru](mailto:alex@alexgyver.ru)  
The library is open for revision and your **Pull Requests*!

When reporting bugs or incorrect work of the library, it is necessary to specify:
- Library version
- What is used by the IC
- SDK version (for ESP)
- Arduino IDE version
- Are embedded examples that use features and designs that cause bugs in your code working correctly?
- What code was downloaded, what work was expected from it and how it works in reality
- Ideally, attach the minimum code in which the bug is observed. Not a canvas of a thousand lines, but a minimum code.
