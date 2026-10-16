"""Send a postcard to a US address. Python 3.9+, standard library only.

Usage: SHIPAPOSTCARD_API_KEY=... python3 create_postcard.py
"""
import json
import os
import sys
import urllib.error
import urllib.request

API = os.environ.get("SHIPAPOSTCARD_API_URL", "https://shipapostcard.com/api/v1")

postcard = {
    "message": "Thanks for your order! Enjoy 20% off your next one with THANKS20.",
    "recipient_details": {
        "name": "Jane Doe",
        "address_line1": "185 Berry St",
        "city": "San Francisco",
        "state": "CA",
        "zip": "94107",
        "country": "US",
    },
    "design_details": {
        "image_url": "https://example.com/postcard-front.jpg",
        "size": "4x6",
        "orientation": "landscape",
    },
}

request = urllib.request.Request(
    f"{API}/postcards",
    data=json.dumps(postcard).encode(),
    method="POST",
    headers={
        "Authorization": f"Bearer {os.environ['SHIPAPOSTCARD_API_KEY']}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    },
)

try:
    with urllib.request.urlopen(request) as response:
        body = json.load(response)
except urllib.error.HTTPError as error:
    body = json.load(error)
    if error.code == 422:
        # {"error": "Validation Failed", "details": {"recipient_details.zip": ["..."]}}
        sys.exit(f"Invalid postcard: {json.dumps(body['details'], indent=2)}")
    sys.exit(f"HTTP {error.code}: {body}")

print(f"Queued {body['id']} ({body['status']}) for ${body['amount']}")
