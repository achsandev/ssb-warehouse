<?php

namespace App\Services\Reports;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Penulis XLSX sederhana tanpa dependency eksternal.
 *
 * Kapasitas yang didukung:
 *  - Judul dengan merge cell (cth. B2:H2) + center align (horizontal & vertical).
 *  - Header kolom (bold).
 *  - Baris data (teks atau angka, dideteksi otomatis).
 *  - Lebar kolom kustom.
 *
 * Catatan: data di-buffer di memory. Untuk dataset > ~100k rows, pertimbangkan
 * openspout / phpspreadsheet yang men-stream ke disk.
 */
class XlsxReportWriter
{
    /** @var array<int, array{row:int,startCol:string,endCol:string,text:string,style:int}> */
    private array $titles = [];

    /** @var array{row:int,startColIdx:int,values:array<int,string>}|null */
    private ?array $header = null;

    /** @var array<int, array{row:int,startColIdx:int,values:array<int,mixed>}> */
    private array $dataRows = [];

    /** @var array<int, float> */
    private array $columnWidths = [];

    /** @var array<int, int> Override style untuk data cell per kolom (idx 1-based). */
    private array $columnStyles = [];

    /** @var array<int, array{startRow:int,endRow:int,startCol:string,endCol:string,text:string,style:int}> */
    private array $mergeRanges = [];

    /** @var array<int, array{row:int,col:int,value:mixed,style:int}> */
    private array $extraCells = [];

    private string $sheetName;

    public function __construct(string $sheetName = 'Report')
    {
        // Sheet name dibatasi Excel: 31 char, tidak boleh mengandung : \ / ? * [ ]
        $this->sheetName = mb_substr(preg_replace('/[:\\/\\\\?*\[\]]/', '', $sheetName), 0, 31);
    }

    /** Tambah judul yang di-merge horizontal dari startCol..endCol. */
    public function addMergedTitle(int $row, string $startCol, string $endCol, string $text, int $style = 1): self
    {
        $this->titles[] = compact('row', 'startCol', 'endCol', 'text', 'style');

        return $this;
    }

    /** Set baris header. $startColIdx = 1 untuk A, 2 untuk B, dst. */
    public function setHeader(int $row, int $startColIdx, array $values): self
    {
        $this->header = [
            'row' => $row,
            'startColIdx' => $startColIdx,
            'values' => array_values($values),
        ];

        return $this;
    }

    public function addRow(int $row, int $startColIdx, array $values): self
    {
        $this->dataRows[] = [
            'row' => $row,
            'startColIdx' => $startColIdx,
            'values' => array_values($values),
        ];

        return $this;
    }

    public function setColumnWidth(int $colIdx, float $width): self
    {
        $this->columnWidths[$colIdx] = $width;

        return $this;
    }

    /** Set style override untuk seluruh data cell pada kolom tertentu. */
    public function setColumnStyle(int $colIdx, int $style): self
    {
        $this->columnStyles[$colIdx] = $style;

        return $this;
    }

    /**
     * Merge rectangular range (mendukung multi-row dan multi-column).
     * Isi teks hanya di sel pojok kiri atas.
     */
    public function addMerge(int $startRow, int $endRow, string $startCol, string $endCol, string $text, int $style = 0): self
    {
        $this->mergeRanges[] = compact('startRow', 'endRow', 'startCol', 'endCol', 'text', 'style');

        return $this;
    }

    /** Tulis 1 cell di posisi bebas (row & colIdx 1-based, A=1). */
    public function addCell(int $row, int $colIdx, mixed $value, int $style = 0): self
    {
        $this->extraCells[] = compact('row') + ['col' => $colIdx, 'value' => $value, 'style' => $style];

        return $this;
    }

    /**
     * Bangun file di path temp lalu stream ke response & hapus file temp.
     */
    public function streamResponse(string $filename): StreamedResponse
    {
        $tmp = $this->buildTmp();

        return new StreamedResponse(
            function () use ($tmp) {
                readfile($tmp);
                @unlink($tmp);
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Content-Length' => (string) filesize($tmp),
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    private function buildTmp(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('Gagal membuat temp file untuk XLSX.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuka ZipArchive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();

        return $tmp;
    }

    // ─── Worksheet XML ───────────────────────────────────────────────────────
    private function sheetXml(): string
    {
        // Pakai map col-keyed per row supaya write berikutnya (misal addCell) bisa override.
        /** @var array<int, array<int, array{value:mixed,style:int}>> */
        $rowsMap = [];

        $put = function (int $row, int $col, mixed $value, int $style) use (&$rowsMap): void {
            $rowsMap[$row][$col] = ['value' => $value, 'style' => $style];
        };

        // Titles (single-row merges via addMergedTitle)
        foreach ($this->titles as $t) {
            $put($t['row'], $this->colIndex($t['startCol']), $t['text'], $t['style']);
        }

        // Multi-row / multi-col merges (addMerge)
        foreach ($this->mergeRanges as $m) {
            $put($m['startRow'], $this->colIndex($m['startCol']), $m['text'], $m['style']);
        }

        // Header
        if ($this->header !== null) {
            foreach ($this->header['values'] as $i => $v) {
                $put($this->header['row'], $this->header['startColIdx'] + $i, $v, 4);
            }
        }

        // Data
        foreach ($this->dataRows as $r) {
            foreach ($r['values'] as $i => $v) {
                $col = $r['startColIdx'] + $i;
                $put($r['row'], $col, $v, $this->columnStyles[$col] ?? 0);
            }
        }

        // Extra cells (override akhir — addCell)
        foreach ($this->extraCells as $c) {
            $put($c['row'], $c['col'], $c['value'], $c['style']);
        }

        ksort($rowsMap);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Column widths
        if (! empty($this->columnWidths)) {
            $xml .= '<cols>';
            foreach ($this->columnWidths as $idx => $w) {
                $xml .= '<col min="'.$idx.'" max="'.$idx.'" width="'.$w.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach ($rowsMap as $rowIdx => $cells) {
            ksort($cells);

            $xml .= '<row r="'.$rowIdx.'">';
            foreach ($cells as $colIdx => $c) {
                $ref = $this->colLetter($colIdx).$rowIdx;
                $val = $c['value'];
                $style = $c['style'];

                if (is_int($val) || is_float($val)) {
                    $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$val.'</v></c>';
                } else {
                    $text = htmlspecialchars((string) ($val ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $xml .= '<c r="'.$ref.'" s="'.$style.'" t="inlineStr">'
                        .'<is><t xml:space="preserve">'.$text.'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        // Merge cells (gabungan single-row titles + multi-row/col ranges)
        $allMerges = [];
        foreach ($this->titles as $t) {
            $allMerges[] = $t['startCol'].$t['row'].':'.$t['endCol'].$t['row'];
        }
        foreach ($this->mergeRanges as $m) {
            $allMerges[] = $m['startCol'].$m['startRow'].':'.$m['endCol'].$m['endRow'];
        }
        if (! empty($allMerges)) {
            $xml .= '<mergeCells count="'.count($allMerges).'">';
            foreach ($allMerges as $ref) {
                $xml .= '<mergeCell ref="'.$ref.'"/>';
            }
            $xml .= '</mergeCells>';
        }

        $xml .= '</worksheet>';

        return $xml;
    }

    // ─── Styles ──────────────────────────────────────────────────────────────
    /**
     * Indeks style:
     *  0 — Arial 11pt                                    (data default, left)
     *  1 — Courier 14pt, center+middle                   (judul 1)
     *  2 — Arial bold 20pt #990033, center+middle        (judul 2)
     *  3 — Courier 13pt, center+middle                   (judul 3 / periode)
     *  4 — Arial bold 11pt dark cyan #003366, center     (header kolom)
     *  5 — Arial 11pt, right                             (data right-aligned, nominal Rp)
     *  6 — Arial 11pt, center                            (data center-aligned, e.g. No)
     *  7 — Arial italic 10pt, left                       (catatan di atas tabel)
     *  8 — Arial bold 11pt merah #CC0000, center         (Moving Category)
     */
    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="7">'
            .'<font><sz val="11"/><name val="Arial"/></font>'
            .'<font><sz val="14"/><name val="Courier"/></font>'
            .'<font><b/><sz val="20"/><color rgb="FF990033"/><name val="Arial"/></font>'
            .'<font><sz val="13"/><name val="Courier"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF003366"/><name val="Arial"/></font>'
            .'<font><i/><sz val="10"/><name val="Arial"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFCC0000"/><name val="Arial"/></font>'
            .'</fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="9">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="right" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="left" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    // ─── Package XMLs ────────────────────────────────────────────────────────
    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        $name = htmlspecialchars($this->sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    // ─── Column helpers ──────────────────────────────────────────────────────
    /** Ubah huruf kolom ke index 1-based. "A" => 1, "Z" => 26, "AA" => 27. */
    private function colIndex(string $col): int
    {
        $col = strtoupper($col);
        $idx = 0;
        for ($i = 0, $n = strlen($col); $i < $n; $i++) {
            $idx = $idx * 26 + (ord($col[$i]) - 64);
        }

        return $idx;
    }

    /** Ubah index 1-based ke huruf kolom. 1 => "A", 27 => "AA". */
    private function colLetter(int $idx): string
    {
        $letter = '';
        while ($idx > 0) {
            $mod = ($idx - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $idx = intdiv($idx - 1, 26);
        }

        return $letter;
    }
}
