#!/bin/bash
set -e

# Usage: ./scripts/mqtt-add-device.sh <device_uid>
if [ -z "$1" ]; then
    echo "Usage: $0 <device_uid>"
    echo "Example: $0 ST-DEMO-002"
    exit 1
fi

DEVICE_UID="$1"
MQTT_USER="device_${DEVICE_UID}"

# Generate random secure 16-character alphanumeric password
DEVICE_PASSWORD=$(tr -dc 'A-Za-z0-9!@#%^&*' </dev/urandom | head -c 16 2>/dev/null || openssl rand -base64 12)

echo "Provisioning MQTT device user for UID: ${DEVICE_UID}..."

# Locate mosquitto container
CONTAINER_NAME=$(docker ps --filter "name=mosquitto" --format "{{.Names}}" | head -n 1)

if [ -z "$CONTAINER_NAME" ]; then
    echo "Error: mosquitto container is not running. Please run 'docker compose up -d' first."
    exit 1
fi

# Add user to Mosquitto password file inside container
docker exec "$CONTAINER_NAME" mosquitto_passwd -b /mosquitto/config/passwd "$MQTT_USER" "$DEVICE_PASSWORD"

# Append device ACL to docker/mosquitto/acl if not already present
ACL_FILE="$(dirname "$0")/../docker/mosquitto/acl"

if ! grep -q "user ${MQTT_USER}" "$ACL_FILE" 2>/dev/null; then
    cat <<EOF >> "$ACL_FILE"

# Device: ${DEVICE_UID}
user ${MQTT_USER}
topic write tracker/${DEVICE_UID}/telemetry
topic write tracker/${DEVICE_UID}/status
topic write tracker/${DEVICE_UID}/event
topic write tracker/${DEVICE_UID}/ack
topic read tracker/${DEVICE_UID}/cmd
EOF
fi

# Reload Mosquitto configuration by sending SIGHUP signal to PID 1
docker exec "$CONTAINER_NAME" kill -HUP 1 2>/dev/null || true

echo "============================================================"
echo " Device MQTT Credentials Generated Successfully"
echo "============================================================"
echo " Device UID:      ${DEVICE_UID}"
echo " MQTT Username:   ${MQTT_USER}"
echo " MQTT Password:   ${DEVICE_PASSWORD}"
echo " Telemetry Topic: tracker/${DEVICE_UID}/telemetry"
echo " Status Topic:    tracker/${DEVICE_UID}/status"
echo " Command Topic:   tracker/${DEVICE_UID}/cmd"
echo "------------------------------------------------------------"
echo " IMPORTANT: Save this password now. It will NOT be shown again."
echo "============================================================"
