#include <Arduino.h>

#ifdef ESP8266
#include <ESP8266WiFi.h>
#else
#include <WiFi.h>
#endif

#include <GyverPush.h>
#include <WiFiClient.h>

// токены брать тут https://push.gyver.ru/
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

    // одному
    push.send_P("Hello!", "From esp", push_token1);

    // нескольким
    // push.send_P("Hello!", "From esp", tokens, 2);

    // из файла, разделитель токенов - \n или ;
    // File f = ...
    // push.send("Hello!", "From esp", f);
}

void loop() {
}