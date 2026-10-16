"""List your postcards, get one, and cancel it. Python 3.9+, standard library only.

Usage: SHIPAPOSTCARD_API_KEY=... python3 manage_postcards.py sap_order_1234
"""
import json
import os
import sys
import urllib.error
import urllib.request

API = os.environ.get("SHIPAPOSTCARD_API_URL", "https://shipapostcard.com/api/v1")
HEADERS = {"Authorization": f"Bearer {os.environ['SHIPAPOSTCARD_API_KEY']}", "Accept": "application/json"}


def request(method, path):
    try:
        with urllib.request.urlopen(urllib.request.Request(f"{API}{path}", method=method, headers=HEADERS)) as response:
            raw = response.read()
            return response.status, json.loads(raw) if raw else None
    except urllib.error.HTTPError as error:
        return error.code, None


if len(sys.argv) < 2:
    sys.exit("Pass a postcard id, e.g. sap_order_1234")
postcard_id = sys.argv[1]

_, page = request("GET", "/postcards?page=1")
print(f"{page['total']} postcards; first page:")
for postcard in page["data"]:
    print(f"  {postcard['id']}  {postcard['status']}  {postcard['recipient_details']['name']}")

_, postcard = request("GET", f"/postcards/{postcard_id}")
print(f"\n{postcard_id} is {postcard['status']}, expected delivery: {postcard['tracking']['expected_delivery_date'] or 'not printed yet'}")
for event in postcard["tracking"]["events"]:
    print(f"  {event['timestamp']}  {event['event']}")

status, _ = request("DELETE", f"/postcards/{postcard_id}")
print({204: "Cancelled.", 403: "Too late to cancel: it is already being billed, printed or mailed."}.get(status, f"HTTP {status}"))
