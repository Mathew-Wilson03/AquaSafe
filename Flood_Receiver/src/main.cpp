#include <Arduino.h>
#include <SPI.h>
#include <LoRa.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ─────────────────────────────────────────
// ⚙️ CONFIGURATION
// ─────────────────────────────────────────
const char* WIFI_SSID     = "iPhone XIII";       
const char* WIFI_PASSWORD = "akashmathew@2004";   
const char* SERVER_URL    = "http://172.20.10.2/AquaSafe/Login/receive_iot_data.php";

#define SS 5
#define RST 4
#define DIO0 21

void connectWiFi() {
  Serial.print("Connecting to Wi-Fi: ");
  Serial.println(WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500); Serial.print("."); attempts++;
  }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ Wi-Fi Connected!");
  } else {
    Serial.println("\n❌ Wi-Fi connection FAILED.");
  }
}

// ─────────────────────────────────────────
// 🔧 Enhanced Parser: ID:1|Level:XX.X|Status:XXXX
// ─────────────────────────────────────────
String getVal(String data, char separator, int index) {
  int found = 0;
  int strIndex[] = {0, -1};
  int maxIndex = data.length() - 1;
  for (int i = 0; i <= maxIndex && found <= index; i++) {
    if (data.charAt(i) == separator || i == maxIndex) {
      found++;
      strIndex[0] = strIndex[1] + 1;
      strIndex[1] = (i == maxIndex) ? i + 1 : i;
    }
  }
  return found > index ? data.substring(strIndex[0], strIndex[1]) : "";
}

void sendToServer(int id, float level, String status) {
  if (WiFi.status() != WL_CONNECTED) connectWiFi();

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String postBody = "sensor_id=" + String(id) + "&level=" + String(level, 2) + "&status=" + status;
    Serial.println("📤 Relaying: " + postBody);

    int httpCode = http.POST(postBody);
    if (httpCode > 0) Serial.println("✅ OK: " + http.getString());
    else Serial.printf("❌ HTTP Error: %s\n", http.errorToString(httpCode).c_str());
    http.end();
  }
}

void setup() {
  Serial.begin(115200);
  LoRa.setPins(SS, RST, DIO0);
  if (!LoRa.begin(433E6)) {
    Serial.println("LoRa init failed!");
    while (true);
  }
  connectWiFi();
  Serial.println("👂 Gateway Ready...");
}

void loop() {
  int packetSize = LoRa.parsePacket();
  if (packetSize) {
    String received = "";
    while (LoRa.available()) received += (char)LoRa.read();
    Serial.println("\n📥 Received: " + received);

    // Format: ID:1|Level:5.00|Status:SAFE
    String sId    = getVal(received, '|', 0); // "ID:1"
    String sLevel = getVal(received, '|', 1); // "Level:5.00"
    String sStat  = getVal(received, '|', 2); // "Status:SAFE"

    int id       = sId.substring(sId.indexOf(':') + 1).toInt();
    float level  = sLevel.substring(sLevel.indexOf(':') + 1).toFloat();
    String status = sStat.substring(sStat.indexOf(':') + 1);

    if (id > 0) {
      sendToServer(id, level, status);
    } else {
      Serial.println("⚠ Parsing failed.");
    }
  }
}
