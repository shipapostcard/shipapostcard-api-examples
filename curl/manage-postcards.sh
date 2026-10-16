#!/usr/bin/env bash
# List your postcards, get one, and cancel it while it's still waiting for the nightly batch.
# Usage: SHIPAPOSTCARD_API_KEY=... ./manage-postcards.sh sap_order_1234
set -euo pipefail

API="${SHIPAPOSTCARD_API_URL:-https://shipapostcard.com/api/v1}"
AUTH=(-H "Authorization: Bearer $SHIPAPOSTCARD_API_KEY" -H "Accept: application/json")
ID="${1:?Pass a postcard id, e.g. sap_order_1234}"

echo "# Check the key"
curl --fail-with-body -sS "${AUTH[@]}" "$API/user"; echo

echo "# First page of postcards (10 per page)"
curl --fail-with-body -sS "${AUTH[@]}" "$API/postcards?page=1"; echo

echo "# One postcard"
curl --fail-with-body -sS "${AUTH[@]}" "$API/postcards/$ID"; echo

echo "# Cancel it (204 = cancelled, 403 = already being billed or printed)"
curl -sS -o /dev/null -w "%{http_code}\n" -X DELETE "${AUTH[@]}" "$API/postcards/$ID"
