# Solar Tracker – Industrial Solar Monitoring

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Mosquitto](https://img.shields.io/badge/Mosquitto-2.x-7A3E9D?style=flat&logo=eclipse-mosquitto&logoColor=white)](https://mosquitto.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat&logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-43%20Passed%20(271%20Assertions)-emerald?style=flat)]()

Production-minded, full-stack supervisory control and data acquisition (SCADA) web application for an industrial pilot dual-axis solar tracker controlled by an ESP32 edge microcontroller. Runs entirely containerized in Docker with sub-second MQTT communication, autonomous safety interlocks, and real-time kinematic visualization.

---

## 1. Hardware Architecture & System Boundaries

The system is strictly engineered around the physical sensors and actuators installed on the pilot tracking frame:

```
                          ┌──────────────────────────────────────────────┐
                          │         ESP32 Edge Microcontroller           │
                          └──────┬────────────┬────────────┬─────────────┘
                                 │            │            │
            ┌────────────────────┼────────────┼────────────┼────────────────────┐
            │                    │            │            │                    │
     [ I2C Bus ]           [ UART1/Modbus ]  [ UART2 ]   [ Pulse/Analog ]    [ GPIO / PWM ]
            │                    │            │            │                    │
    ┌───────┴───────┐     ┌──────┴──────┐ ┌───┴────┐ ┌─────┴──────┐      ┌──────┴──────┐
    │ BNO055 9-DOF  │     │  PZEM-017   │ │ GPS    │ │ Wind Speed │      │  2x BTS7960 │
    │ IMU (Heading, │     │ DC Voltage, │ │ M8N    │ │ Anemometer │      │ H-Bridges   │
    │ Pitch, Roll,  │     │ Current,    │ │ UTC,   │ │ (m/s)      │      │ Actuator 1: │
    │ Calibration)  │     │ Power, Wh   │ │ Sats,  │ │            │      │ Elevation   │
    ├───────────────┤     │ (Solar      │ │ Fix,   │ │            │      │ Actuator 2: │
    │ DS3231 RTC    │     │  Array)     │ │ HDOP   │ │            │      │ Azimuth     │
    │ (UTC Time &   │     └─────────────┘ └────────┘ └────────────┘      └─────────────┘
    │  Temp °C)     │
    └───────────────┘
```

> [!IMPORTANT]
> **Strict Hardware Boundary:** There is **NO** battery sensor, **NO** BMS, **NO** solar irradiance sensor, **NO** inverter, and **NO** load cells. The PZEM-017 is the single DC measurement point (default label: *"Solar array"*).

---

## 2. Authentication & User Management

Authentication uses session-based RBAC with three defined operational tiers. All credentials are securely hashed in the database; there are no hardcoded accounts or seeders.

| Role | Permissions & Scope |
| :--- | :--- |
| **Admin** | Full administrative access: User Management (CRUD, status toggle, password reset), Device CRUD, System Settings, Command Control, Alert Resolution. |
| **Operator** | Operational control: Manual slewing (`move_to`), Mode change (`set_mode`), Stow, E-Stop, Acknowledge & Resolve alerts, View & update device configs. |
| **Viewer** | Read-only telemetry monitoring, interactive waveforms, report generator, CSV exports. *(Control panels and mutations are strictly locked with 403 Forbidden)* |

### Creating the Initial Admin Account
Run the interactive artisan command:
```bash
php artisan user:create
```
Follow the interactive prompts to specify the full name, email address, role (`admin`), and a secure password (minimum 8 characters). Subsequent accounts (Operators and Viewers) can be created and managed via the **User Management** menu (`/user-management`) by an administrator. Public self-registration is disabled.

---

## 3. Quick Start: Local Run Guide (Docker)

The complete stack runs with a single command via Docker Compose.

### Prerequisites
- Docker Engine 24+ & Docker Compose v2+
- Git

### 1. Clone & Configure Environment
```bash
git clone https://github.com/your-org/solar-tracker.git
cd solar-tracker

# Copy environment template
cp .env.example .env
```

### 2. Build and Launch Containers
```bash
# Start all 7 services in background
docker compose up -d --build
```

### 3. Initialize Database & Seed Pilot Data
```bash
# Run database migrations and seed default site, users, and ST-DEMO-001 tracker
docker compose exec app php artisan migrate:fresh --seed
```

### 4. Access the Web Dashboard
Open your browser and navigate to:
**[http://localhost:8088](http://localhost:8088)** (or `http://127.0.0.1:8088`)

### Container Service Architecture
| Container Name | Service | Internal Port | Host Port | Role |
| :--- | :--- | :--- | :--- | :--- |
| `solar_tracker_nginx` | Nginx Alpine | 80 | **8088** | Reverse proxy & static assets |
| `solar_tracker_app` | PHP 8.3 FPM | 9000 | - | Application runtime |
| `solar_tracker_postgres` | PostgreSQL 16 | 5432 | **5433** | Time-series telemetry & relational data |
| `solar_tracker_mosquitto` | Eclipse Mosquitto 2 | 1883 | **1883** | Authenticated MQTT Broker |
| `solar_tracker_mqtt_listener` | Worker | - | - | Persistent `php artisan mqtt:listen` daemon |
| `solar_tracker_scheduler` | Worker | - | - | Background task scheduler (`schedule:work`) |
| `solar_tracker_queue` | Worker | - | - | Asynchronous command & audit log processor |

---

## 4. Makefile Commands

For developer convenience, a `Makefile` is included:

```bash
make up         # Start all Docker containers
make down       # Stop and remove containers
make migrate    # Run fresh database migrations with seed data
make test       # Run complete test suite (43 tests, 271 assertions)
make sim        # Launch realistic offline simulator for ST-DEMO-001
make logs       # Tail logs for all containers
```

---

## 5. Offline Hardware Simulator

To test the application without physical hardware, launch the built-in simulator:

```bash
# Connects to Mosquitto as ST-DEMO-001 and publishes realistic sun curves every 5s
docker compose exec -d app php artisan tracker:simulate ST-DEMO-001 --interval=5
```

The simulator features:
- Realistic astronomical solar trajectory (Elevation & Azimuth movement across the day).
- Realistic PZEM-017 DC generation following sun elevation.
- Stochastic wind gusts with automatic protective stow triggers when gusts exceed 15.0 m/s.
- Automatic processing and acknowledgement of incoming commands (`move_to`, `set_mode`, `stow`, `stop`).
- MQTT Last Will and Testament (LWT) retained status.

---

## 6. How to Provision a New Tracker Node

Each hardware tracker node connects with dedicated credentials and scoped Mosquitto ACL permissions.

### Option A: Via Web UI
1. Log in as an **Administrator** or **Operator**.
2. Navigate to **Device Management** (`/device-management`).
3. Click **Register Tracker** and enter the Device UID (e.g. `ST-PILOT-002`), friendly name, site, and physical travel bounds.
4. Run the provisioning script below to generate the hardware password.

### Option B: Via Provisioning Script
Run the automated device creation script:
```bash
./scripts/mqtt-add-device.sh ST-PILOT-002
```

This script:
1. Adds the device user to Mosquitto's password file using `mosquitto_passwd`.
2. Appends the scoped publish/subscribe ACL permissions to `docker/mosquitto/acl`.
3. Sends a `SIGHUP` reload signal to Mosquitto.
4. Prints the generated one-time credentials:

```text
============================================================
 Device MQTT Credentials Generated Successfully
============================================================
 Device UID:      ST-PILOT-002
 MQTT Username:   device_ST-PILOT-002
 MQTT Password:   a8F#9xK2!mQ4vLp1
 Telemetry Topic: tracker/ST-PILOT-002/telemetry
 Status Topic:    tracker/ST-PILOT-002/status
 Command Topic:   tracker/ST-PILOT-002/cmd
------------------------------------------------------------
 IMPORTANT: Save this password now. It will NOT be shown again.
============================================================
```

---

## 7. Connecting a Real ESP32 on the Local Area Network (LAN)

### A. Network & Port Configuration
1. Determine the host machine's LAN IP address (e.g. `192.168.1.100` via `ipconfig` or `ip a`).
2. Ensure host firewall permits inbound TCP traffic on port `1883` (or `8883` if TLS is enabled).
3. The ESP32 and the host Docker machine must reside on the same Wi-Fi subnet or local Ethernet switch.

### B. ESP32 Arduino / ESP-IDF Configuration
Configure your ESP32 firmware with the network parameters:

```cpp
// Wi-Fi Configuration
const char* WIFI_SSID     = "Your_Industrial_WiFi";
const char* WIFI_PASSWORD = "Your_WiFi_Password";

// MQTT Broker Settings
const char* MQTT_BROKER   = "192.168.1.100"; // IP address of host running Docker
const int   MQTT_PORT     = 1883;
const char* MQTT_CLIENT   = "ST-PILOT-002";
const char* MQTT_USER     = "device_ST-PILOT-002";
const char* MQTT_PASS     = "a8F#9xK2!mQ4vLp1";

// Topics
const char* TOPIC_TELEMETRY = "tracker/ST-PILOT-002/telemetry";
const char* TOPIC_STATUS    = "tracker/ST-PILOT-002/status";
const char* TOPIC_CMD       = "tracker/ST-PILOT-002/cmd";
const char* TOPIC_ACK       = "tracker/ST-PILOT-002/ack";
```

### C. Last Will and Testament (LWT)
Configure the MQTT connection Last Will before connecting:
```cpp
// LWT Message: If ESP32 drops connection unexpectedly, broker publishes offline state
const char* lwt_topic   = TOPIC_STATUS;
const char* lwt_message = "{\"state\":\"offline\",\"fw\":\"1.0.0\"}";
mqttClient.setWill(lwt_topic, lwt_message, true /* retained */, 1 /* QoS */);
```

Upon successful connection, publish online status retained:
```cpp
mqttClient.publish(TOPIC_STATUS, "{\"state\":\"online\",\"fw\":\"1.0.0\"}", true, 1);
mqttClient.subscribe(TOPIC_CMD, 1);
```

### D. Telemetry JSON Payload Format (Publish every 5 seconds)
Topic: `tracker/{uid}/telemetry` (QoS 0 or 1)
```json
{
  "ts": 1760600000,
  "fw": "1.0.0",
  "rssi": -61,
  "uptime_s": 12345,
  "mode": "auto",
  "pwr": {
    "v": 212.4,
    "i": 5.31,
    "p": 1127.8,
    "e_wh": 48210
  },
  "wind": {
    "ms": 6.4
  },
  "imu": {
    "elev": 34.8,
    "az": 202.1,
    "roll": 0.4,
    "cal": {
      "sys": 3,
      "gyro": 3,
      "accel": 3,
      "mag": 2
    }
  },
  "target": {
    "elev": 35.0,
    "az": 201.5
  },
  "act": {
    "elev_state": "idle",
    "az_state": "idle",
    "elev_i": 0.8,
    "az_i": 0.0
  },
  "gps": {
    "lat": -6.2944,
    "lon": 107.1539,
    "alt": 50,
    "sats": 9,
    "fix": true,
    "hdop": 1.1
  },
  "rtc": {
    "temp_c": 31.2,
    "drift_s": 0.4
  },
  "flags": {
    "stow_active": false,
    "limit_elev_min": false,
    "limit_elev_max": false,
    "limit_az_min": false,
    "limit_az_max": false
  }
}
```

### E. Inbound Command & ACK Protocol
When an operator issues a command from the web UI, the server publishes to `tracker/{uid}/cmd`:
```json
{
  "id": "a0000000-0000-0000-0000-000000000001",
  "type": "move_to",
  "params": {
    "elev": 42.0,
    "az": 195.0
  },
  "issued_at": 1760600100,
  "expires_at": 1760600130
}
```

The ESP32 processes the command and returns an ACK to `tracker/{uid}/ack`:
```json
{
  "id": "a0000000-0000-0000-0000-000000000001",
  "status": "accepted",
  "reason": "Slewing actuators toward target angles"
}
```
Upon completing actuator travel, publish completion status:
```json
{
  "id": "a0000000-0000-0000-0000-000000000001",
  "status": "done",
  "reason": "Target angles reached within 0.5 deg tolerance"
}
```

---

## 8. Safety & Edge Interlocks Philosophy

Primary safety resides in the **ESP32 firmware** as defined in [`docs/firmware-notes.md`](docs/firmware-notes.md):
1. **Autonomous Wind Stow:** High wind ($\ge 15.0\text{ m/s}$) immediately preempts all active movement and drives to flat stow (`10.0°` elevation). Hysteresis requires $< 12.0\text{ m/s}$ for 10 minutes before releasing stow lock.
2. **Hardware Limit Switches:** Elevation and azimuth limits wired directly to hardware interrupt pins cut BTS7960 PWM within microseconds.
3. **Actuator Current & Stall Watchdog:** Motor current exceeding $15.0\text{A}$ or failing to advance position by $0.5°$ within 5 seconds aborts drive.
4. **Offline Astronomical Tracking:** When Wi-Fi/MQTT disconnects, the ESP32 calculates solar coordinates using local GPS coordinates and RTC time, buffering up to 500 telemetry records until reconnection.
5. **Server-Side Validation:** The web application rejects commands outside configured limits, rejects movement when wind speed is high, rejects stale telemetry older than 15s, and logs every action to `audit_logs`.

---

## 9. Automated Test Verification

The application is fully covered by an automated test suite:

```bash
php artisan test --compact
```

```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\AlertEngineTest
   PASS  Tests\Feature\AuthTest
   PASS  Tests\Feature\CommandControlTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\MqttListenerTest
   PASS  Tests\Feature\PhaseFiveEnergyAnalyticsTest
   PASS  Tests\Feature\RoleAccessTest
   PASS  Tests\Feature\RolePolicyPhaseSixTest
   PASS  Tests\Feature\TelemetryIngestionTest

  Tests:    43 passed (271 assertions)
  Duration: 52.06s
```

All PHP code conforms strictly to project standards formatted by Laravel Pint (`vendor/bin/pint`).

---

## 10. License

This project is licensed under the [MIT License](LICENSE).
