// Send a postcard to a US address. Node.js 18+ (built-in fetch), no dependencies.
// Usage: SHIPAPOSTCARD_API_KEY=... node create-postcard.mjs
const API = process.env.SHIPAPOSTCARD_API_URL ?? 'https://shipapostcard.com/api/v1';

const response = await fetch(`${API}/postcards`, {
    method: 'POST',
    headers: {
        Authorization: `Bearer ${process.env.SHIPAPOSTCARD_API_KEY}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    body: JSON.stringify({
        message: 'Thanks for your order! Enjoy 20% off your next one with THANKS20.',
        recipient_details: {
            name: 'Jane Doe',
            address_line1: '185 Berry St',
            city: 'San Francisco',
            state: 'CA',
            zip: '94107',
            country: 'US',
        },
        design_details: {
            image_url: 'https://example.com/postcard-front.jpg',
            size: '4x6',
            orientation: 'landscape',
        },
    }),
});

const body = await response.json();

if (response.status === 422) {
    // { error: 'Validation Failed', details: { 'recipient_details.zip': ['...'] } }
    console.error('Invalid postcard:', body.details);
    process.exit(1);
}
if (!response.ok) {
    console.error(`HTTP ${response.status}`, body);
    process.exit(1);
}

console.log(`Queued ${body.id} (${body.status}) for $${body.amount}`);
