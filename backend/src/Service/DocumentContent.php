<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DocumentContent
{
    public function validate(array $data): array
    {
        $result = [];
        foreach (['title' => 150, 'category' => 60, 'summary' => 300] as $key => $limit) {
            if (!is_string($data[$key] ?? null) || trim($data[$key]) === '' || mb_strlen($data[$key]) > $limit) {
                throw new BadRequestHttpException('Invalid document metadata.');
            }
            $result[$key] = trim($data[$key]);
        }
        $result['blocks'] = null;
        if (isset($data['blocks'])) {
            if (!is_array($data['blocks']) || !array_is_list($data['blocks']) || count($data['blocks']) < 1 || count($data['blocks']) > 30) {
                throw new BadRequestHttpException('Invalid block count.');
            }
            $blocks = [];
            $text = [];
            foreach ($data['blocks'] as $block) {
                if (!is_array($block) || !in_array($block['type'] ?? null, ['section', 'text', 'note', 'checklist', 'code'], true)) {
                    throw new BadRequestHttpException('Invalid block type.');
                }
                $type = $block['type'];
                $title = $block['title'] ?? '';
                if (!is_string($title) || mb_strlen($title) > 150 || ($type === 'section' && trim($title) === '')) {
                    throw new BadRequestHttpException('Invalid block title.');
                }
                $clean = ['type' => $type, 'title' => trim($title)];
                if ($type === 'checklist') {
                    $items = $block['items'] ?? null;
                    if (!is_array($items) || !array_is_list($items) || count($items) < 1 || count($items) > 20) { throw new BadRequestHttpException('Invalid checklist.'); }
                    foreach ($items as $item) {
                        if (!is_string($item) || trim($item) === '' || mb_strlen($item) > 500) { throw new BadRequestHttpException('Invalid checklist item.'); }
                    }
                    $clean['items'] = array_map('trim', $items);
                    $body = implode("\n", $clean['items']);
                } else {
                    $body = $block['text'] ?? null;
                    if (!is_string($body) || trim($body) === '' || mb_strlen($body) > 5000) { throw new BadRequestHttpException('Invalid block content.'); }
                    $clean['text'] = trim($body);
                }
                $blocks[] = $clean;
                $text[] = ($clean['title'] ? '## '.$clean['title']."\n" : '').$body;
            }
            $result['blocks'] = json_encode($blocks, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $result['content'] = implode("\n\n", $text);
        } else {
            $result['content'] = is_string($data['content'] ?? null) ? trim($data['content']) : '';
        }
        if ($result['content'] === '' || mb_strlen($result['content']) > 10000) { throw new BadRequestHttpException('Invalid document length.'); }
        return $result;
    }
}
