#!/bin/sh
set -eu

CERT_PATH=/etc/ssl/private/localhost.crt
KEY_PATH=/etc/ssl/private/localhost.key

if [ "${HTTPS_SELF_SIGNED:-0}" -eq 1 ] && [ ! -e "$CERT_PATH" ]; then
    OPENSSL_CONFIG=$(mktemp)

    echo "[dn]
CN=localhost
[req]
distinguished_name = dn
[EXT]
subjectAltName=DNS:localhost
keyUsage=digitalSignature
extendedKeyUsage=serverAuth" >> "$OPENSSL_CONFIG"

    openssl req -x509 -out "$CERT_PATH" -keyout "$KEY_PATH" \
        -newkey rsa:2048 -nodes -sha256 -subj '/CN=localhost' -extensions EXT \
        -config "$OPENSSL_CONFIG"

    {
        echo 'listen 443 ssl http2;'
        echo 'ssl_certificate /etc/ssl/private/localhost.crt;'
        echo 'ssl_certificate_key /etc/ssl/private/localhost.key;'
    } >> /etc/nginx/ssl.conf
elif [ "${HTTPS_SELF_SIGNED:-0}" -eq 0 ]; then
    echo '' > /etc/nginx/ssl.conf
fi

exec "$@"
