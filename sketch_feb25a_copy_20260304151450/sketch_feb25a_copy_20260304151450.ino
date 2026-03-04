#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "DHT.h"

// ---------- PIN CONFIG ----------
#define DHTPIN 4
#define DHTTYPE DHT11
#define PIR 5
#define LDR 34
#define RELAY 18
#define BUZZER 19
#define LED_WIFI 2
#define LED_ALERT 15

// ---------- WIFI ----------
const char* ssid = "SUWIPHA.";
const char* password = "19072546";
String server = "http://10.124.178.39/iot/";

// ---------- API KEY ----------
String apiKey = "123456789";

DHT dht(DHTPIN, DHTTYPE);

bool webRelay = false;
bool webBuzzer = false;

unsigned long lastSend = 0;
unsigned long sendInterval = 5000;
unsigned long systemStartTime;

// 🔥 ปรับ threshold ตามค่าจริง (ดูจาก Serial)
int darkThreshold = 1500;

void setup() {
  Serial.begin(115200);

  pinMode(PIR, INPUT);
  pinMode(RELAY, OUTPUT);
  pinMode(LED_WIFI, OUTPUT);
  pinMode(LED_ALERT, OUTPUT);

  digitalWrite(RELAY, LOW);
  digitalWrite(LED_ALERT, LOW);

  // PWM Buzzer (ESP32 Core v3)
  ledcAttach(BUZZER, 2000, 8);
  ledcWrite(BUZZER, 0);

  dht.begin();
  connectWiFi();

  systemStartTime = millis();
}

void connectWiFi() {
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    digitalWrite(LED_WIFI, HIGH);
    delay(300);
    digitalWrite(LED_WIFI, LOW);
    delay(300);
  }

  digitalWrite(LED_WIFI, HIGH);
  Serial.println("WiFi Connected");
}

void loop() {

  float temp = dht.readTemperature();
  float hum = dht.readHumidity();
  int lightValue = analogRead(LDR);
  int motion = digitalRead(PIR);

  bool autoRelay = false;
  bool autoBuzzer = false;

  if (!isnan(temp) && temp > 35) {
    autoRelay = true;
  }

  // 🔥 รอ 15 วิแรกก่อนเริ่มระบบ
  if (millis() - systemStartTime > 15000) {

    // 🔥 แก้ตรงนี้: มืด = ค่าสูง
    if (motion == 1 && lightValue > darkThreshold) {
      autoBuzzer = true;
    }
  }

  // ---------- ONLINE ----------
  if (WiFi.status() == WL_CONNECTED &&
      millis() - lastSend > sendInterval) {

    lastSend = millis();
    HTTPClient http;

    // ส่งข้อมูล
    http.begin(server + "insert.php");
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String data = "api_key=" + apiKey;
    data += "&temperature=" + String(temp);
    data += "&humidity=" + String(hum);
    data += "&light=" + String(lightValue);
    data += "&motion=" + String(motion);

    http.POST(data);
    http.end();

    // รับคำสั่ง
    http.begin(server + "get_control.php");
    int httpCode = http.GET();

    if (httpCode == 200) {
      String payload = http.getString();

      StaticJsonDocument<200> doc;
      DeserializationError error = deserializeJson(doc, payload);

      if (!error) {
        webRelay = doc["relay"].as<int>() == 1;
        webBuzzer = doc["buzzer"].as<int>() == 1;
      } else {
        webRelay = false;
        webBuzzer = false;
      }
    }

    http.end();
  }

  // ---------- DEBUG ----------
  Serial.print("Light: "); Serial.println(lightValue);
  Serial.print("Motion: "); Serial.println(motion);
  Serial.print("webBuzzer: "); Serial.println(webBuzzer);
  Serial.print("Web Relay Status: "); Serial.println(webRelay);
  Serial.print("Auto Relay Status: "); Serial.println(autoRelay);
  Serial.println("-------------------");

  // ---------- OUTPUT ----------
  digitalWrite(RELAY, autoRelay || webRelay);

  bool finalBuzzer = autoBuzzer || webBuzzer;

  if (finalBuzzer) {
    ledcWrite(BUZZER, 60);
    digitalWrite(LED_ALERT, HIGH);
  } else {
    ledcWrite(BUZZER, 0);
    digitalWrite(LED_ALERT, LOW);
  }

  delay(500);
}