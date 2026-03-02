"""
Convert project_report.md.resolved to a professionally styled PDF using reportlab.
Fixed: inline_format now uses only valid ReportLab paragraph XML tags.
"""
import re
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.colors import HexColor, white
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    HRFlowable, Preformatted
)
from reportlab.pdfgen import canvas

# ─── Color palette ────────────────────────────────────────────────────────────
INDIGO    = HexColor("#6366f1")
DARK_NAVY = HexColor("#0f172a")
SLATE_800 = HexColor("#1e293b")
SLATE_600 = HexColor("#475569")
SLATE_200 = HexColor("#e2e8f0")
SLATE_100 = HexColor("#f1f5f9")
SLATE_50  = HexColor("#f8fafc")
BLUE_600  = HexColor("#2563eb")
BLUE_50   = HexColor("#eff6ff")
CODE_BG   = HexColor("#0f172a")
CODE_FG   = HexColor("#e2e8f0")

PAGE_W, PAGE_H = A4
MARGIN = 2.2 * cm

input_file  = r"d:\Work Projects\mychitti-main\project_report.md.resolved"
output_file = r"d:\Work Projects\mychitti-main\project_report.pdf"

# ─── Styles ────────────────────────────────────────────────────────────────────
def S(name, **kw):
    return ParagraphStyle(name, **kw)

STYLES = {
    "h1": S("H1", fontSize=24, fontName="Helvetica-Bold",
            textColor=DARK_NAVY, spaceAfter=4, spaceBefore=0, leading=30),
    "h2": S("H2", fontSize=13, fontName="Helvetica-Bold",
            textColor=SLATE_800, spaceAfter=0, spaceBefore=0, leading=18),
    "h3": S("H3", fontSize=11, fontName="Helvetica-Bold",
            textColor=SLATE_600, spaceAfter=4, spaceBefore=12, leading=15),
    "h4": S("H4", fontSize=10, fontName="Helvetica-Bold",
            textColor=SLATE_600, spaceAfter=3, spaceBefore=8, leading=13),
    "normal": S("Normal2", fontSize=10, fontName="Helvetica",
                textColor=SLATE_800, spaceAfter=5, leading=14),
    "blockquote": S("BQ", fontSize=9.5, fontName="Helvetica-Oblique",
                    textColor=HexColor("#1e40af"), leading=14,
                    leftIndent=14, rightIndent=14),
    "bullet": S("Bullet2", fontSize=10, fontName="Helvetica",
                textColor=SLATE_800, spaceAfter=3, leading=14,
                leftIndent=16, firstLineIndent=0),
    "code": S("Code2", fontSize=7.5, fontName="Courier",
              textColor=CODE_FG, backColor=CODE_BG,
              leftIndent=10, rightIndent=10, spaceAfter=8, spaceBefore=6,
              leading=11),
    "th": S("TH", fontSize=9.5, fontName="Helvetica-Bold",
            textColor=white, leading=13),
    "td": S("TD", fontSize=9.5, fontName="Helvetica",
            textColor=SLATE_800, leading=13),
}


# ─── Canvas with header/footer ─────────────────────────────────────────────────
class ReportCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self._draw_page(num_pages)
            super().showPage()
        super().save()

    def _draw_page(self, page_count):
        page_num = self._pageNumber
        # Footer line
        self.setStrokeColor(SLATE_200)
        self.setLineWidth(0.5)
        self.line(MARGIN, 1.4 * cm, PAGE_W - MARGIN, 1.4 * cm)
        # Footer text
        self.setFont("Helvetica", 8)
        self.setFillColor(SLATE_600)
        self.drawCentredString(
            PAGE_W / 2, 0.9 * cm,
            f"mychitti-main \u2014 Full Project Report  |  Page {page_num} of {page_count}"
        )
        # Top accent bar (all pages except the first)
        if page_num > 1:
            self.setFillColor(INDIGO)
            self.rect(MARGIN, PAGE_H - 1.5 * cm, PAGE_W - 2 * MARGIN, 3, fill=1, stroke=0)


# ─── Helpers ────────────────────────────────────────────────────────────────────
def xml_escape(text):
    """Escape characters that break ReportLab's XML parser."""
    return (text
            .replace("&", "&amp;")
            .replace("<", "&lt;")
            .replace(">", "&gt;")
            .replace('"', "&quot;"))


def inline_format(text):
    """
    Apply inline markdown formatting using VALID ReportLab paragraph XML.
    Order matters: escape XML first, then add markup tags (which contain real < >).
    """
    text = xml_escape(text)

    # Bold: **text** or __text__
    text = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', text)
    text = re.sub(r'__(.+?)__',     r'<b>\1</b>', text)

    # Italic: *text*
    text = re.sub(r'\*(.+?)\*',     r'<i>\1</i>', text)

    # Inline code: `code` — use <font> with only supported attributes (name, color, size)
    # backColor is NOT supported inside <font> in ReportLab paragraphs, so omit it
    text = re.sub(
        r'`([^`]+)`',
        lambda m: f'<font name="Courier" color="#6366f1" size="8.5">{xml_escape(m.group(1))}</font>',
        text
    )

    # Strip emoji (they cause rendering issues in standard fonts)
    text = re.sub(r'[^\x00-\x7F📊🔧⚙️💡🗂️]+', '', text)
    text = re.sub(r'[📊🔧⚙️💡🗂️]', '', text)

    return text.strip()


# ─── Table parser ─────────────────────────────────────────────────────────────
def parse_table(lines):
    rows = []
    for line in lines:
        line = line.strip()
        if re.match(r'^[\|\-\s:]+$', line):
            continue  # separator row
        cells = [c.strip() for c in line.strip('|').split('|')]
        rows.append(cells)
    return rows


def build_table(rows):
    if not rows:
        return None
    max_cols = max(len(r) for r in rows)
    col_w = (PAGE_W - 2 * MARGIN) / max_cols

    table_data = []
    for i, row in enumerate(rows):
        row = row + [''] * (max_cols - len(row))
        style = STYLES["th"] if i == 0 else STYLES["td"]
        table_data.append([
            Paragraph(inline_format(cell), style) for cell in row
        ])

    t = Table(table_data, colWidths=[col_w] * max_cols, repeatRows=1)
    t.setStyle(TableStyle([
        ('BACKGROUND',   (0, 0), (-1,  0), INDIGO),
        ('TEXTCOLOR',    (0, 0), (-1,  0), white),
        ('ROWBACKGROUNDS',(0, 1),(-1, -1), [white, SLATE_50]),
        ('GRID',         (0, 0), (-1, -1), 0.25, SLATE_200),
        ('LINEBELOW',    (0, -1),(-1, -1), 1.5,  INDIGO),
        ('VALIGN',       (0, 0), (-1, -1), 'TOP'),
        ('TOPPADDING',   (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING',(0, 0), (-1, -1), 6),
        ('LEFTPADDING',  (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
    ]))
    return t


# ─── Markdown → Flowables ─────────────────────────────────────────────────────
def parse_markdown(md_text):
    flowables = []
    lines = md_text.splitlines()
    i = 0

    while i < len(lines):
        raw = lines[i]
        line = raw.strip()

        # Blank line
        if not line:
            i += 1
            continue

        # Horizontal rule
        if re.match(r'^---+\s*$', line):
            flowables.append(Spacer(1, 6))
            flowables.append(HRFlowable(width="100%", thickness=1.5,
                                         color=SLATE_200, spaceAfter=6))
            i += 1
            continue

        # Fenced code block
        if line.startswith("```"):
            code_lines = []
            i += 1
            while i < len(lines) and not lines[i].strip().startswith("```"):
                code_lines.append(lines[i])
                i += 1
            i += 1  # skip closing ```
            # Use Preformatted for exact whitespace/monospace rendering
            block = Preformatted("\n".join(code_lines), STYLES["code"])
            flowables.append(block)
            continue

        # Table
        if line.startswith("|"):
            table_lines = []
            while i < len(lines) and lines[i].strip().startswith("|"):
                table_lines.append(lines[i])
                i += 1
            rows = parse_table(table_lines)
            if rows:
                tbl = build_table(rows)
                if tbl:
                    flowables.append(Spacer(1, 4))
                    flowables.append(tbl)
                    flowables.append(Spacer(1, 8))
            continue

        # Headings
        m = re.match(r'^(#{1,4})\s+(.*)', line)
        if m:
            level = len(m.group(1))
            text  = inline_format(m.group(2))

            if level == 1:
                flowables.append(Spacer(1, 4))
                flowables.append(Paragraph(text, STYLES["h1"]))
                flowables.append(HRFlowable(width="100%", thickness=3,
                                             color=INDIGO, spaceAfter=10))
            elif level == 2:
                flowables.append(Spacer(1, 10))
                bg = Table([[Paragraph(text, STYLES["h2"])]],
                           colWidths=[PAGE_W - 2 * MARGIN])
                bg.setStyle(TableStyle([
                    ('BACKGROUND',   (0, 0), (-1, -1), SLATE_100),
                    ('LINEBEFORE',   (0, 0), (0,  -1), 4, INDIGO),
                    ('TOPPADDING',   (0, 0), (-1, -1), 9),
                    ('BOTTOMPADDING',(0, 0), (-1, -1), 9),
                    ('LEFTPADDING',  (0, 0), (-1, -1), 14),
                    ('RIGHTPADDING', (0, 0), (-1, -1), 14),
                ]))
                flowables.append(bg)
                flowables.append(Spacer(1, 6))
            elif level == 3:
                flowables.append(Paragraph(text, STYLES["h3"]))
                flowables.append(HRFlowable(width="100%", thickness=0.5,
                                             color=SLATE_200, spaceAfter=4))
            else:
                flowables.append(Paragraph(text, STYLES["h4"]))
            i += 1
            continue

        # Blockquote
        if line.startswith(">"):
            bq_text = re.sub(r'^>\s?', '', line)
            bq_text = inline_format(bq_text)
            bg = Table([[Paragraph(bq_text, STYLES["blockquote"])]],
                       colWidths=[PAGE_W - 2 * MARGIN])
            bg.setStyle(TableStyle([
                ('BACKGROUND',   (0, 0), (-1, -1), BLUE_50),
                ('LINEBEFORE',   (0, 0), (0,  -1), 4, BLUE_600),
                ('TOPPADDING',   (0, 0), (-1, -1), 8),
                ('BOTTOMPADDING',(0, 0), (-1, -1), 8),
                ('LEFTPADDING',  (0, 0), (-1, -1), 14),
                ('RIGHTPADDING', (0, 0), (-1, -1), 14),
            ]))
            flowables.append(bg)
            flowables.append(Spacer(1, 6))
            i += 1
            continue

        # Unordered list
        m = re.match(r'^(\s*)[-*+]\s+(.*)', raw)
        if m:
            indent = len(m.group(1))
            text   = inline_format(m.group(2))
            sty = ParagraphStyle(f"Bullet{indent}",
                                  fontName="Helvetica", fontSize=10,
                                  textColor=SLATE_800, spaceAfter=3,
                                  leading=14,
                                  leftIndent=16 + indent * 10,
                                  firstLineIndent=0)
            flowables.append(Paragraph(f"\u2022\u2002{text}", sty))
            i += 1
            continue

        # Ordered list
        m = re.match(r'^\d+\.\s+(.*)', line)
        if m:
            text = inline_format(m.group(1))
            flowables.append(Paragraph(f"\u2022\u2002{text}", STYLES["bullet"]))
            i += 1
            continue

        # Normal paragraph
        text = inline_format(line)
        if text:
            flowables.append(Paragraph(text, STYLES["normal"]))
        i += 1

    return flowables


# ─── Build PDF ─────────────────────────────────────────────────────────────────
print("Reading markdown...")
with open(input_file, "r", encoding="utf-8") as f:
    md_text = f.read()

print("Parsing content...")
flowables = parse_markdown(md_text)

print("Building PDF...")
doc = SimpleDocTemplate(
    output_file,
    pagesize=A4,
    leftMargin=MARGIN, rightMargin=MARGIN,
    topMargin=2.0 * cm, bottomMargin=2.2 * cm,
    title="mychitti-main — Full Project Report",
    author="mychitti",
    subject="Project Report",
)

doc.build(flowables, canvasmaker=ReportCanvas)
print(f"\n✅  PDF saved: {output_file}")
