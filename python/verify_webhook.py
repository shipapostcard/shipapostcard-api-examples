"""Receive Ship a Postcard webhooks and verify their signature. Python 3.9+, standard library only.

Usage: SHIPAPOSTCARD_WEBHOOK_SECRET=... python3 verify_webhook.py   (listens on :3000)
"""
import hashlib
import hmac
import json
import os
import time
from http.server import BaseHTTPRequestHandler, HTTPServer

TOLERANCE_SECONDS = 300  # reject replays of old requests


def verify_signature(raw_body: bytes, header: str, secret: str, now: int = None) -> bool:
    """raw_body is the request body exactly as received; header is "t=1760600000,v1=<hex>"."""
    parts = dict(part.split("=", 1) for part in (header or "").split(",") if "=" in part)
    try:
        timestamp = int(parts.get("t", ""))
    except ValueError:
        return False
    if not parts.get("v1") or abs((now or int(time.time())) - timestamp) > TOLERANCE_SECONDS:
        return False

    signed = f"{timestamp}.".encode() + raw_body
    expected = hmac.new(secret.encode(), signed, hashlib.sha256).hexdigest()
    return hmac.compare_digest(expected, parts["v1"])  # constant-time comparison


seen = set()  # use your database in production: ShipAPostcard-Event-Id repeats on retries


class WebhookHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        raw_body = self.rfile.read(int(self.headers.get("Content-Length", 0)))

        if not verify_signature(raw_body, self.headers.get("ShipAPostcard-Signature"), os.environ["SHIPAPOSTCARD_WEBHOOK_SECRET"]):
            self.send_response(400)
            self.end_headers()
            return

        event_id = self.headers.get("ShipAPostcard-Event-Id")
        if event_id not in seen:
            seen.add(event_id)
            event = json.loads(raw_body)
            print(event["type"], event["data"])

        self.send_response(200)  # answer within 10 seconds, do slow work afterwards
        self.end_headers()
        self.wfile.write(b"ok")


if __name__ == "__main__":
    print("Listening on http://localhost:3000")
    HTTPServer(("", 3000), WebhookHandler).serve_forever()
