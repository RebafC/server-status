<?php

declare(strict_types=1);

//
// Parsedown
// http://parsedown.org
//
// (c) Emanuil Rusev
// http://erusev.com
//
// For the full license information, view the LICENSE file that was distributed
// with this source code.
//
//

class Parsedown
{
    // ~
    public const version = '1.8.0-beta-5';

    protected $breaksEnabled;

    protected $markupEscaped;

    protected $urlsLinked = true;

    protected $safeMode;

    protected $strictMode;

    protected $safeLinksWhitelist = [
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'tel:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    ];

    //
    // Lines
    //

    protected $BlockTypes = [
        '#' => ['Header'],
        '*' => ['Rule', 'List'],
        '+' => ['List'],
        '-' => ['SetextHeader', 'Table', 'Rule', 'List'],
        '0' => ['List'],
        '1' => ['List'],
        '2' => ['List'],
        '3' => ['List'],
        '4' => ['List'],
        '5' => ['List'],
        '6' => ['List'],
        '7' => ['List'],
        '8' => ['List'],
        '9' => ['List'],
        ':' => ['Table'],
        '<' => ['Comment', 'Markup'],
        '=' => ['SetextHeader'],
        '>' => ['Quote'],
        '[' => ['Reference'],
        '_' => ['Rule'],
        '`' => ['FencedCode'],
        '|' => ['Table'],
        '~' => ['FencedCode'],
    ];

    // ~

    protected $unmarkedBlockTypes = [
        'Code',
    ];

    //
    // Inline Elements
    //

    protected $InlineTypes = [
        '!' => ['Image'],
        '&' => ['SpecialCharacter'],
        '*' => ['Emphasis'],
        ':' => ['Url'],
        '<' => ['UrlTag', 'EmailTag', 'Markup'],
        '[' => ['Link'],
        '_' => ['Emphasis'],
        '`' => ['Code'],
        '~' => ['Strikethrough'],
        '\\' => ['EscapeSequence'],
    ];

    // ~

    protected $inlineMarkerList = '!*_&[:<`~\\';

    //
    // Fields
    //

    protected $DefinitionData;

    //
    // Read-Only

    protected $specialCharacters = [
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '~',
    ];

    protected $StrongRegex = [
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
    ];

    protected $EmRegex = [
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];

    protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    protected $voidElements = [
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
    ];

    protected $textLevelElements = [
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code', 'strike', 'marquee',
        'q', 'rt', 'ins', 'font', 'strong',
        's', 'tt', 'kbd', 'mark',
        'u', 'xm', 'sub', 'nobr',
        'sup', 'ruby',
        'var', 'span',
        'wbr', 'time',
    ];

    private static array $instances = [];

    // ~

    public function text($text): string
    {
        $Elements = $this->textElements($text);

        // convert to markup
        $markup = $this->elements($Elements);

        // trim line breaks
        return trim($markup, "\n");
    }

    //
    // Setters
    //

    public function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    public function setMarkupEscaped($markupEscaped)
    {
        $this->markupEscaped = $markupEscaped;

        return $this;
    }

    public function setUrlsLinked($urlsLinked)
    {
        $this->urlsLinked = $urlsLinked;

        return $this;
    }

    public function setSafeMode($safeMode)
    {
        $this->safeMode = (bool) $safeMode;

        return $this;
    }

    public function setStrictMode($strictMode)
    {
        $this->strictMode = (bool) $strictMode;

        return $this;
    }

    //
    // ~
    //

    public function line($text, array $nonNestables = []): string
    {
        return $this->elements($this->lineElements($text, $nonNestables));
    }

    //
    // Deprecated Methods
    //

    public function parse($text): string
    {
        return $this->text($text);
    }

    public static function instance($name = 'default')
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $static = new static();

        self::$instances[$name] = $static;

        return $static;
    }

    protected function textElements($text): array
    {
        // make sure no definitions are set
        $this->DefinitionData = [];

        // standardize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // remove surrounding line breaks
        $text = trim($text, "\n");

        // split text into lines
        $lines = explode("\n", $text);

        // iterate through lines to identify blocks
        return $this->linesElements($lines);
    }

    //
    // Blocks
    //

    protected function lines(array $lines): string
    {
        return $this->elements($this->linesElements($lines));
    }

    /**
     * @return mixed[]
     */
    protected function linesElements(array $lines): array
    {
        $Elements = [];
        $CurrentBlock = null;

        foreach ($lines as $line) {
            if ('' === rtrim($line)) {
                if (isset($CurrentBlock)) {
                    $CurrentBlock['interrupted'] = (
                        isset($CurrentBlock['interrupted'])
                        ? $CurrentBlock['interrupted'] + 1 : 1
                    );
                }

                continue;
            }

            while (($beforeTab = strstr($line, "\t", true)) !== false) {
                $shortage = 4 - mb_strlen($beforeTab, 'utf-8') % 4;

                $line = $beforeTab
                    . str_repeat(' ', $shortage)
                    . substr($line, strlen($beforeTab) + 1);
            }

            $indent = strspn($line, ' ');

            $text = $indent > 0 ? substr($line, $indent) : $line;

            // ~

            $Line = ['body' => $line, 'indent' => $indent, 'text' => $text];

            // ~

            if (isset($CurrentBlock['continuable'])) {
                $methodName = 'block' . $CurrentBlock['type'] . 'Continue';
                $Block = $this->{$methodName}($Line, $CurrentBlock);

                if (isset($Block)) {
                    $CurrentBlock = $Block;

                    continue;
                }

                if ($this->isBlockCompletable($CurrentBlock['type'])) {
                    $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
                    $CurrentBlock = $this->{$methodName}($CurrentBlock);
                }
            }

            // ~

            $marker = $text[0];

            // ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker])) {
                foreach ($this->BlockTypes[$marker] as $blockType) {
                    $blockTypes[] = $blockType;
                }
            }

            //
            // ~

            foreach ($blockTypes as $blockType) {
                $Block = $this->{sprintf('block%s', $blockType)}($Line, $CurrentBlock);

                if (isset($Block)) {
                    $Block['type'] = $blockType;

                    if (!isset($Block['identified'])) {
                        if (isset($CurrentBlock)) {
                            $Elements[] = $this->extractElement($CurrentBlock);
                        }

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType)) {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            // ~

            if (isset($CurrentBlock) && 'Paragraph' === $CurrentBlock['type']) {
                $Block = $this->paragraphContinue($Line, $CurrentBlock);
            }

            if (isset($Block)) {
                $CurrentBlock = $Block;
            } else {
                if (isset($CurrentBlock)) {
                    $Elements[] = $this->extractElement($CurrentBlock);
                }

                $CurrentBlock = $this->paragraph($Line);

                $CurrentBlock['identified'] = true;
            }
        }

        // ~

        if (isset($CurrentBlock['continuable']) && $this->isBlockCompletable($CurrentBlock['type'])) {
            $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
            $CurrentBlock = $this->{$methodName}($CurrentBlock);
        }

        // ~

        if (isset($CurrentBlock)) {
            $Elements[] = $this->extractElement($CurrentBlock);
        }

        // ~

        return $Elements;
    }

    protected function extractElement(array $Component)
    {
        if (!isset($Component['element'])) {
            if (isset($Component['markup'])) {
                $Component['element'] = ['rawHtml' => $Component['markup']];
            } elseif (isset($Component['hidden'])) {
                $Component['element'] = [];
            }
        }

        return $Component['element'];
    }

    protected function isBlockContinuable(string $Type): bool
    {
        return method_exists($this, 'block' . $Type . 'Continue');
    }

    protected function isBlockCompletable(string $Type): bool
    {
        return method_exists($this, 'block' . $Type . 'Complete');
    }

    //
    // Code

    protected function blockCode(array $Line, array $Block = null)
    {
        if (isset($Block) && 'Paragraph' === $Block['type'] && !isset($Block['interrupted'])) {
            return;
        }

        if ($Line['indent'] >= 4) {
            $text = substr($Line['body'], 4);

            return [
                'element' => [
                    'name' => 'pre',
                    'element' => [
                        'name' => 'code',
                        'text' => $text,
                    ],
                ],
            ];
        }
    }

    protected function blockCodeContinue(array $Line, array $Block)
    {
        if ($Line['indent'] >= 4) {
            if (isset($Block['interrupted'])) {
                $Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

                unset($Block['interrupted']);
            }

            $Block['element']['element']['text'] .= "\n";

            $text = substr($Line['body'], 4);

            $Block['element']['element']['text'] .= $text;

            return $Block;
        }
    }

    protected function blockCodeComplete($Block)
    {
        return $Block;
    }

    //
    // Comment

    protected function blockComment(array $Line)
    {
        if ($this->markupEscaped || $this->safeMode) {
            return;
        }

        if (str_starts_with($Line['text'], '<!--')) {
            $Block = [
                'element' => [
                    'rawHtml' => $Line['body'],
                    'autobreak' => true,
                ],
            ];

            if (str_contains($Line['text'], '-->')) {
                $Block['closed'] = true;
            }

            return $Block;
        }
    }

    protected function blockCommentContinue(array $Line, array $Block)
    {
        if (isset($Block['closed'])) {
            return;
        }

        $Block['element']['rawHtml'] .= "\n" . $Line['body'];

        if (str_contains($Line['text'], '-->')) {
            $Block['closed'] = true;
        }

        return $Block;
    }

    //
    // Fenced Code

    protected function blockFencedCode(array $Line)
    {
        $marker = $Line['text'][0];

        $openerLength = strspn($Line['text'], $marker);

        if ($openerLength < 3) {
            return;
        }

        $infostring = trim(substr($Line['text'], $openerLength), "\t ");

        if (str_contains($infostring, '`')) {
            return;
        }

        $Element = [
            'name' => 'code',
            'text' => '',
        ];

        if ('' !== $infostring) {
            $Element['attributes'] = ['class' => sprintf('language-%s', $infostring)];
        }

        return [
            'char' => $marker,
            'openerLength' => $openerLength,
            'element' => [
                'name' => 'pre',
                'element' => $Element,
            ],
        ];
    }

    protected function blockFencedCodeContinue(array $Line, array $Block)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

            unset($Block['interrupted']);
        }

        if (($len = strspn($Line['text'], $Block['char'])) >= $Block['openerLength']
            && '' === rtrim(substr($Line['text'], $len), ' ')
        ) {
            $Block['element']['element']['text'] = substr($Block['element']['element']['text'], 1);

            $Block['complete'] = true;

            return $Block;
        }

        $Block['element']['element']['text'] .= "\n" . $Line['body'];

        return $Block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        return $Block;
    }

    //
    // Header

    protected function blockHeader(array $Line)
    {
        $level = strspn($Line['text'], '#');

        if ($level > 6) {
            return;
        }

        $text = trim($Line['text'], '#');

        if ($this->strictMode && isset($text[0]) && ' ' !== $text[0]) {
            return;
        }

        $text = trim($text, ' ');

        return [
            'element' => [
                'name' => 'h' . $level,
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $text,
                    'destination' => 'elements',
                ],
            ],
        ];
    }

    //
    // List

    protected function blockList(array $Line, array $CurrentBlock = null)
    {
        [$name, $pattern] = $Line['text'][0] <= '-' ? ['ul', '[*+-]'] : ['ol', '[0-9]{1,9}+[.\)]'];

        if (preg_match('/^(' . $pattern . '([ ]++|$))(.*+)/', $Line['text'], $matches)) {
            $contentIndent = strlen($matches[2]);

            if ($contentIndent >= 5) {
                --$contentIndent;
                $matches[1] = substr($matches[1], 0, -$contentIndent);
                $matches[3] = str_repeat(' ', $contentIndent) . $matches[3];
            } elseif (0 === $contentIndent) {
                $matches[1] .= ' ';
            }

            $markerWithoutWhitespace = strstr($matches[1], ' ', true);

            $Block = [
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'data' => [
                    'type' => $name,
                    'marker' => $matches[1],
                    'markerType' => ('ul' === $name ? $markerWithoutWhitespace : substr($markerWithoutWhitespace, -1)),
                ],
                'element' => [
                    'name' => $name,
                    'elements' => [],
                ],
            ];
            $Block['data']['markerTypeRegex'] = preg_quote($Block['data']['markerType'], '/');

            if ('ol' === $name) {
                $listStart = ltrim(strstr($matches[1], (string) $Block['data']['markerType'], true), '0') ?: '0';

                if ('1' !== $listStart) {
                    if (
                        isset($CurrentBlock)
                        && 'Paragraph' === $CurrentBlock['type']
                        && !isset($CurrentBlock['interrupted'])
                    ) {
                        return;
                    }

                    $Block['element']['attributes'] = ['start' => $listStart];
                }
            }

            $Block['li'] = [
                'name' => 'li',
                'handler' => [
                    'function' => 'li',
                    'argument' => empty($matches[3]) ? [] : [$matches[3]],
                    'destination' => 'elements',
                ],
            ];

            $Block['element']['elements'][] = &$Block['li'];

            return $Block;
        }
    }

    protected function blockListContinue(array $Line, array $Block)
    {
        if (isset($Block['interrupted']) && empty($Block['li']['handler']['argument'])) {
            return null;
        }

        $requiredIndent = ($Block['indent'] + strlen($Block['data']['marker']));

        if ($Line['indent'] < $requiredIndent
            && (
                (
                    'ol' === $Block['data']['type']
                    && preg_match('/^[0-9]++' . $Block['data']['markerTypeRegex'] . '(?:[ ]++(.*)|$)/', $Line['text'], $matches)
                ) || (
                    'ul' === $Block['data']['type']
                    && preg_match('/^' . $Block['data']['markerTypeRegex'] . '(?:[ ]++(.*)|$)/', $Line['text'], $matches)
                )
            )
        ) {
            if (isset($Block['interrupted'])) {
                $Block['li']['handler']['argument'][] = '';

                $Block['loose'] = true;

                unset($Block['interrupted']);
            }

            unset($Block['li']);

            $text = $matches[1] ?? '';

            $Block['indent'] = $Line['indent'];

            $Block['li'] = [
                'name' => 'li',
                'handler' => [
                    'function' => 'li',
                    'argument' => [$text],
                    'destination' => 'elements',
                ],
            ];

            $Block['element']['elements'][] = &$Block['li'];

            return $Block;
        }

        if ($Line['indent'] < $requiredIndent && $this->blockList($Line)) {
            return null;
        }

        if ('[' === $Line['text'][0] && $this->blockReference($Line)) {
            return $Block;
        }

        if ($Line['indent'] >= $requiredIndent) {
            if (isset($Block['interrupted'])) {
                $Block['li']['handler']['argument'][] = '';

                $Block['loose'] = true;

                unset($Block['interrupted']);
            }

            $text = substr($Line['body'], $requiredIndent);

            $Block['li']['handler']['argument'][] = $text;

            return $Block;
        }

        if (!isset($Block['interrupted'])) {
            $text = preg_replace('/^[ ]{0,' . $requiredIndent . '}+/', '', $Line['body']);

            $Block['li']['handler']['argument'][] = $text;

            return $Block;
        }
    }

    protected function blockListComplete(array $Block): array
    {
        if (isset($Block['loose'])) {
            foreach ($Block['element']['elements'] as &$li) {
                if ('' !== end($li['handler']['argument'])) {
                    $li['handler']['argument'][] = '';
                }
            }
        }

        return $Block;
    }

    //
    // Quote

    protected function blockQuote(array $Line)
    {
        if (preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches)) {
            return [
                'element' => [
                    'name' => 'blockquote',
                    'handler' => [
                        'function' => 'linesElements',
                        'argument' => (array) $matches[1],
                        'destination' => 'elements',
                    ],
                ],
            ];
        }
    }

    protected function blockQuoteContinue(array $Line, array $Block)
    {
        if (isset($Block['interrupted'])) {
            return;
        }

        if ('>' === $Line['text'][0] && preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches)) {
            $Block['element']['handler']['argument'][] = $matches[1];

            return $Block;
        }

        if (!isset($Block['interrupted'])) {
            $Block['element']['handler']['argument'][] = $Line['text'];

            return $Block;
        }
    }

    //
    // Rule

    protected function blockRule(array $Line)
    {
        $marker = $Line['text'][0];
        if (substr_count($Line['text'], $marker) < 3) {
            return;
        }

        if ('' !== rtrim($Line['text'], sprintf(' %s', $marker))) {
            return;
        }

        return [
            'element' => [
                'name' => 'hr',
            ],
        ];
    }

    //
    // Setext

    protected function blockSetextHeader(array $Line, array $Block = null)
    {
        if (!isset($Block) || 'Paragraph' !== $Block['type'] || isset($Block['interrupted'])) {
            return;
        }

        if ($Line['indent'] < 4 && '' === rtrim(rtrim($Line['text'], ' '), $Line['text'][0])) {
            $Block['element']['name'] = '=' === $Line['text'][0] ? 'h1' : 'h2';

            return $Block;
        }
    }

    //
    // Markup

    protected function blockMarkup(array $Line)
    {
        if ($this->markupEscaped || $this->safeMode) {
            return;
        }

        if (preg_match('/^<[\/]?+(\w*)(?:[ ]*+' . $this->regexHtmlAttribute . ')*+[ ]*+(\/)?>/', $Line['text'], $matches)) {
            $element = strtolower($matches[1]);

            if (in_array($element, $this->textLevelElements, true)) {
                return;
            }

            return [
                'name' => $matches[1],
                'element' => [
                    'rawHtml' => $Line['text'],
                    'autobreak' => true,
                ],
            ];
        }
    }

    protected function blockMarkupContinue(array $Line, array $Block)
    {
        if (isset($Block['closed']) || isset($Block['interrupted'])) {
            return;
        }

        $Block['element']['rawHtml'] .= "\n" . $Line['body'];

        return $Block;
    }

    //
    // Reference

    protected function blockReference(array $Line)
    {
        if (str_contains($Line['text'], ']')
            && preg_match('/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/', $Line['text'], $matches)
        ) {
            $id = strtolower($matches[1]);

            $Data = [
                'url' => $matches[2],
                'title' => $matches[3] ?? null,
            ];

            $this->DefinitionData['Reference'][$id] = $Data;

            return [
                'element' => [],
            ];
        }
    }

    //
    // Table

    protected function blockTable(array $Line, array $Block = null)
    {
        if (!isset($Block) || 'Paragraph' !== $Block['type'] || isset($Block['interrupted'])) {
            return;
        }

        if (
            !str_contains($Block['element']['handler']['argument'], '|')
            && !str_contains($Line['text'], '|')
            && !str_contains($Line['text'], ':')
            || str_contains($Block['element']['handler']['argument'], "\n")
        ) {
            return;
        }

        if ('' !== rtrim($Line['text'], ' -:|')) {
            return;
        }

        $alignments = [];

        $divider = $Line['text'];

        $divider = trim($divider);
        $divider = trim($divider, '|');

        $dividerCells = explode('|', $divider);

        foreach ($dividerCells as $dividerCell) {
            $dividerCell = trim($dividerCell);

            if ('' === $dividerCell) {
                return;
            }

            $alignment = null;

            if (':' === $dividerCell[0]) {
                $alignment = 'left';
            }

            if (':' === substr($dividerCell, -1)) {
                $alignment = 'left' === $alignment ? 'center' : 'right';
            }

            $alignments[] = $alignment;
        }

        // ~

        $HeaderElements = [];

        $header = $Block['element']['handler']['argument'];

        $header = trim($header);
        $header = trim($header, '|');

        $headerCells = explode('|', $header);

        if (count($headerCells) !== count($alignments)) {
            return;
        }

        foreach ($headerCells as $index => $headerCell) {
            $headerCell = trim($headerCell);

            $HeaderElement = [
                'name' => 'th',
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $headerCell,
                    'destination' => 'elements',
                ],
            ];

            if (isset($alignments[$index])) {
                $alignment = $alignments[$index];

                $HeaderElement['attributes'] = [
                    'style' => sprintf('text-align: %s;', $alignment),
                ];
            }

            $HeaderElements[] = $HeaderElement;
        }

        // ~

        $Block = [
            'alignments' => $alignments,
            'identified' => true,
            'element' => [
                'name' => 'table',
                'elements' => [],
            ],
        ];

        $Block['element']['elements'][] = [
            'name' => 'thead',
        ];

        $Block['element']['elements'][] = [
            'name' => 'tbody',
            'elements' => [],
        ];

        $Block['element']['elements'][0]['elements'][] = [
            'name' => 'tr',
            'elements' => $HeaderElements,
        ];

        return $Block;
    }

    protected function blockTableContinue(array $Line, array $Block)
    {
        if (isset($Block['interrupted'])) {
            return;
        }

        if (1 === (is_countable($Block['alignments']) ? count($Block['alignments']) : 0) || '|' === $Line['text'][0] || strpos($Line['text'], '|')) {
            $Elements = [];

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches);

            $cells = array_slice($matches[0], 0, is_countable($Block['alignments']) ? count($Block['alignments']) : 0);

            foreach ($cells as $index => $cell) {
                $cell = trim($cell);

                $Element = [
                    'name' => 'td',
                    'handler' => [
                        'function' => 'lineElements',
                        'argument' => $cell,
                        'destination' => 'elements',
                    ],
                ];

                if (isset($Block['alignments'][$index])) {
                    $Element['attributes'] = [
                        'style' => 'text-align: ' . $Block['alignments'][$index] . ';',
                    ];
                }

                $Elements[] = $Element;
            }

            $Element = [
                'name' => 'tr',
                'elements' => $Elements,
            ];

            $Block['element']['elements'][1]['elements'][] = $Element;

            return $Block;
        }
    }

    //
    // ~
    //

    protected function paragraph(array $Line): array
    {
        return [
            'type' => 'Paragraph',
            'element' => [
                'name' => 'p',
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $Line['text'],
                    'destination' => 'elements',
                ],
            ],
        ];
    }

    protected function paragraphContinue(array $Line, array $Block)
    {
        if (isset($Block['interrupted'])) {
            return;
        }

        $Block['element']['handler']['argument'] .= "\n" . $Line['text'];

        return $Block;
    }

    protected function lineElements($text, array $nonNestables = [])
    {
        // standardize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $Elements = [];

        $nonNestables = (
            [] === $nonNestables
            ? []
            : array_combine($nonNestables, $nonNestables)
        );

        // $excerpt is based on the first occurrence of a marker

        while ($excerpt = strpbrk($text, $this->inlineMarkerList)) {
            $marker = $excerpt[0];

            $markerPosition = strlen($text) - strlen($excerpt);

            $Excerpt = ['text' => $excerpt, 'context' => $text];

            foreach ($this->InlineTypes[$marker] as $inlineType) {
                // check to see if the current inline type is nestable in the current context

                if (isset($nonNestables[$inlineType])) {
                    continue;
                }

                $Inline = $this->{sprintf('inline%s', $inlineType)}($Excerpt);

                if (!isset($Inline)) {
                    continue;
                }

                // makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) && $Inline['position'] > $markerPosition) {
                    continue;
                }

                // sets a default inline position

                if (!isset($Inline['position'])) {
                    $Inline['position'] = $markerPosition;
                }

                // cause the new element to 'inherit' our non nestables

                $Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
                    ? array_merge($Inline['element']['nonNestables'], $nonNestables)
                    : $nonNestables;

                // the text that comes before the inline
                $unmarkedText = substr($text, 0, $Inline['position']);

                // compile the unmarked text
                $InlineText = $this->inlineText($unmarkedText);
                $Elements[] = $InlineText['element'];

                // compile the inline
                $Elements[] = $this->extractElement($Inline);

                // remove the examined text
                $text = substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            // the marker does not belong to an inline

            $unmarkedText = substr($text, 0, $markerPosition + 1);

            $InlineText = $this->inlineText($unmarkedText);
            $Elements[] = $InlineText['element'];

            $text = substr($text, $markerPosition + 1);
        }

        $InlineText = $this->inlineText($text);
        $Elements[] = $InlineText['element'];

        foreach ($Elements as &$Element) {
            if (!isset($Element['autobreak'])) {
                $Element['autobreak'] = false;
            }
        }

        return $Elements;
    }

    //
    // ~
    //

    protected function inlineText($text): array
    {
        $Inline = [
            'extent' => strlen($text),
            'element' => [],
        ];

        $Inline['element']['elements'] = self::pregReplaceElements(
            $this->breaksEnabled ? '/[ ]*+\n/' : '/(?:[ ]*+\\\\|[ ]{2,}+)\n/',
            [
                ['name' => 'br'],
                ['text' => "\n"],
            ],
            $text
        );

        return $Inline;
    }

    protected function inlineCode(array $Excerpt)
    {
        $marker = $Excerpt['text'][0];

        if (preg_match('/^([' . $marker . ']++)[ ]*+(.+?)[ ]*+(?<![' . $marker . '])\1(?!' . $marker . ')/s', $Excerpt['text'], $matches)) {
            $text = $matches[2];
            $text = preg_replace('/[ ]*+\n/', ' ', $text);

            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'code',
                    'text' => $text,
                ],
            ];
        }
    }

    protected function inlineEmailTag(array $Excerpt)
    {
        $hostnameLabel = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';

        $commonMarkEmail = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]++@'
            . $hostnameLabel . '(?:\.' . $hostnameLabel . ')*';

        if (str_contains($Excerpt['text'], '>')
            && preg_match(sprintf('/^<((mailto:)?%s)>/i', $commonMarkEmail), $Excerpt['text'], $matches)
        ) {
            $url = $matches[1];

            if (!isset($matches[2])) {
                $url = sprintf('mailto:%s', $url);
            }

            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    protected function inlineEmphasis(array $Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
        }

        $marker = $Excerpt['text'][0];

        if ($Excerpt['text'][1] === $marker && preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'strong';
        } elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'em';
        } else {
            return;
        }

        return [
            'extent' => strlen($matches[0]),
            'element' => [
                'name' => $emphasis,
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $matches[1],
                    'destination' => 'elements',
                ],
            ],
        ];
    }

    protected function inlineEscapeSequence(array $Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
        }

        if (!in_array($Excerpt['text'][1], $this->specialCharacters, true)) {
            return;
        }

        return [
            'element' => ['rawHtml' => $Excerpt['text'][1]],
            'extent' => 2,
        ];
    }

    protected function inlineImage(array $Excerpt)
    {
        if (!isset($Excerpt['text'][1]) || '[' !== $Excerpt['text'][1]) {
            return;
        }

        $Excerpt['text'] = substr($Excerpt['text'], 1);

        $Link = $this->inlineLink($Excerpt);

        if (null === $Link) {
            return;
        }

        $Inline = [
            'extent' => $Link['extent'] + 1,
            'element' => [
                'name' => 'img',
                'attributes' => [
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['handler']['argument'],
                ],
                'autobreak' => true,
            ],
        ];

        $Inline['element']['attributes'] += $Link['element']['attributes'];

        unset($Inline['element']['attributes']['href']);

        return $Inline;
    }

    protected function inlineLink(array $Excerpt)
    {
        $Element = [
            'name' => 'a',
            'handler' => [
                'function' => 'lineElements',
                'argument' => null,
                'destination' => 'elements',
            ],
            'nonNestables' => ['Url', 'Link'],
            'attributes' => [
                'href' => null,
                'title' => null,
            ],
        ];

        $extent = 0;

        $remainder = $Excerpt['text'];

        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) {
            $Element['handler']['argument'] = $matches[1];

            $extent += strlen($matches[0]);

            $remainder = substr($remainder, $extent);
        } else {
            return;
        }

        if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches)) {
            $Element['attributes']['href'] = $matches[1];

            if (isset($matches[2])) {
                $Element['attributes']['title'] = substr($matches[2], 1, -1);
            }

            $extent += strlen($matches[0]);
        } else {
            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
                $definition = 0 !== strlen($matches[1]) ? $matches[1] : $Element['handler']['argument'];
                $definition = strtolower($definition);

                $extent += strlen($matches[0]);
            } else {
                $definition = strtolower($Element['handler']['argument']);
            }

            if (!isset($this->DefinitionData['Reference'][$definition])) {
                return;
            }

            $Definition = $this->DefinitionData['Reference'][$definition];

            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }

        return [
            'extent' => $extent,
            'element' => $Element,
        ];
    }

    protected function inlineMarkup(array $Excerpt)
    {
        if ($this->markupEscaped || $this->safeMode || !str_contains($Excerpt['text'], '>')) {
            return;
        }

        if ('/' === $Excerpt['text'][1] && preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt['text'], $matches)) {
            return [
                'element' => ['rawHtml' => $matches[0]],
                'extent' => strlen($matches[0]),
            ];
        }

        if ('!' === $Excerpt['text'][1] && preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt['text'], $matches)) {
            return [
                'element' => ['rawHtml' => $matches[0]],
                'extent' => strlen($matches[0]),
            ];
        }

        if (' ' === $Excerpt['text'][1]) {
            return;
        }

        if (!preg_match('/^<\w[\w-]*+(?:[ ]*+' . $this->regexHtmlAttribute . ')*+[ ]*+\/?>/s', $Excerpt['text'], $matches)) {
            return;
        }

        return [
            'element' => ['rawHtml' => $matches[0]],
            'extent' => strlen($matches[0]),
        ];
    }

    protected function inlineSpecialCharacter(array $Excerpt)
    {
        if (' ' === substr($Excerpt['text'], 1, 1)) {
            return;
        }

        if (!str_contains($Excerpt['text'], ';')) {
            return;
        }

        if (!preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt['text'], $matches)) {
            return;
        }

        return [
            'element' => ['rawHtml' => '&' . $matches[1] . ';'],
            'extent' => strlen($matches[0]),
        ];
    }

    protected function inlineStrikethrough(array $Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
        }

        if ('~' !== $Excerpt['text'][1]) {
            return;
        }

        if (!preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches)) {
            return;
        }

        return [
            'extent' => strlen($matches[0]),
            'element' => [
                'name' => 'del',
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $matches[1],
                    'destination' => 'elements',
                ],
            ],
        ];
    }

    protected function inlineUrl(array $Excerpt)
    {
        if (true !== $this->urlsLinked || !isset($Excerpt['text'][2]) || '/' !== $Excerpt['text'][2]) {
            return;
        }

        if (str_contains($Excerpt['context'], 'http')
            && preg_match('/\bhttps?+:[\/]{2}[^\s<]+\b\/*+/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE)
        ) {
            $url = $matches[0][0];

            return [
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    protected function inlineUrlTag(array $Excerpt)
    {
        if (str_contains($Excerpt['text'], '>') && preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt['text'], $matches)) {
            $url = $matches[1];

            return [
                'extent' => strlen($matches[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    // ~

    protected function unmarkedText($text): string
    {
        $Inline = $this->inlineText($text);

        return $this->element($Inline['element']);
    }

    //
    // Handlers
    //

    protected function handle(array $Element)
    {
        if (isset($Element['handler'])) {
            if (!isset($Element['nonNestables'])) {
                $Element['nonNestables'] = [];
            }

            if (is_string($Element['handler'])) {
                $function = $Element['handler'];
                $argument = $Element['text'];
                unset($Element['text']);
                $destination = 'rawHtml';
            } else {
                $function = $Element['handler']['function'];
                $argument = $Element['handler']['argument'];
                $destination = $Element['handler']['destination'];
            }

            $Element[$destination] = $this->{$function}($argument, $Element['nonNestables']);

            if ('handler' === $destination) {
                $Element = $this->handle($Element);
            }

            unset($Element['handler']);
        }

        return $Element;
    }

    protected function handleElementRecursive(array $Element)
    {
        return $this->elementApplyRecursive(fn (array $Element) => $this->handle($Element), $Element);
    }

    protected function handleElementsRecursive(array $Elements): array
    {
        return $this->elementsApplyRecursive(fn (array $Element) => $this->handle($Element), $Elements);
    }

    protected function elementApplyRecursive($closure, array $Element)
    {
        $Element = call_user_func($closure, $Element);

        if (isset($Element['elements'])) {
            $Element['elements'] = $this->elementsApplyRecursive($closure, $Element['elements']);
        } elseif (isset($Element['element'])) {
            $Element['element'] = $this->elementApplyRecursive($closure, $Element['element']);
        }

        return $Element;
    }

    protected function elementApplyRecursiveDepthFirst($closure, array $Element)
    {
        if (isset($Element['elements'])) {
            $Element['elements'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['elements']);
        } elseif (isset($Element['element'])) {
            $Element['element'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['element']);
        }

        return call_user_func($closure, $Element);
    }

    protected function elementsApplyRecursive($closure, array $Elements): array
    {
        foreach ($Elements as &$Element) {
            $Element = $this->elementApplyRecursive($closure, $Element);
        }

        return $Elements;
    }

    protected function elementsApplyRecursiveDepthFirst($closure, array $Elements): array
    {
        foreach ($Elements as &$Element) {
            $Element = $this->elementApplyRecursiveDepthFirst($closure, $Element);
        }

        return $Elements;
    }

    protected function element(array $Element): string
    {
        if ($this->safeMode) {
            $Element = $this->sanitiseElement($Element);
        }

        // identity map if element has no handler
        $Element = $this->handle($Element);

        $hasName = isset($Element['name']);

        $markup = '';

        if ($hasName) {
            $markup .= '<' . $Element['name'];

            if (isset($Element['attributes'])) {
                foreach ($Element['attributes'] as $name => $value) {
                    if (null === $value) {
                        continue;
                    }

                    $markup .= sprintf(' %s="', $name) . self::escape($value) . '"';
                }
            }
        }

        $permitRawHtml = false;

        if (isset($Element['text'])) {
            $text = $Element['text'];
        }
        // very strongly consider an alternative if you're writing an
        // extension
        elseif (isset($Element['rawHtml'])) {
            $text = $Element['rawHtml'];

            $allowRawHtmlInSafeMode = isset($Element['allowRawHtmlInSafeMode']) && $Element['allowRawHtmlInSafeMode'];
            $permitRawHtml = !$this->safeMode || $allowRawHtmlInSafeMode;
        }

        $hasContent = isset($text) || isset($Element['element']) || isset($Element['elements']);

        if ($hasContent) {
            $markup .= $hasName ? '>' : '';

            if (isset($Element['elements'])) {
                $markup .= $this->elements($Element['elements']);
            } elseif (isset($Element['element'])) {
                $markup .= $this->element($Element['element']);
            } elseif (!$permitRawHtml) {
                $markup .= self::escape($text, true);
            } else {
                $markup .= $text;
            }

            $markup .= $hasName ? '</' . $Element['name'] . '>' : '';
        } elseif ($hasName) {
            $markup .= ' />';
        }

        return $markup;
    }

    protected function elements(array $Elements): string
    {
        $markup = '';

        $autoBreak = true;

        foreach ($Elements as $Element) {
            if (empty($Element)) {
                continue;
            }

            $autoBreakNext = (
                $Element['autobreak'] ?? isset($Element['name'])
            );
            // (autobreak === false) covers both sides of an element
            $autoBreak = $autoBreak ? $autoBreakNext : $autoBreak;

            $markup .= ($autoBreak ? "\n" : '') . $this->element($Element);
            $autoBreak = $autoBreakNext;
        }

        return $markup . ($autoBreak ? "\n" : '');
    }

    // ~

    protected function li($lines)
    {
        $Elements = $this->linesElements($lines);

        if (!in_array('', $lines, true)
            && isset($Elements[0], $Elements[0]['name'])
            && 'p' === $Elements[0]['name']
        ) {
            unset($Elements[0]['name']);
        }

        return $Elements;
    }

    //
    // AST Convenience
    //

    /**
     * Replace occurrences $regexp with $Elements in $text. Return an array of
     * elements representing the replacement.
     *
     * @param mixed $regexp
     * @param mixed $Elements
     * @param mixed $text
     */
    protected static function pregReplaceElements($regexp, $Elements, $text)
    {
        $newElements = [];

        while (preg_match($regexp, $text, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($matches[0][0]));

            $newElements[] = ['text' => $before];

            foreach ($Elements as $Element) {
                $newElements[] = $Element;
            }

            $text = $after;
        }

        $newElements[] = ['text' => $text];

        return $newElements;
    }

    protected function sanitiseElement(array $Element)
    {
        static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
        static $safeUrlNameToAtt = [
            'a' => 'href',
            'img' => 'src',
        ];

        if (!isset($Element['name'])) {
            unset($Element['attributes']);

            return $Element;
        }

        if (isset($safeUrlNameToAtt[$Element['name']])) {
            $Element = $this->filterUnsafeUrlInAttribute($Element, $safeUrlNameToAtt[$Element['name']]);
        }

        if (!empty($Element['attributes'])) {
            foreach ($Element['attributes'] as $att => $val) {
                // filter out badly parsed attribute
                if (!preg_match($goodAttribute, $att)) {
                    unset($Element['attributes'][$att]);
                }
                // dump onevent attribute
                elseif (self::striAtStart($att, 'on')) {
                    unset($Element['attributes'][$att]);
                }
            }
        }

        return $Element;
    }

    protected function filterUnsafeUrlInAttribute(array $Element, $attribute)
    {
        foreach ($this->safeLinksWhitelist as $safeLinkWhitelist) {
            if (self::striAtStart($Element['attributes'][$attribute], $safeLinkWhitelist)) {
                return $Element;
            }
        }

        $Element['attributes'][$attribute] = str_replace(':', '%3A', $Element['attributes'][$attribute]);

        return $Element;
    }

    //
    // Static Methods
    //

    protected static function escape($text, $allowQuotes = false): string
    {
        return htmlspecialchars($text, $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
    }

    protected static function striAtStart($string, $needle): bool
    {
        $len = strlen($needle);

        if ($len > strlen($string)) {
            return false;
        }

        return strtolower(substr($string, 0, $len)) === strtolower($needle);
    }
}
