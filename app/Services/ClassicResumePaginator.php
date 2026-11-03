<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Options;
use DOMXPath;
use RuntimeException;

/** Keep Classic's two columns independent while bounding every PDF table row to one page. */
class ClassicResumePaginator
{
    public function paginate(string $html): string
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($document);
        $style = $this->element($xpath, '//style')->textContent;
        $header = $this->html($document, $this->element($xpath, '//table[@class="header"]'));
        $columns = [];

        foreach (['sidebar-inner', 'main'] as $class) {
            $column = $this->element($xpath, '//div[@class="'.$class.'"]');
            $chunks = [];
            foreach ($column->childNodes as $child) {
                if (! $child instanceof DOMElement) {
                    continue;
                }
                $chunks[] = $this->html($document, $child);
            }
            $columns[] = $chunks;
        }

        // A4 minus the template's 28px page margins, in CSS pixels.
        $width = 210 / 25.4 * 96 - 56;
        $height = 297 / 25.4 * 96 - 56;
        $headerHeight = $this->measure([$header], $style, $width)[0];
        $pages = [0 => []];
        foreach ($columns as $index => $chunks) {
            $columnWidth = $width * ($index === 0 ? 0.31 : 0.69) - ($index === 0 ? 16 : 17);
            $heights = $this->measure($chunks, $style, $columnWidth);
            $split = [];
            foreach ($chunks as $i => $chunk) {
                if ($heights[$i] > $height - 8) {
                    array_push($split, ...$this->splitEntry($chunk));
                } else {
                    $split[] = $chunk;
                }
            }
            if ($split !== $chunks) {
                $chunks = $split;
                $heights = $this->measure($chunks, $style, $columnWidth);
            }
            $page = 0;
            $used = 0;
            foreach ($chunks as $i => $chunk) {
                $capacity = $height - ($page === 0 ? $headerHeight + 10 : 0) - 8;
                // Keep headings and rating labels with the content immediately after them.
                $lookahead = str_contains($chunk, 'class="heading"') ? 2 : (preg_match('/class="(?:level-name|item-title|side-title)"/', $chunk) ? 1 : 0);
                $required = array_sum(array_slice($heights, $i, $lookahead + 1));
                $manualBreak = str_contains($chunk, 'data-page-break="1"');
                if (($manualBreak && ($page === 0 || $used > 0)) || ($used > 0 && $used + $required > $capacity)
                    || ($page === 0 && $heights[$i] > $capacity)) {
                    $page++;
                    $used = 0;
                }
                $pages[$page][$index][] = '<div class="classic-chunk">'.$chunk.'</div>';
                $used += $heights[$i];
            }
        }

        ksort($pages);
        $body = '';
        foreach ($pages as $page => $columns) {
            $body .= '<div'.($page > 0 ? ' style="page-break-before: always;"' : '').'>';
            if ($page === 0) {
                $body .= $header;
            }
            $body .= '<table style="table-layout: fixed; margin-top: '.($page === 0 ? '10' : '0').'px"><tr>'
                .'<td style="width: 31%; padding: 0"><div style="padding-right: 16px">'.implode('', $columns[0] ?? []).'</div></td>'
                .'<td style="width: 69%; padding: 0; border-left: 1px solid #e5e7eb"><div style="padding-left: 16px">'.implode('', $columns[1] ?? []).'</div></td>'
                .'</tr></table></div>';
        }

        $style .= "\n.classic-chunk { padding-top: 0.01px; }";

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'.$style.'</style></head><body>'.$body.'</body></html>';
    }

    /** @return list<string> */
    private function splitEntry(string $html): array
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>');
        $body = $document->getElementsByTagName('body')->item(0);
        $entry = $body?->firstChild;
        if (! $entry instanceof DOMElement || ! in_array($entry->getAttribute('class'), ['item', 'side-item'], true)) {
            return [$html];
        }

        $parts = [];
        foreach ($entry->childNodes as $part) {
            if (! $part instanceof DOMElement) {
                continue;
            }
            if ($part->tagName === 'ul') {
                foreach ($part->childNodes as $bullet) {
                    if ($bullet instanceof DOMElement) {
                        $parts[] = '<ul style="margin-bottom: 0">'.$this->html($document, $bullet).'</ul>';
                    }
                }
            } else {
                $parts[] = $this->html($document, $part);
            }
        }
        if ($parts !== [] && $entry->getAttribute('data-page-break') === '1') {
            $parts[0] = '<div data-page-break="1">'.$parts[0].'</div>';
        }

        return $parts ?: [$html];
    }

    private function element(DOMXPath $xpath, string $query): DOMElement
    {
        $matches = $xpath->query($query);
        $element = $matches === false ? null : $matches->item(0);
        if (! $element instanceof DOMElement) {
            throw new RuntimeException('Missing Classic template element: '.$query);
        }

        return $element;
    }

    private function html(DOMDocument $document, DOMElement $element): string
    {
        $html = $document->saveHTML($element);
        if ($html === false) {
            throw new RuntimeException('Could not serialize Classic template content.');
        }

        return $html;
    }

    /**
     * @param  list<string>  $chunks
     * @return list<float>
     */
    private function measure(array $chunks, string $style, float $width): array
    {
        if ($chunks === []) {
            return [];
        }

        $heights = [];
        $pdf = new Dompdf(new Options(['isRemoteEnabled' => false]));
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function (Frame $frame) use (&$heights): void {
            $node = $frame->get_node();
            if ($node instanceof DOMElement && $node->hasAttribute('data-chunk')) {
                $heights[(int) $node->getAttribute('data-chunk')] = $frame->get_margin_height() / 0.75;
            }
        }]]);
        $body = '';
        foreach ($chunks as $i => $chunk) {
            $body .= '<div data-chunk="'.$i.'" style="padding-top: 0.01px">'.$chunk.'</div>';
        }
        $pdf->loadHtml('<html><head><meta charset="utf-8"><style>'.$style.' @page { margin: 0; } body { padding: 0; width: '.$width.'px; }</style></head><body>'.$body.'</body></html>');
        $pdf->setPaper([0, 0, $width * 0.75, 100000]);
        $pdf->render();
        ksort($heights);

        return array_values($heights);
    }
}
