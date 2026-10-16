// Receive Ship a Postcard webhooks and verify their signature. Node.js 18+, no dependencies.
// Usage: SHIPAPOSTCARD_WEBHOOK_SECRET=... node verify-webhook.mjs   (listens on :3000)
import { createHmac, timingSafeEqual } from 'node:crypto';
import { createServer } from 'node:http';

const SECRET = process.env.SHIPAPOSTCARD_WEBHOOK_SECRET;
const TOLERANCE_SECONDS = 300; // reject replays of old requests

/**
 * @param {string} rawBody the request body exactly as received (don't parse and re-serialize it)
 * @param {string} header  the ShipAPostcard-Signature header: "t=1760600000,v1=<hex>"
 */
export function verifySignature(rawBody, header, secret, now = Math.floor(Date.now() / 1000)) {
    const parts = Object.fromEntries((header ?? '').split(',').map((part) => part.split('=', 2)));
    const timestamp = Number(parts.t);
    if (!timestamp || !parts.v1 || Math.abs(now - timestamp) > TOLERANCE_SECONDS) return false;

    const expected = createHmac('sha256', secret).update(`${timestamp}.${rawBody}`).digest('hex');
    const received = Buffer.from(parts.v1, 'hex');

    return received.length === expected.length / 2 && timingSafeEqual(received, Buffer.from(expected, 'hex'));
}

const seen = new Set(); // use your database in production: ShipAPostcard-Event-Id repeats on retries

function startServer() {
    createServer((request, response) => {
        let rawBody = '';
        request.on('data', (chunk) => (rawBody += chunk));
        request.on('end', () => {
            if (!verifySignature(rawBody, request.headers['shipapostcard-signature'], SECRET)) {
                response.writeHead(400).end('Invalid signature');
                return;
            }

            const eventId = request.headers['shipapostcard-event-id'];
            if (!seen.has(eventId)) {
                seen.add(eventId);
                const event = JSON.parse(rawBody);
                console.log(event.type, event.data); // e.g. postcard.delivered { id: 'sap_order_1234', ... }
            }

            response.writeHead(200).end('ok'); // answer within 10 seconds, do slow work afterwards
        });
    }).listen(3000, () => console.log('Listening on http://localhost:3000'));
}

// Start the demo server only when run directly (so the function can be imported and tested)
if (import.meta.url === `file://${process.argv[1]}`) startServer();
