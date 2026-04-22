#pragma once
#include <Arduino.h>

#ifdef ESP8266
#include <ESP8266WiFi.h>
#else
#include <WiFi.h>
#endif

#include <GyverPush.h>
#include <WiFiClient.h>

class GyverPushESP : public GyverPush {
   public:
    GyverPushESP() : GyverPush(client) {}

    WiFiClient client;
};