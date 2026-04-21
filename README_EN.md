This is an automatic translation, may be incorrect in some places. See sources and examples!

# GyverPush
Library for sending PUSH notifications from Arduino

### Compatibility
Compatible with all Arduino platforms (uses Arduino functions)

## Contents
- [Usage](#usage)
- [Versions](#versions)
- [Installation](#install)
- [Bugs and feedback](#feedback)

<a id="usage"></a>

## Usage
### How it works
- What we want: to receive a push notification from the ESP8266/ESP32 to a smartphone or PC
- **Browser** of a PC or smartphone (hereinafter referred to as the client) subscribes to notifications using a native mechanism, resulting in a “token”
- Using a token, you can send a notification to the client.This is a complex process that uses special encryption, so the ESP itself cannot handle it
- It is proposed to use an intermediate PHP server that accepts a token from the esp and sends a push using it.The option is not the most secure, but it is very simple and working, and does not require maintaining a subscription database on the server

My website https://push.gyver.ru/ is available “out of the box” - it both gives the browser a subscription and processes requests from the ESP.Those.notifications will come on behalf of this site, and the library will contact the same server to send push messages.In the repository I am attaching the source code of the web application and a php script - you can create a similar service to push.gyver.ru in just a couple of clicks on your server and website.

### Library
```cpp
GyverPush(Client& client);

// configure to another server
void setEndpoint(const char* host, uint16_t port, const char* path);

// send to one client
bool send(const String& title, const String& body, const char* token);

// send to one client, PROGMEM token
bool send_P(const String& title, const String& body, const char* token);

// send to multiple clients
bool send(const String& title, const String& body, const char** tokens, uint8_t len);

// send to several clients, PROGMEM tokens, RAM list
bool send_P(const String& title, const String& body, const char** tokens, uint8_t len);

// send from file, token separator ';'or '\n'
bool send(const String& title, const String& body, Stream& token);
```

### Receiving a token
Go to https://push.gyver.ru/, click subscribe, enable notifications.A token will be received - it is unique for each device and browser.The token is quite heavy - about 500 bytes, so it is better to store it in PROGMEM (static) or a file (can be changed during operation).There are two windows with a token on the site - one is “raw”, the second is designed for PROGMEM.

### Dispatch
The token characterizes the client, i.e.You can send a push to someone specific or to everyone:

```cpp
// alone
push.send_P("Hello!", "From esp", push_token1);

// several
push.send_P("Hello!", "From esp", tokens, 2);

// File tokens = ...
// push.send("Hello!", "From esp", tokens);
```

- Tokens can be combined into one line separated by `':'`, sent to all specified clients
- Tokens in the file can also be separated by a line break `'\n'` for convenient maintenance of the client database

### Own server
- Install on php serverlibrary `web-push-bundle`: https://packagist.org/packages/minishlink/web-push-bundle
- Generate VAPID keys: https://vapidkeys.com/
- Specify the public key in `package.json` of the web application and collect it via Node.js
- Write both keys in `push-config.php` and put it next to `push.php`
- In GyverPush, specify your server, port and path to `push.php`

## Examples
```cpp
#include <Arduino.h>

#ifdef ESP8266
#include <ESP8266WiFi.h>
#else
#include <WiFi.h>
#endif

#include <GyverPush.h>
#include <WiFiClient.h>

// get tokens here https://push.gyver.ru/
static const char push_token1[] PROGMEM = "";
static const char push_token2[] PROGMEM = "";

const char* tokens[] = {
push_token1,
push_token2,
};

WiFiClient client;
GyverPush push(client);

void setup() {
Serial.begin(115200);

WiFi.begin("", "");
WiFi.waitForConnectResult();
Serial.println(WiFi.localIP());

// alone
push.send("Hello!", "From esp", push_token1, true);

// several
// push.send("Hello!", "From esp", tokens, 2, true);

// from a file, token separator - \n or ;
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
- The library can be found by the name **GyverPush** and installed through the library manager in:
- Arduino IDE
- Arduino IDE v2
- PlatformIO
- [Download library](https://github.com/GyverLibs/GyverPush/archive/refs/heads/main.zip).zip archive for manual installation:
- Unpack and put in *C:\Program Files (x86)\Arduino\libraries* (Windows x64)
- Unpack and put in *C:\Program Files\Arduino\libraries* (Windows x32)
- Unpack and put in *Documents/Arduino/libraries/*
- (Arduino IDE) automatic installation from .zip: *Sketch/Connect library/Add .ZIP library…* and indicate the downloaded archive
- Read more detailed instructions for installing libraries[here](https://alexgyver.ru/arduino-first/#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D0%B1%D0%B8%D0%B1%D0%BB%D0%B8%D0%BE%D1%82%D0%B5%D0%BA)
### Update
- I recommend always updating the library: in new versions errors and bugs are corrected, as well as optimization is carried out and new features are added
- Through the IDE library manager: find the library as during installation and click "Update"
- Manually: **delete the folder with the old version**, and then put the new one in its place.“Replacement” cannot be done: sometimes new versions delete files that will remain after replacement and can lead to errors!

<a id="feedback"></a>

## Bugs and feedback
When you find bugs, create an **Issue**, or better yet, immediately write to [alex@alexgyver.ru](mailto:alex@alexgyver.ru)
The library is open for improvement and your **Pull Requests**!

When reporting bugs or incorrect operation of the library, be sure to indicate:
- Library version
- Which MK is used?
- SDK version (for ESP)
- Arduino IDE version
- Do the built-in examples that use functions and constructs that lead to a bug in your code work correctly?
- What code was loaded, what work was expected from it and how it works in reality
- Ideally, attach the minimum code in which the bug is observed.Not a canvas of a thousand lines, but minimal code