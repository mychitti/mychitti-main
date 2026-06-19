# Retail POS — Hardware Desktop Agent (contract)

Browser pages can't talk to USB/serial peripherals directly. The Retail POS billing screen
talks to a small **local desktop agent** (Electron / Node service) over `http://localhost:9100`.
If the agent isn't running, every call fails silently and the browser 80mm thermal window is the
fallback — billing never blocks.

JS client: `POSAgent` in `views/vendor/retail-pos/index.blade.php`.

## Endpoints the agent must expose (all POST, JSON)

| Path | Body | Returns | Purpose |
|---|---|---|---|
| `/drawer/open` | `{}` | `{ok:true}` | Kick the cash drawer (RJ11 via printer). Called on cash payment. |
| `/print` | `{url, format:"80mm"}` | `{ok:true}` | Fetch the receipt URL and print ESC/POS to the thermal printer. |
| `/scale/read` | `{}` | `{weight: <kg>}` | Read current weight from the RS-232/USB weighing scale (loose items). |

CORS: the agent must allow the panel origin (`Access-Control-Allow-Origin`).

## Notes
- **Barcode/QR scanners** need no agent — they emulate a keyboard (HID); the New Sale search box
  captures the scan + Enter directly.
- Per-terminal hardware (printer name/IP, scale port, drawer on/off) is configured under
  **Retail POS → Terminals** and stored in `pos_terminals.hardware` (JSON). A future revision can
  pass the active terminal's config to the agent.
- Build the agent as a separate repo/app (Electron `serialport` + `escpos` libraries). It is **not**
  part of this Laravel codebase.
