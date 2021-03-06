/*
 * Copyright 2018 Anthony Calabretta
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#include <EEPROM.h>
#include <SoftwareSerial.h>

// Set your PINs here
const byte rPin = 9;
const byte gPin = 10;
const byte bPin = 11;

// Set your identifier here
String id = "Arduino over BT";

// Set your Bluetooth TX pin here (PWM)
const byte txPin = 6;
// Set your Bluetooth RX pin here (Non-PWM)
const byte rxPin = 7;

// SoftwareSerial object
SoftwareSerial BTSerial = SoftwareSerial(rxPin, txPin);

// Variables used in the loop
String inString;
int rIntensity;
int gIntensity;
int bIntensity;

// Setup pins and restore saved color
void setup(){
  pinMode(rPin, OUTPUT);
  pinMode(gPin, OUTPUT);
  pinMode(bPin, OUTPUT);
  restoreColors();
  writeColors();
  BTSerial.begin(9600);
  BTSerial.println("init");
}

// Loop, read from serial and update color, maybe save the color to EEPROM
void loop(){
  if(BTSerial.available() > 0){
    inString = BTSerial.readStringUntil('\n');
    if(inString.equals("save")){
      saveColors();
      return;
    }
    if(inString.equals("id")){
      Serial.println(id);
      return;
    }

    // r000g000b000
    if(inString.length() != 12) return;
    rIntensity = inString.substring(1, 4).toInt();
    gIntensity = inString.substring(5, 8).toInt();
    bIntensity = inString.substring(9, 12).toInt();
    writeColors();
  }
}

// FUNCTIONS -----------------------------

// write colors to RGB LED strip
void writeColors(){
  analogWrite(rPin, 255 - rIntensity);
  analogWrite(gPin, 255 - gIntensity);
  analogWrite(bPin, 255 - bIntensity);
}

// save cached colors to memory
void saveColors(){
  EEPROM.write(0, rIntensity);
  EEPROM.write(1, gIntensity);
  EEPROM.write(2, bIntensity);
}

// restore cached colors to memory
void restoreColors(){
  rIntensity = EEPROM.read(0);
  gIntensity = EEPROM.read(1);
  bIntensity = EEPROM.read(2);
}
