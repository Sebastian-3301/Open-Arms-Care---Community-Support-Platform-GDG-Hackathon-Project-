# Open Arms local prototype

Run the site with Node 24 or newer:

```powershell
npm start
```

Open `http://localhost:3000`. The site serves `openarms-website.html` and creates a local SQLite database at `data/open-arms.db` on first run.

The database stores individual, organisation, and orphanage registrations; donation intentions; contact messages; submission statuses; and orphanage registration documents. Files are kept locally in `data/uploads/` and can be downloaded only through the authenticated dashboard. This is a local prototype, not a production document-security system.

## Local admin dashboard

Before starting the server, set a password in the same PowerShell window:

```powershell
$env:ADMIN_PASSWORD = "choose-a-strong-local-password"
npm start
```

Then open `http://127.0.0.1:3000/admin` and sign in with username `admin` and the password you set. The server listens only on `127.0.0.1`, so this dashboard cannot be reached from another device on the network.

## What needs external API keys

No key is needed for the local site, SQLite database, file uploads, or admin dashboard.

To launch a public website, these integrations should be added with accounts and credentials that you control:

- **Payments:** Razorpay or Stripe publishable/secret keys plus a webhook signing secret. Use this before taking live donations; the current site stores a donation intention only and never accepts card or UPI details.
- **Email:** a transactional provider such as Resend, Postmark, or an SMTP account and a verified sending domain for confirmations and staff alerts.
- **Production hosting/database:** a public host/domain plus a managed database (for example PostgreSQL via Supabase, Neon, or Railway) and protected object storage for documents. Do not expose the local SQLite database or `data/uploads/` folder to the internet.
.
