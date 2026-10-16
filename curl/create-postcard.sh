#!/usr/bin/env bash
# Send a postcard to a US address.
# Usage: SHIPAPOSTCARD_API_KEY=... ./create-postcard.sh
set -euo pipefail

API="${SHIPAPOSTCARD_API_URL:-https://shipapostcard.com/api/v1}"

curl --fail-with-body -sS -X POST "$API/postcards" \
  -H "Authorization: Bearer $SHIPAPOSTCARD_API_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Thanks for your order! Enjoy 20% off your next one with THANKS20.",
    "recipient_details": {
      "name": "Jane Doe",
      "address_line1": "185 Berry St",
      "city": "San Francisco",
      "state": "CA",
      "zip": "94107",
      "country": "US"
    },
    "design_details": {
      "image_url": "https://example.com/postcard-front.jpg",
      "size": "4x6",
      "orientation": "landscape"
    }
  }'
echo
