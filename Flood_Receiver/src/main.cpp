#include <Arduino.h>
#include <SPI.h>
#include <LoRa.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ─────────────────────────────────────────
// ⚙️ CONFIGURATION — CHANGE THESE VALUES
// ─────────────────────────────────────────
const char* WIFI_SSID     = "iPhone XIII";       // Your Wi-Fi SSID
const char* WIFI_PASSWORD = "akashmathew@2004";   // Your Wi-Fi Password

// Your PC's local IP running XAMPP
// Find it by running: ipconfig (Windows) / ifconfig (Mac/Linux)
// Example: "http://192.168.1.5/AquaSafe/Login/receive_iot_data.php"
const char* SERVER_URL    = "http://172.20.10.2/AquaSafe/Login/receive_iot_data.php";

// ─────────────────────────────────────────
// 📡 LoRa Pin Configuration (SX1276/SX1278)
// ─────────────────────────────────────────
#define SS   5
#define RST  4
#define DIO0 21

// ─────────────────────────────────────────
// 🔧 Helper: Connect to Wi-Fi
// ─────────────────────────────────────────
void connectWiFi() {
  Serial.print("Connecting to Wi-Fi: ");
  Serial.println(WIFI_SSID);

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ Wi-Fi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n❌ Wi-Fi connection FAILED. Will retry on next alert.");
  }
}

// ─────────────────────────────────────────
// 🔧 Helper: Parse float from LoRa message
// Example message: "Level: 3.20 ft | Status: SAFE"
// ─────────────────────────────────────────
float parseLevel(String msg) {
  // Find "Level: " and extract the number before " ft"
  int startIdx = msg.indexOf("Level: ");
  int endIdx   = msg.indexOf(" ft");

  if (startIdx == -1 || endIdx == -1) return -1.0;

  String levelStr = msg.substring(startIdx + 7, endIdx);
  return levelStr.toFloat();
}

String parseStatus(String msg) {
  if (msg.indexOf("CRITICAL") >= 0) return "CRITICAL";
  if (msg.indexOf("WARNING")  >= 0) return "WARNING";
  if (msg.indexOf("SAFE")     >= 0) return "SAFE";
  return "UNKNOWN";
}

// ─────────────────────────────────────────
// 🔧 Helper: POST data to PHP API
// ─────────────────────────────────────────
void sendToServer(float level, String status) {
  // Reconnect Wi-Fi if dropped
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠ Wi-Fi disconnected. Reconnecting...");
    connectWiFi();
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("❌ Cannot send — no Wi-Fi.");
    return;
  }

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  // Build POST body
  String postBody = "level=" + String(level, 2) + "&status=" + status;

  Serial.println("📤 Sending to server: " + postBody);

  int httpCode = http.POST(postBody);

  if (httpCode == HTTP_CODE_OK) {
    String response = http.getString();
    Serial.println("✅ Server Response: " + response);
  } else {
    Serial.print("❌ HTTP Error Code: ");
    Serial.println(httpCode);
  }

  http.end();
}

// ─────────────────────────────────────────
// 🚀 SETUP
// ─────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n============================");
  Serial.println("  AquaSafe Flood Receiver");
  Serial.println("============================");

  // Init LoRa
  LoRa.setPins(SS, RST, DIO0);
  if (!LoRa.begin(433E6)) {
    Serial.println("❌ LoRa init failed!");
    while (true);  // Halt
  }
  Serial.println("✅ LoRa Initialized on 433 MHz");

  // Connect to Wi-Fi
  connectWiFi();

  Serial.println("👂 Waiting for LoRa alerts...\n");
}

// ─────────────────────────────────────────
// 🔄 LOOP — Listen for LoRa packets
// ─────────────────────────────────────────
void loop() {
  int packetSize = LoRa.parsePacket();

  if (packetSize) {
    String received = "";

    while (LoRa.available()) {
      received += (char)LoRa.read();
    }

    Serial.println("\n>>> LORA PACKET RECEIVED <<<");
    Serial.println("Raw: " + received);
    Serial.print("Signal (RSSI): ");
    Serial.println(LoRa.packetRssi());

    // Parse values from message
    float  level  = parseLevel(received);
    String status = parseStatus(received);

    Serial.println("Level : " + String(level, 2) + " ft");
    Serial.println("Status: " + status);

    // Validate before sending
    if (level < 0 || status == "UNKNOWN") {
      Serial.println("⚠ Invalid message format. Skipping.");
    } else {
      sendToServer(level, status);
    }

    Serial.println("----------------------------");
  }
}
