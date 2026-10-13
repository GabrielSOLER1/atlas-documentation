import { test } from 'node:test'
import assert from 'node:assert/strict'
import { parseSections, readingMinutes } from './document.js'

test('numbered procedures retain all paragraphs and their original numbering', () => {
  const sections = parseSections('Introduction.\r\n\r\n1. Vérifier\r\nPremier paragraphe.\r\n\r\nDeuxième paragraphe.\r\n\r\n3. Terminer\r\nDernière étape.')
  assert.equal(sections.length, 3)
  assert.equal(sections[0].paragraphs[0], 'Introduction.')
  assert.equal(sections[1].number, '1')
  assert.deepEqual(sections[1].paragraphs, ['Premier paragraphe.', 'Deuxième paragraphe.'])
  assert.equal(sections[2].number, '3')
})

test('headings and unstructured text stay plain text, including HTML-like content', () => {
  const sections = parseSections('## Sécurité\n<script>alert(1)</script>\n\n## Suite\nContenu.')
  assert.equal(sections[0].title, 'Sécurité')
  assert.equal(sections[0].paragraphs[0], '<script>alert(1)</script>')
  assert.equal(parseSections('Simple texte.')[0].paragraphs[0], 'Simple texte.')
  assert.deepEqual(parseSections(''), [])
  assert.equal(readingMinutes('mot '.repeat(181)), 2)
})
