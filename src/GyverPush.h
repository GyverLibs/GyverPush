#pragma once
#include <Arduino.h>
#include <Client.h>

#ifndef GYVER_PUSH_HOST
#define GYVER_PUSH_HOST "push.gyver.ru"
#endif

#ifndef GYVER_PUSH_PATH
#define GYVER_PUSH_PATH "/push.php"
#endif

#ifndef GYVER_PUSH_PORT
#define GYVER_PUSH_PORT 80
#endif

class GyverPush {
   public:
    GyverPush(Client& client) : _client(client) {}

    // отправить одному клиенту
    bool send(const String& title, const String& body, const char* token) {
        if (!_beginRequest()) return false;
        _client.print(token);
        return _endRequest(title, body);
    }

    // отправить одному клиенту, PROGMEM-токен
    bool send_P(const String& title, const String& body, const char* token) {
        if (!_beginRequest()) return false;
        _client.print((const __FlashStringHelper*)token);
        return _endRequest(title, body);
    }

    // отправить нескольким клиентам
    bool send(const String& title, const String& body, const char** tokens, uint8_t len) {
        if (!_beginRequest()) return false;

        while (len--) {
            _client.print(tokens[len]);
            if (len) _client.print(';');
        }

        return _endRequest(title, body);
    }

    // отправить нескольким клиентам, PROGMEM-токены, список RAM
    bool send_P(const String& title, const String& body, const char** tokens, uint8_t len) {
        if (!_beginRequest()) return false;

        while (len--) {
            _client.print((const __FlashStringHelper*)tokens[len]);
            if (len) _client.print(';');
        }

        return _endRequest(title, body);
    }

    // отправить из файла, разделитель токенов ';' или '\n'
    bool send(const String& title, const String& body, Stream& token) {
        if (!_beginRequest()) return false;

        uint8_t buf[128];

        while (token.available()) {
            size_t len = token.readBytes((char*)buf, sizeof(buf));
            size_t n = len;
            while (n--) {
                if (buf[n] == '\n' || buf[n] == '\r') buf[n] = ';';
            }
            if (len) _client.write(buf, len);
        }

        return _endRequest(title, body);
    }

   private:
    Client& _client;

    bool _beginRequest() {
        if (!_client.connect(GYVER_PUSH_HOST, GYVER_PUSH_PORT)) return false;

        _client.print(F("POST " GYVER_PUSH_PATH
                        " HTTP/1.1"
                        "\r\nHost: " GYVER_PUSH_HOST
                        "\r\nConnection: close"
                        "\r\nPush-Token: "));
        return true;
    }

    bool _endRequest(const String& title, const String& body) {
        String req;
        req += F("\r\nPush-Title: ");
        req += title;
        req += F("\r\nPush-Body: ");
        req += body;
        req += F("\r\n\r\n");
        _client.print(req);

        _client.flush();
        _client.stop();
        return true;
    }
};