require('dotenv').config();

const fs = require('fs');
const path = require('path');
const axios = require('axios');
const pino = require('pino');
const qrcode = require('qrcode-terminal');

const {
    default: makeWASocket,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    DisconnectReason,
    Browsers,
} = require('baileys');

const CONFIG = {
    ingestUrl: process.env.INGEST_URL,
    ingestSecret: process.env.INGEST_SECRET,
    // Kept outside the repo on purpose: the deploy does `git reset --hard`, and losing this
    // folder means re-scanning the QR from a phone that may not be in the room.
    authDir: process.env.AUTH_DIR || '/var/lib/wa-bridge/auth',
    spoolDir: process.env.SPOOL_DIR || '/var/lib/wa-bridge/spool',
    // all    = every conversation this account can see, groups and one-to-one
    // groups = groups only
    // list   = only the groups named in GROUP_NAMES / GROUP_JIDS
    capture: (process.env.CAPTURE || 'all').toLowerCase(),
    groupNames: (process.env.GROUP_NAMES || 'mychitti development')
        .split(',').map(s => s.trim().toLowerCase()).filter(Boolean),
    groupJids: (process.env.GROUP_JIDS || '')
        .split(',').map(s => s.trim()).filter(Boolean),
    // Chats never to forward, whatever the capture mode. Bare numbers are accepted.
    excludeJids: (process.env.EXCLUDE_JIDS || '')
        .split(',').map(s => s.trim()).filter(Boolean)
        .map(s => (s.includes('@') ? s : s.replace(/\D/g, '') + '@s.whatsapp.net')),
    syncHistory: process.env.SYNC_HISTORY === '1',
    batchSize: parseInt(process.env.BATCH_SIZE || '40', 10),
    flushMs: parseInt(process.env.FLUSH_MS || '3000', 10),
    logLevel: process.env.LOG_LEVEL || 'info',
};

const LIST_ONLY = process.argv.includes('--list-groups');

const logger = pino({ level: CONFIG.logLevel });
const log = (...a) => console.log(new Date().toISOString(), ...a);

for (const dir of [CONFIG.authDir, CONFIG.spoolDir]) {
    fs.mkdirSync(dir, { recursive: true });
}

// Group JIDs archived in `list` mode. Seeded from GROUP_JIDS, then filled in by name once
// the group list arrives, so a rename on the phone does not silently stop the archive.
const targetJids = new Set(CONFIG.groupJids);
const groupNameByJid = new Map();

// Who is on the other end of a one-to-one chat. WhatsApp does not put the contact's name on
// the messages we send, so it has to be remembered from the contact list and from whatever
// name arrived on their inbound messages.
const contactNameByJid = new Map();

const excluded = new Set(CONFIG.excludeJids);

function rememberContact(jid, name) {
    if (!jid || !name) return;
    const clean = String(name).trim();
    if (clean) contactNameByJid.set(jid, clean);
}

/* ------------------------------------------------------------------ *
 * Message shaping
 * ------------------------------------------------------------------ */

// WhatsApp wraps the real payload in one or more envelopes (disappearing messages,
// view-once, document-with-caption). Unwrap before reading anything out of it.
function unwrap(message) {
    if (!message) return null;
    return message.ephemeralMessage?.message
        || message.viewOnceMessage?.message
        || message.viewOnceMessageV2?.message
        || message.viewOnceMessageV2Extension?.message
        || message.documentWithCaptionMessage?.message
        || message;
}

function extractContent(message) {
    const m = unwrap(message);
    if (!m) return null;

    const ctx = m.extendedTextMessage?.contextInfo
        || m.imageMessage?.contextInfo
        || m.videoMessage?.contextInfo
        || m.documentMessage?.contextInfo
        || null;

    const quoted = ctx?.quotedMessage ? unwrap(ctx.quotedMessage) : null;
    const base = {
        quoted_message_id: ctx?.stanzaId || null,
        quoted_body: quoted
            ? (quoted.conversation || quoted.extendedTextMessage?.text
                || quoted.imageMessage?.caption || quoted.videoMessage?.caption || null)
            : null,
        media_mime: null,
        media_name: null,
    };

    if (m.conversation) {
        return { ...base, type: 'text', body: m.conversation };
    }
    if (m.extendedTextMessage?.text) {
        return { ...base, type: 'text', body: m.extendedTextMessage.text };
    }
    if (m.imageMessage) {
        return { ...base, type: 'image', body: m.imageMessage.caption || '[image]', media_mime: m.imageMessage.mimetype || null };
    }
    if (m.videoMessage) {
        return { ...base, type: 'video', body: m.videoMessage.caption || '[video]', media_mime: m.videoMessage.mimetype || null };
    }
    if (m.documentMessage) {
        return {
            ...base,
            type: 'document',
            body: m.documentMessage.caption || m.documentMessage.fileName || '[document]',
            media_mime: m.documentMessage.mimetype || null,
            media_name: m.documentMessage.fileName || null,
        };
    }
    if (m.audioMessage) {
        // A voice note carries no text at all. Store the placeholder so the timeline keeps
        // its shape; transcription is a separate concern on the Laravel side.
        return { ...base, type: m.audioMessage.ptt ? 'voice' : 'audio', body: '[voice note]', media_mime: m.audioMessage.mimetype || null };
    }
    if (m.stickerMessage) {
        return { ...base, type: 'sticker', body: '[sticker]' };
    }
    if (m.locationMessage) {
        const loc = m.locationMessage;
        return { ...base, type: 'location', body: ('[location] ' + loc.degreesLatitude + ',' + loc.degreesLongitude + ' ' + (loc.name || '')).trim() };
    }
    if (m.contactMessage || m.contactsArrayMessage) {
        const name = m.contactMessage?.displayName || 'contacts';
        return { ...base, type: 'contact', body: '[contact] ' + name };
    }
    if (m.reactionMessage) {
        return {
            ...base,
            type: 'reaction',
            body: m.reactionMessage.text || '[reaction]',
            quoted_message_id: m.reactionMessage.key?.id || base.quoted_message_id,
        };
    }
    if (m.pollCreationMessage || m.pollCreationMessageV3) {
        const poll = m.pollCreationMessage || m.pollCreationMessageV3;
        const opts = (poll.options || []).map(o => o.optionName).join(' | ');
        return { ...base, type: 'poll', body: ('[poll] ' + (poll.name || '') + ' :: ' + opts).trim() };
    }
    if (m.listResponseMessage?.title) {
        return { ...base, type: 'text', body: m.listResponseMessage.title };
    }
    if (m.buttonsResponseMessage?.selectedDisplayText) {
        return { ...base, type: 'text', body: m.buttonsResponseMessage.selectedDisplayText };
    }
    // protocolMessage = edits/revokes/key distribution. Nothing a human wrote.
    if (m.protocolMessage || m.senderKeyDistributionMessage || m.messageContextInfo) {
        return null;
    }

    const key = Object.keys(m)[0];
    return key ? { ...base, type: key.replace(/Message$/, '').toLowerCase(), body: '[' + key + ']' } : null;
}

// Decide whether a chat is archived at all, and what kind it is.
// Status posts, channels and broadcast lists are never conversations - drop them outright.
function classify(jid) {
    if (!jid) return null;
    if (jid === 'status@broadcast' || jid.endsWith('@broadcast') || jid.endsWith('@newsletter')) return null;
    if (excluded.has(jid)) return null;

    const isGroup = jid.endsWith('@g.us');

    if (CONFIG.capture === 'list') {
        return isGroup && targetJids.has(jid) ? 'group' : null;
    }
    if (CONFIG.capture === 'groups') {
        return isGroup ? 'group' : null;
    }
    return isGroup ? 'group' : 'dm';
}

function toRow(msg) {
    const jid = msg.key?.remoteJid;
    const chatType = classify(jid);
    if (!chatType) return null;

    const content = extractContent(msg.message);
    if (!content) return null;

    // In a group the author is `participant` and `remoteJid` is the group itself. In a
    // one-to-one chat there is no participant: the author is the other party, or us.
    const senderJid = chatType === 'group'
        ? (msg.key.participant || msg.participant || (msg.key.fromMe ? 'me' : null))
        : (msg.key.fromMe ? 'me' : jid);

    const senderPhone = senderJid && senderJid.includes('@')
        ? senderJid.split('@')[0].split(':')[0]
        : null;

    // An inbound one-to-one message carries the other party's own name, which is the best
    // name we will ever get for that chat. Our outbound ones carry ours, so ignore those.
    if (chatType === 'dm' && !msg.key.fromMe && msg.pushName) {
        rememberContact(jid, msg.pushName);
    }

    const chatName = chatType === 'group'
        ? (groupNameByJid.get(jid) || null)
        : (contactNameByJid.get(jid) || null);

    return {
        wa_message_id: msg.key.id,
        chat_jid: jid,
        chat_name: chatName,
        chat_type: chatType,
        sender_jid: senderJid,
        sender_name: msg.pushName || null,
        sender_phone: senderPhone,
        from_me: msg.key.fromMe ? 1 : 0,
        type: content.type,
        body: content.body,
        quoted_message_id: content.quoted_message_id,
        quoted_body: content.quoted_body,
        media_mime: content.media_mime,
        media_name: content.media_name,
        sent_at: msg.messageTimestamp
            ? new Date(Number(msg.messageTimestamp) * 1000).toISOString()
            : new Date().toISOString(),
    };
}

/* ------------------------------------------------------------------ *
 * Delivery to Laravel, with an on-disk spool
 * ------------------------------------------------------------------ */

let pending = [];
let flushTimer = null;

function spool(rows) {
    const file = path.join(CONFIG.spoolDir, Date.now() + '-' + Math.random().toString(36).slice(2, 8) + '.json');
    fs.writeFileSync(file, JSON.stringify(rows));
    log('spooled ' + rows.length + ' message(s) to ' + path.basename(file));
}

async function post(rows) {
    const res = await axios.post(
        CONFIG.ingestUrl,
        { messages: rows },
        {
            headers: {
                'X-Bridge-Secret': CONFIG.ingestSecret,
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            timeout: 20000,
        }
    );
    return res.data;
}

async function flush() {
    if (flushTimer) { clearTimeout(flushTimer); flushTimer = null; }
    if (!pending.length) return;

    const rows = pending.splice(0, CONFIG.batchSize);
    try {
        const data = await post(rows);
        log('ingested ' + rows.length + ' -> stored ' + (data?.stored ?? '?') + ' duplicate ' + (data?.duplicate ?? '?'));
    } catch (e) {
        // Never drop a message because Laravel was down or mid-deploy.
        log('ingest failed:', e.response?.status || '', e.message);
        spool(rows);
    }
    if (pending.length) scheduleFlush(50);
}

function scheduleFlush(ms) {
    if (flushTimer) return;
    flushTimer = setTimeout(() => { flushTimer = null; flush(); }, ms || CONFIG.flushMs);
}

function enqueue(row) {
    pending.push(row);
    if (pending.length >= CONFIG.batchSize) flush();
    else scheduleFlush();
}

async function drainSpool() {
    let files;
    try {
        files = fs.readdirSync(CONFIG.spoolDir).filter(f => f.endsWith('.json')).sort();
    } catch (e) {
        return;
    }
    for (const f of files) {
        const full = path.join(CONFIG.spoolDir, f);
        let rows;
        try {
            rows = JSON.parse(fs.readFileSync(full, 'utf8'));
        } catch (e) {
            fs.renameSync(full, full + '.corrupt');
            continue;
        }
        try {
            await post(rows);
            fs.unlinkSync(full);
            log('replayed spool ' + f + ' (' + rows.length + ')');
        } catch (e) {
            log('spool replay still failing (' + f + '):', e.message);
            return; // keep chronological order, try again next tick
        }
    }
}

setInterval(drainSpool, 30000);

/* ------------------------------------------------------------------ *
 * Socket
 * ------------------------------------------------------------------ */

async function resolveGroups(sock) {
    let groups;
    try {
        groups = await sock.groupFetchAllParticipating();
    } catch (e) {
        log('could not fetch group list:', e.message);
        return;
    }

    const rows = Object.values(groups).map(g => ({ jid: g.id, subject: g.subject || '' }));
    for (const g of rows) groupNameByJid.set(g.jid, g.subject);

    if (LIST_ONLY) {
        log('groups this account is in (' + rows.length + '):');
        for (const g of rows) console.log('  ' + g.jid + '   ' + g.subject);
        process.exit(0);
    }

    for (const g of rows) {
        if (CONFIG.groupNames.includes(g.subject.trim().toLowerCase())) {
            targetJids.add(g.jid);
        }
    }

    if (CONFIG.capture === 'list') {
        if (!targetJids.size) {
            log('WARNING: no group matched', JSON.stringify(CONFIG.groupNames),
                '- run `npm run groups` and set GROUP_JIDS explicitly.');
        } else {
            log('archiving groups:', [...targetJids].map(j => j + ' (' + (groupNameByJid.get(j) || '?') + ')').join(', '));
        }
        return;
    }

    log('capture mode:', CONFIG.capture,
        '- ' + rows.length + ' group(s) visible' + (CONFIG.capture === 'all' ? ' plus all one-to-one chats' : ''),
        excluded.size ? '(' + excluded.size + ' chat(s) excluded)' : '');
}

async function start() {
    if (!LIST_ONLY && (!CONFIG.ingestUrl || !CONFIG.ingestSecret)) {
        console.error('INGEST_URL and INGEST_SECRET are required. Copy .env.example to .env.');
        process.exit(1);
    }

    const { state, saveCreds } = await useMultiFileAuthState(CONFIG.authDir);
    const { version } = await fetchLatestBaileysVersion();

    const sock = makeWASocket({
        version,
        logger,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        browser: Browsers.ubuntu('MyChitti Archive'),
        // This is an archiver, not a participant: never mark the group read and never
        // broadcast presence, so the account looks idle to everyone in the room.
        markOnlineOnConnect: false,
        syncFullHistory: CONFIG.syncHistory,
        generateHighQualityLinkPreview: false,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            log('scan this QR with the WhatsApp account that is in the group:');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'open') {
            log('connected as', sock.user?.id, sock.user?.name || '');
            await resolveGroups(sock);
            drainSpool();
        }

        if (connection === 'close') {
            const status = lastDisconnect?.error?.output?.statusCode;
            const loggedOut = status === DisconnectReason.loggedOut;
            log('connection closed', status || '', loggedOut ? '(logged out)' : '- reconnecting');

            if (loggedOut) {
                // The session was revoked from the phone. Reconnecting in a loop would just
                // burn the number; the operator has to clear AUTH_DIR and rescan.
                log('session revoked. delete ' + CONFIG.authDir + ' and restart to pair again.');
                process.exit(1);
            }
            setTimeout(() => start().catch(e => { log('restart failed:', e.message); process.exit(1); }), 3000);
        }
    });

    sock.ev.on('groups.update', (updates) => {
        for (const u of updates) {
            if (u.id && u.subject) {
                groupNameByJid.set(u.id, u.subject);
                if (CONFIG.groupNames.includes(u.subject.trim().toLowerCase())) targetJids.add(u.id);
            }
        }
    });

    // The address book. Without it every one-to-one chat is named by a bare phone number.
    const takeContacts = (contacts) => {
        for (const c of contacts || []) {
            rememberContact(c.id, c.name || c.notify || c.verifiedName);
        }
    };
    sock.ev.on('contacts.upsert', takeContacts);
    sock.ev.on('contacts.update', takeContacts);

    sock.ev.on('messages.upsert', ({ messages, type }) => {
        if (type !== 'notify' && type !== 'append') return;
        for (const msg of messages) {
            try {
                const row = toRow(msg);
                if (row) enqueue(row);
            } catch (e) {
                log('skip message:', e.message);
            }
        }
    });

    // Backfill on first pair: WhatsApp pushes whatever history the phone will share.
    sock.ev.on('messaging-history.set', ({ messages, contacts, chats }) => {
        // Names arrive alongside the history and are worth keeping even when the backlog
        // itself is not being archived - they name every chat from here on.
        takeContacts(contacts);
        for (const c of chats || []) {
            if (c.id && c.name) {
                if (c.id.endsWith('@g.us')) groupNameByJid.set(c.id, c.name);
                else rememberContact(c.id, c.name);
            }
        }

        if (!CONFIG.syncHistory || !messages || !messages.length) return;
        let n = 0;
        for (const msg of messages) {
            try {
                const row = toRow(msg);
                if (row) { enqueue(row); n++; }
            } catch (e) { /* ignore */ }
        }
        if (n) log('history sync queued ' + n + ' message(s)');
    });
}

process.on('SIGTERM', async () => { await flush(); process.exit(0); });
process.on('SIGINT', async () => { await flush(); process.exit(0); });
process.on('unhandledRejection', (e) => log('unhandledRejection:', e?.message || e));

start().catch(e => { log('fatal:', e); process.exit(1); });
