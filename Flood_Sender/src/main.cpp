#include <Arduino.h>
#include <SPI.h>
#include <LoRa.h>

#define SS 5
#define RST 4
#define DIO0 21
#define WATER_SENSOR 34

float maxHeightFt = 20.0;

void setup() {
  Serial.begin(115200);

  LoRa.setPins(SS, RST, DIO0);

  if (!LoRa.begin(433E6)) {
    Serial.println("LoRa init failed!");
    while (true);
  }

  Serial.println("Flood Sender Ready...");
}

void loop() {

  int waterLevel = analogRead(WATER_SENSOR);
  float waterFeet = (float)waterLevel * maxHeightFt / 3500.0;

  String status;

  if (waterFeet < 10) status = "SAFE";
  else if (waterFeet <= 15) status = "WARNING";
  else status = "CRITICAL";

  // Serial Output
  Serial.print("Water Level: ");
  Serial.print(waterFeet);
  Serial.println(" ft");

  Serial.print("Status: ");
  Serial.println(status);

  // Send via LoRa
  String message = "Level: " + String(waterFeet) + " ft | Status: " + status;

  LoRa.beginPacket();
  LoRa.print(message);
  LoRa.endPacket();

  Serial.println("-------------------");
  delay(3000);
}
