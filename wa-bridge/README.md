# MyChitti WhatsApp Bridge (Baileys)

Archives the WhatsApp account it is paired to — internal team groups and one-to-one chats with
vendors and customers — into the MyChitti database, so the AI can read sales, leads, payments,
tasks and follow-ups out of what was actually said.

```
WhatsApp account (all chats)
   │  WhatsApp Web multi-device protocol (Baileys)
   ▼
wa-bridge (node)  ──POST /whatsapp/bridge/ingest + X-Bridge-Secret──►  Laravel
                                                                        │
                                            wa_chats          (one row per conversation)
                                            wa_chat_messages  (raw archive)
                                                                        │  php artisan wa:analyze-chats
                                                                        ▼
                                            wa_chat_insights  (sale / lead / payment / task /
                                            task_update / issue / decision / followup / note)
                                                                        │
                                Admin ▸ Business Settings ▸ 3rd Party ▸ WhatsApp ▸
                                WhatsApp Intelligence · Archived Chats · Message Archive
```

## Before you start

Two things worth deciding deliberately.

**This is an unofficial client.** Baileys reverse-engineers WhatsApp Web; it is not a Meta
product. The Cloud API this repo already uses (`WhatsAppService`) cannot see normal groups or
personal chats, which is why this exists — but WhatsApp's terms do not permit unofficial
clients, and the paired account can be banned. Pair a number the business can afford to lose,
not the one everything depends on.

**Archiving a whole account captures other people.** Every one-to-one chat has someone on the
other end who never agreed to be recorded or read by a model, and a personal account will hold
family and private conversations alongside business ones. Three controls exist for this:

- `EXCLUDE_JIDS` — the bridge never forwards these chats at all. Set it before first run.
- **Archived Chats** screen — AI analysis toggles off per conversation. The messages stay
  archived; nothing from that chat is sent to a model.
- The delete button on that screen removes a chat and every message archived from it.

Whether that is enough is a call for whoever owns the account. `CAPTURE=groups` archives only
group conversations if the one-to-one chats are not wanted at all.

The bridge only listens. It never sends, never marks anything read, and never sets presence,
which is both the least ban-prone way to run it and invisible to the people in the chats.

## Install (recommended host: AI droplet 134.209.153.181 — Node + Supervisor already there)

```bash
# 1. Node 20+
node -v

# 2. Session + spool live OUTSIDE the repo: deploys run `git reset --hard`
mkdir -p /var/lib/wa-bridge/auth /var/lib/wa-bridge/spool

# 3. Install
cd /var/www/html/admin/wa-bridge   # or wherever this repo is checked out on that box
npm install

# 4. Configure
cp .env.example .env
openssl rand -hex 32        # paste into INGEST_SECRET below and into Laravel's .env
nano .env
```

Laravel `.env` on **every** server that serves the ingest route:

```
WA_BRIDGE_SECRET=<the same hex string>
OPENAI_API_KEY=<already set — used by the analysis step>
```

Then `php artisan config:clear` on those servers.

Confirm the URL and secret before pairing:

```bash
curl -s -H "X-Bridge-Secret: $SECRET" https://admin.mychitti.net/whatsapp/bridge/ping
# {"ok":true}
```

## First pair

```bash
cd /var/www/html/admin/wa-bridge
node index.js
```

A QR appears in the terminal. On the phone whose account you are archiving: **WhatsApp ▸
Settings ▸ Linked devices ▸ Link a device**, scan it. The session is written to `AUTH_DIR` and
survives restarts — you only scan once.

To pull in existing history, set `SYNC_HISTORY=1` for one run, then set it back to `0`.
WhatsApp decides how much it shares — expect recent messages per chat, not years of backlog.
On a busy account this is the one run that can push tens of thousands of messages; they will
all be queued for analysis, so consider switching off the chats you do not want read first.

`CAPTURE=list` restricts the archive to named groups:

```bash
npm run groups
# 120363043211234567@g.us   mychitti development
```

Put that in `.env` as `GROUP_JIDS=120363043211234567@g.us` — pinning the JID means a rename on
the phone cannot silently stop the archive.

## Run under Supervisor

`/etc/supervisor/conf.d/wa-bridge.conf`:

```ini
[program:wa-bridge]
directory=/var/www/html/admin/wa-bridge
command=/usr/bin/node index.js
autostart=true
autorestart=true
startsecs=10
stderr_logfile=/var/log/wa-bridge.err.log
stdout_logfile=/var/log/wa-bridge.out.log
user=root
environment=NODE_ENV="production"
```

```bash
supervisorctl reread && supervisorctl update && supervisorctl status wa-bridge
```

## Operating notes

- **Nothing is lost when Laravel is down.** A failed POST is spooled to `SPOOL_DIR` as JSON and
  replayed every 30s. Watch for files piling up there — that means ingest is failing.
- **Duplicates are free.** `(chat_jid, wa_message_id)` is unique, so reconnects and history
  syncs re-send the same messages harmlessly.
- **Session revoked** (someone unlinked the device on the phone): the process exits rather than
  reconnect-looping. Delete `AUTH_DIR` and pair again.
- **Analysis** runs every 15 minutes via `wa:analyze-chats` on the Laravel scheduler, one model
  call per chat per batch of 60 messages, with 15 messages of prior context so "done" is read
  against the task above it. Chats with AI switched off are skipped entirely and cost nothing.
  Run it by hand any time: `php artisan wa:analyze-chats --loops=20`.
- **Cost scales with traffic, not with the archive.** Only unanalysed messages in enabled chats
  are ever sent. Switching a chat back on makes its backlog analysable again on the next run.
- **Stale archive** shows as a warning banner on the WhatsApp Intelligence page when nothing has
  arrived in 12 hours. That is the only symptom of a dead bridge an admin ever sees.
- **Contact names** come from the phone's address book as it syncs. Until it does, one-to-one
  chats are named by phone number, and inbound messages fill in the sender's own name.
- Media is not downloaded — captions and filenames are archived, the files are not. Voice notes
  are stored as `[voice note]` placeholders; transcription would be a separate step.
- Status updates, channels and broadcast lists are never archived.

## Environment

| Variable | Default | Notes |
|---|---|---|
| `INGEST_URL` | — | required; `https://admin.mychitti.net/whatsapp/bridge/ingest` |
| `INGEST_SECRET` | — | required; must equal Laravel `WA_BRIDGE_SECRET` |
| `AUTH_DIR` | `/var/lib/wa-bridge/auth` | keep outside the repo |
| `SPOOL_DIR` | `/var/lib/wa-bridge/spool` | retry queue |
| `CAPTURE` | `all` | `all` (groups + one-to-one), `groups`, or `list` |
| `EXCLUDE_JIDS` | — | never forwarded; bare numbers accepted |
| `GROUP_NAMES` | `mychitti development` | `CAPTURE=list` only; matched on group subject |
| `GROUP_JIDS` | — | `CAPTURE=list` only; preferred once known |
| `SYNC_HISTORY` | `0` | one-off backlog pull |
| `BATCH_SIZE` | `40` | messages per POST |
| `FLUSH_MS` | `3000` | batching delay |
