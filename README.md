[![latest](https://img.shields.io/github/v/release/GyverLibs/GyverPush.svg?color=brightgreen)](https://github.com/GyverLibs/GyverPush/releases/latest/download/GyverPush.zip)
[![PIO](https://badges.registry.platformio.org/packages/gyverlibs/library/GyverPush.svg)](https://registry.platformio.org/libraries/gyverlibs/GyverPush)
[![Foo](https://img.shields.io/badge/Website-AlexGyver.ru-blue.svg?style=flat-square)](https://alexgyver.ru/)
[![Foo](https://img.shields.io/badge/%E2%82%BD%24%E2%82%AC%20%D0%9F%D0%BE%D0%B4%D0%B4%D0%B5%D1%80%D0%B6%D0%B0%D1%82%D1%8C-%D0%B0%D0%B2%D1%82%D0%BE%D1%80%D0%B0-orange.svg?style=flat-square)](https://alexgyver.ru/support_alex/)
[![Foo](https://img.shields.io/badge/README-ENGLISH-blueviolet.svg?style=flat-square)](https://github-com.translate.goog/GyverLibs/GyverPush?_x_tr_sl=ru&_x_tr_tl=en)  

[![Foo](https://img.shields.io/badge/ПОДПИСАТЬСЯ-НА%20ОБНОВЛЕНИЯ-brightgreen.svg?style=social&logo=telegram&color=blue)](https://t.me/GyverLibs)

# GyverPush
Библиотека для отправки PUSH уведомлений с Arduino

### Совместимость
Совместима со всеми Arduino платформами (используются Arduino-функции)

## Содержание
- [Использование](#usage)
- [Версии](#versions)
- [Установка](#install)
- [Баги и обратная связь](#feedback)

<a id="usage"></a>

## Использование
### Как это работает
- Что мы хотим: получать от ESP8266/ESP32 пуш-уведомления на смартфон или ПК
- **Браузер** ПК или смартфона (далее - клиент) подписывается на уведомления **веб-сайта** при помощи нативного механизма самого браузера (на уровне JavaScript, это не бэкэнд), в результате получается "токен"
- Используя токен, можно отправить клиенту пуш-уведомление с любого устройства, которое умеет делать HTTP-запросы (запрос на сервис, обслуживающий браузер, у Chrome/Mozilla/Safari они разные, но механизм работы не отличается). Это непростой процесс, использующий сложное шифрование и аутентификацию, поэтому сама ESP-шка с ним не справится
- Предлагается использовать промежуточный PHP сервер, который принимает токен от ESP и отправляет с его помощью пуш. Вариант не самый безопасный, но очень простой и рабочий, не требующий ведения базы данных подписок на сервере

"Из коробки" доступен мой сайт https://push.gyver.ru/ - он как даёт браузеру подписку, так и обрабатывает запросы от ESP (это полностью бесплатно), т.е. уведомления будут приходить от лица этого сайта.

В репозитории прилагаю исходник веб-приложения и php скрипт - можно поднять аналогичный сервис буквально в пару кликов на своём сервере с доменом.

### Библиотека
```cpp
GyverPush(Client& client);

// настроить на другой сервер
void setEndpoint(const char* host, uint16_t port, const char* path);

// отправить одному клиенту
bool send(const String& title, const String& body, const char* token);

// отправить одному клиенту, PROGMEM-токен
bool send_P(const String& title, const String& body, const char* token);

// отправить нескольким клиентам
bool send(const String& title, const String& body, const char** tokens, uint8_t len);

// отправить нескольким клиентам, PROGMEM-токены, список RAM
bool send_P(const String& title, const String& body, const char** tokens, uint8_t len);

// отправить из файла, разделитель токенов ';' или '\n'
bool send(const String& title, const String& body, Stream& token);
```

### Получение токена
Заходим на https://push.gyver.ru/, жмём подписаться, разрешаем уведомления. Будет получен токен - он уникален для каждого устройства и браузера. Токен довольно тяжёлый - около 500 байт, поэтому хранить его лучше в PROGMEM (статично) или файле (можно менять в процессе работы) - библиотека поддерживает отправку из файла. На сайте два окошка с токеном - один "сырой", второй оформлен для PROGMEM.

### Отправка
Токен характеризует клиента, т.е. можно отправить пуш как кому-то конкретному, так и всем:

```cpp
// одному
push.send_P("Hello!", "From esp", push_token1);

// нескольким
push.send_P("Hello!", "From esp", tokens, 2);

// File tokens = ...
// push.send("Hello!", "From esp", tokens);
```

- Токены можно сложить в одну строку с разделением `':'`, отправка будет всем указанным клиентам
- Токены в файле можно разделять также переносом строки `'\n'` для удобного ведения базы клиентов

### Свой сервер
- Понадобится хостинг или сервер с PHP версии 8.2 (скрипт написан под неё) и возможностью установки своих пакетов и библиотек
- Понадобится домен с поддержкой SSL (например Let's Encrypt). Домен не обязательно должен быть на этом же хостинге! Сойдёт и бесплатный GitHub Pages. Задача домена и сайта - только отобразить страничку веб-аппы
- Установить на сервер библиотеку `web-push-bundle`: https://packagist.org/packages/minishlink/web-push-bundle
- Сгенерировать VAPID ключи: https://vapidkeys.com/
- Публичный ключ указать в `package.json` веб-приложения и собрать его через Node.js
- Оба ключа записать в `push-config.php` и положить его рядом с `push.php`
- В GyverPush указать свой сервер, порт и путь к php-скрипту

## Примеры
```cpp
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
```

<a id="versions"></a>

## Версии
- v1.0

<a id="install"></a>
## Установка
- Библиотеку можно найти по названию **GyverPush** и установить через менеджер библиотек в:
    - Arduino IDE
    - Arduino IDE v2
    - PlatformIO
- [Скачать библиотеку](https://github.com/GyverLibs/GyverPush/archive/refs/heads/main.zip) .zip архивом для ручной установки:
    - Распаковать и положить в *C:\Program Files (x86)\Arduino\libraries* (Windows x64)
    - Распаковать и положить в *C:\Program Files\Arduino\libraries* (Windows x32)
    - Распаковать и положить в *Документы/Arduino/libraries/*
    - (Arduino IDE) автоматическая установка из .zip: *Скетч/Подключить библиотеку/Добавить .ZIP библиотеку…* и указать скачанный архив
- Читай более подробную инструкцию по установке библиотек [здесь](https://alexgyver.ru/arduino-first/#%D0%A3%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D0%B1%D0%B8%D0%B1%D0%BB%D0%B8%D0%BE%D1%82%D0%B5%D0%BA)
### Обновление
- Рекомендую всегда обновлять библиотеку: в новых версиях исправляются ошибки и баги, а также проводится оптимизация и добавляются новые фичи
- Через менеджер библиотек IDE: найти библиотеку как при установке и нажать "Обновить"
- Вручную: **удалить папку со старой версией**, а затем положить на её место новую. "Замену" делать нельзя: иногда в новых версиях удаляются файлы, которые останутся при замене и могут привести к ошибкам!

<a id="feedback"></a>

## Баги и обратная связь
При нахождении багов создавайте **Issue**, а лучше сразу пишите на почту [alex@alexgyver.ru](mailto:alex@alexgyver.ru)  
Библиотека открыта для доработки и ваших **Pull Request**'ов!

При сообщении о багах или некорректной работе библиотеки нужно обязательно указывать:
- Версия библиотеки
- Какой используется МК
- Версия SDK (для ESP)
- Версия Arduino IDE
- Корректно ли работают ли встроенные примеры, в которых используются функции и конструкции, приводящие к багу в вашем коде
- Какой код загружался, какая работа от него ожидалась и как он работает в реальности
- В идеале приложить минимальный код, в котором наблюдается баг. Не полотно из тысячи строк, а минимальный код