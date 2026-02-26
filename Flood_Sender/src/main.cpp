#include <Arduino.h>
#include <SPI.h>
#include <LoRa.h>

// ─────────────────────────────────────────
// ⚙️ CONFIGURATION
// ─────────────────────────────────────────
#define SENSOR_ID 1   // Churakullam, Kakkikavala, & Nellimala
#define SS 5
#define RST 4
#define DIO0 21
#define WATER_SENSOR 34

float maxHeightFt = 25.0; 
int   rawMax      = 3500; 

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n============================");
  Serial.printf("  AquaSafe Sender ID: %d\n", SENSOR_ID);
  Serial.println("============================");

  LoRa.setPins(SS, RST, DIO0);
  if (!LoRa.begin(433E6)) {
    Serial.println("❌ LoRa init failed!");
    while (true);
  }
  Serial.println("✅ LoRa Initialized on 433 MHz");
}

void loop() {
  int rawValue = analogRead(WATER_SENSOR);
  float waterFeet = (float)rawValue * maxHeightFt / (float)rawMax;
  if (waterFeet > maxHeightFt) waterFeet = maxHeightFt;

  String status;
  if (waterFeet < 10.0) status = "SAFE";
  else if (waterFeet < 18.0) status = "WARNING";
  else status = "CRITICAL";

  // New Message Format: "ID:1|Level:XX.X|Status:XXXX"
  String message = "ID:" + String(SENSOR_ID) + "|Level:" + String(waterFeet, 2) + "|Status:" + status;
  
  LoRa.beginPacket();
  LoRa.print(message);
  LoRa.endPacket();

  Serial.println("📤 Sent: " + message);
  delay(3000); 
}
