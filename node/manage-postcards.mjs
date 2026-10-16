// List your postcards, get one, and cancel it. Node.js 18+.
// Usage: SHIPAPOSTCARD_API_KEY=... node manage-postcards.mjs sap_order_1234
const API = process.env.SHIPAPOSTCARD_API_URL ?? 'https://shipapostcard.com/api/v1';
const headers = { Authorization: `Bearer ${process.env.SHIPAPOSTCARD_API_KEY}`, Accept: 'application/json' };
const id = process.argv[2];

if (!id) {
    console.error('Pass a postcard id, e.g. sap_order_1234');
    process.exit(1);
}

const page = await (await fetch(`${API}/postcards?page=1`, { headers })).json();
console.log(`${page.total} postcards; first page:`);
for (const postcard of page.data) {
    console.log(`  ${postcard.id}  ${postcard.status}  ${postcard.recipient_details?.name}`);
}

const postcard = await (await fetch(`${API}/postcards/${id}`, { headers })).json();
console.log(`\n${id} is ${postcard.status}, expected delivery: ${postcard.tracking.expected_delivery_date ?? 'not printed yet'}`);
for (const event of postcard.tracking.events) console.log(`  ${event.timestamp}  ${event.event}`);

const cancel = await fetch(`${API}/postcards/${id}`, { method: 'DELETE', headers });
if (cancel.status === 204) console.log('Cancelled.');
else if (cancel.status === 403) console.log('Too late to cancel: it is already being billed, printed or mailed.');
else console.log(`HTTP ${cancel.status}`, await cancel.text());
