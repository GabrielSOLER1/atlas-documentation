// Parse headings without interpreting HTML.
export function parseSections(content) {
  const sections = []
  let current = { title: '', number: null, lines: [] }
  for (const line of content.replace(/\r\n?/g, '\n').split('\n')) {
    const numbered = line.match(/^\s*(\d+)\.\s+(.+)$/)
    const heading = line.match(/^#{1,3}\s+(.+)$/)
    if (numbered || heading) {
      if (current.title || current.lines.some(line => line.trim())) sections.push(current)
      current = { title: numbered ? numbered[2] : heading[1], number: numbered ? numbered[1] : null, lines: [] }
    } else current.lines.push(line)
  }
  if (current.title || current.lines.some(line => line.trim())) sections.push(current)
  return sections.map((section, index) => ({
    id: `section-${index + 1}`,
    title: section.title,
    number: section.number,
    paragraphs: section.lines.join('\n').trim().split(/\n\s*\n/).filter(Boolean),
  }))
}

export function readingMinutes(content) {
  return Math.max(1, Math.ceil(content.trim().split(/\s+/u).filter(Boolean).length / 180))
}
