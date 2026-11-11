export async function compose(title, body, background) {
  const { PDFDocument, StandardFonts, rgb } = window.PDFLib;
  const pdf = await PDFDocument.create();
  const regular = await pdf.embedFont(StandardFonts.Helvetica);
  const bold = await pdf.embedFont(StandardFonts.HelveticaBold);
  const letterhead = await pdf.embedJpg(background);
  const width = 595.28, height = 841.89;
  const left = 62, right = 62, bottom = 106, top = 712;
  const fontSize = 11, lineHeight = 16;
  let page, y;
  function addPage() {
    page = pdf.addPage([width, height]);
    page.drawImage(letterhead, {x:0, y:0, width, height});
    y = top;
  }
  function safe(text, font) {
    return Array.from(text.normalize('NFC'), character => {
      try { font.encodeText(character); return character; } catch { return '?'; }
    }).join('');
  }
  function wrap(text, font, size) {
    const lines = [];
    let line = '';
    for (const word of safe(text, font).split(/\s+/)) {
      const joined = line ? line + ' ' + word : word;
      if (font.widthOfTextAtSize(joined, size) <= width - left - right) {
        line = joined;
        continue;
      }
      if (line) lines.push(line);
      line = '';
      for (const character of word) {
        if (font.widthOfTextAtSize(line + character, size) > width - left - right) {
          lines.push(line);
          line = '';
        }
        line += character;
      }
    }
    if (line) lines.push(line);
    return lines;
  }
  function draw(line, font, size) {
    if (y < bottom) addPage();
    page.drawText(line, {x:left, y, size, font, color:rgb(.1,.08,.08)});
    y -= lineHeight;
  }
  addPage();
  for (const line of wrap(title.trim().toUpperCase(), bold, 15)) draw(line, bold, 15);
  y -= 14;
  for (const paragraph of body.split('\n')) {
    if (!paragraph.trim()) { y -= 7; continue; }
    const heading = paragraph.startsWith('CLÁUSULA ');
    const font = heading ? bold : regular;
    const lines = wrap(paragraph, font, fontSize);
    if (heading && y - (lines.length + 1) * lineHeight < bottom) addPage();
    for (const line of lines) draw(line, font, fontSize);
  }
  return pdf.save();
}
