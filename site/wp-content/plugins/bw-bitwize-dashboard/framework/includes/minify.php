<?php
class BW_JSMin
{
    const ORD_LF = 10;
    const ORD_SPACE = 32;
    const ACTION_KEEP_A = 1;
    const ACTION_DELETE_A = 2;
    const ACTION_DELETE_A_B = 3;

    protected $a = "\n";
    protected $b = '';
    protected $input = '';
    protected $inputIndex = 0;
    protected $inputLength = 0;
    protected $lookAhead = null;
    protected $output = '';
    protected $lastByteOut = '';
    protected $keptComment = '';

    public static function minify($js) {
        $jsmin = new BW_JSMin($js);
        return $jsmin->min();
    }

    public function __construct($input) {
        $this->input = $input;
    }

    public function min() {
        if ($this->output !== '') {
            return $this->output;
        }

        $mbIntEnc = null;
        if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
            $mbIntEnc = mb_internal_encoding();
            mb_internal_encoding('8bit');
        }
        $this->input = str_replace("\r\n", "\n", $this->input);
        $this->inputLength = strlen($this->input);

        $this->action(self::ACTION_DELETE_A_B);

        while ($this->a !== null) {
            $command = self::ACTION_KEEP_A;
            if ($this->a === ' ') {
                if (($this->lastByteOut === '+' || $this->lastByteOut === '-') && ($this->b === $this->lastByteOut)) {
                }
                elseif (!$this->isAlphaNum($this->b)) {
                    $command = self::ACTION_DELETE_A;
                }
            }
            elseif ($this->a === "\n") {
                if ($this->b === ' ') {
                    $command = self::ACTION_DELETE_A_B;
                }
                elseif ($this->b === null || (false === strpos('{[(+-!~', $this->b) && !$this->isAlphaNum($this->b))) {
                    $command = self::ACTION_DELETE_A;
                }
            }
            elseif (!$this->isAlphaNum($this->a)) {
                if ($this->b === ' ' || ($this->b === "\n" && (false === strpos('}])+-"\'', $this->a)))) {
                    $command = self::ACTION_DELETE_A_B;
                }
            }
            $this->action($command);
        }
        $this->output = trim($this->output);

        if ($mbIntEnc !== null) {
            mb_internal_encoding($mbIntEnc);
        }
        return $this->output;
    }

    protected function action($command) {
        if ($command === self::ACTION_DELETE_A_B && $this->b === ' ' && ($this->a === '+' || $this->a === '-')) {
            if ($this->input[$this->inputIndex] === $this->a) {
                $command = self::ACTION_KEEP_A;
            }
        }

        switch ($command) {
            case self::ACTION_KEEP_A:
                $this->output.= $this->a;

                if ($this->keptComment) {
                    $this->output = rtrim($this->output, "\n");
                    $this->output.= $this->keptComment;
                    $this->keptComment = '';
                }

                $this->lastByteOut = $this->a;
            case self::ACTION_DELETE_A:
                $this->a = $this->b;
                if ($this->a === "'" || $this->a === '"') {
                    $str = $this->a;
                    for (;;) {
                        $this->output.= $this->a;
                        $this->lastByteOut = $this->a;

                        $this->a = $this->get();
                        if ($this->a === $this->b) {
                            break;
                        }
                        if ($this->isEOF($this->a)) {
                            throw new BW_JSMin_UnterminatedStringException("BW_JSMin: Unterminated String at byte {$this->inputIndex}: {$str}");
                        }
                        $str.= $this->a;
                        if ($this->a === '\\') {
                            $this->output.= $this->a;
                            $this->lastByteOut = $this->a;

                            $this->a = $this->get();
                            $str.= $this->a;
                        }
                    }
                }
            case self::ACTION_DELETE_A_B:
                $this->b = $this->next();
                if ($this->b === '/' && $this->isRegexpLiteral()) {
                    $this->output.= $this->a . $this->b;
                    $pattern = '/';
                    for (;;) {
                        $this->a = $this->get();
                        $pattern.= $this->a;
                        if ($this->a === '[') {
                            for (;;) {
                                $this->output.= $this->a;
                                $this->a = $this->get();
                                $pattern.= $this->a;
                                if ($this->a === ']') {
                                    break;
                                }
                                if ($this->a === '\\') {
                                    $this->output.= $this->a;
                                    $this->a = $this->get();
                                    $pattern.= $this->a;
                                }
                                if ($this->isEOF($this->a)) {
                                    throw new BW_JSMin_UnterminatedRegExpException("BW_JSMin: Unterminated set in RegExp at byte " . $this->inputIndex . ": {$pattern}");
                                }
                            }
                        }

                        if ($this->a === '/') {
                            break;
                        }
                        elseif ($this->a === '\\') {
                            $this->output.= $this->a;
                            $this->a = $this->get();
                            $pattern.= $this->a;
                        }
                        elseif ($this->isEOF($this->a)) {
                            throw new BW_JSMin_UnterminatedRegExpException("BW_JSMin: Unterminated RegExp at byte {$this->inputIndex}: {$pattern}");
                        }
                        $this->output.= $this->a;
                        $this->lastByteOut = $this->a;
                    }
                    $this->b = $this->next();
                }
        }
    }

    protected function isRegexpLiteral() {
        if (false !== strpos("(,=:[!&|?+-~*{;", $this->a)) {
            return true;
        }
        if ($this->a === ' ' || $this->a === "\n") {
            $length = strlen($this->output);
            if ($length < 2) {
                return true;
            }
            if (preg_match('/(?:case|else|in|return|typeof)$/', $this->output, $m)) {
                if ($this->output === $m[0]) {
                    return true;
                }
                $charBeforeKeyword = substr($this->output, $length - strlen($m[0]) - 1, 1);
                if (!$this->isAlphaNum($charBeforeKeyword)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function get() {
        $c = $this->lookAhead;
        $this->lookAhead = null;
        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex+= 1;
            }
            else {
                $c = null;
            }
        }
        if (ord($c) >= self::ORD_SPACE || $c === "\n" || $c === null) {
            return $c;
        }
        if ($c === "\r") {
            return "\n";
        }
        return ' ';
    }

    protected function isEOF($a) {
        return ord($a) <= self::ORD_LF;
    }

    protected function peek() {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

    protected function isAlphaNum($c) {
        return (preg_match('/^[a-z0-9A-Z_\\$\\\\]$/', $c) || ord($c) > 126);
    }

    protected function consumeSingleLineComment() {
        $comment = '';
        while (true) {
            $get = $this->get();
            $comment.= $get;
            if (ord($get) <= self::ORD_LF) {
                if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                    $this->keptComment.= "/{$comment}";
                }
                return;
            }
        }
    }

    protected function consumeMultipleLineComment() {
        $this->get();
        $comment = '';
        for (;;) {
            $get = $this->get();
            if ($get === '*') {
                if ($this->peek() === '/') {
                    $this->get();
                    if (0 === strpos($comment, '!')) {
                        if (!$this->keptComment) {
                            $this->keptComment = "\n";
                        }
                        $this->keptComment.= "/*!" . substr($comment, 1) . "*/\n";
                    }
                    else if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                        $this->keptComment.= "/*{$comment}*/";
                    }
                    return;
                }
            }
            elseif ($get === null) {
                throw new BW_JSMin_UnterminatedCommentException("BW_JSMin: Unterminated comment at byte {$this->inputIndex}: /*{$comment}");
            }
            $comment.= $get;
        }
    }

    protected function next() {
        $get = $this->get();
        if ($get === '/') {
            switch ($this->peek()) {
                case '/':
                    $this->consumeSingleLineComment();
                    $get = "\n";
                    break;

                case '*':
                    $this->consumeMultipleLineComment();
                    $get = ' ';
                    break;
            }
        }
        return $get;
    }
}

class BW_JSMin_UnterminatedStringException extends Exception{}
class BW_JSMin_UnterminatedCommentException extends Exception{}
class BW_JSMin_UnterminatedRegExpException extends Exception{}

class BW_Minify_CSS_Compressor
{
    public static function process($css, $options = array()) {
        $obj = new BW_Minify_CSS_Compressor($options);
        return $obj->_process($css);
    }

    protected $_options = null;

    protected $_inHack = false;

    private function __construct($options) {
        $this->_options = $options;
    }

    protected function _process($css) {
        $css = str_replace("\r\n", "\n", $css);

        $css = preg_replace('@>/\\*\\s*\\*/@', '>/*keep*/', $css);

        $css = preg_replace('@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $css);
        $css = preg_replace('@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $css);

        $css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@', array($this, '_commentCB'), $css);

        $css = preg_replace('/\\s*{\\s*/', '{', $css);
        $css = preg_replace('/;?\\s*}\\s*/', '}', $css);

        $css = preg_replace('/\\s*;\\s*/', ';', $css);

        $css = preg_replace('/
                url\\(      # url(
                \\s*
                ([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
                \\s*
                \\)         # )
            /x', 'url($1)', $css);

        $css = preg_replace('/
                \\s*
                ([{;])              # 1 = beginning of block or rule separator
                \\s*
                ([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
                \\s*
                :
                \\s*
                (\\b|[#\'"-])        # 3 = first character of a value
            /x', '$1$2:$3', $css);

        $css = preg_replace_callback('/
                (?:              # non-capture
                    \\s*
                    [^~>+,\\s]+  # selector part
                    \\s*
                    [,>+~]       # combinators
                )+
                \\s*
                [^~>+,\\s]+      # selector part
                {                # open declaration block
            /x', array($this, '_selectorsCB'), $css);

        $css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $css);

        $css = preg_replace_callback('/font-family:([^;}]+)([;}])/', array($this, '_fontFamilyCB'), $css);

        $css = preg_replace('/@import\\s+url/', '@import url', $css);

        $css = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $css);

        $css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/', "$1\n$2{", $css);

        $css = preg_replace('/
            ((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
            \\s+
            /x', "$1\n", $css);

        $css = preg_replace('/:first-l(etter|ine)\\{/', ':first-l$1 {', $css);

        return trim($css);
    }

    protected function _selectorsCB($m) {
        return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
    }

    protected function _commentCB($m) {
        $hasSurroundingWs = (trim($m[0]) !== $m[1]);
        $m = $m[1];
        if ($m === 'keep') {
            return '/**/';
        }
        if ($m === '" "') {
            return '/*" "*/';
        }
        if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $m)) {
            return '/*";}}/* */';
        }
        if ($this->_inHack) {
            if (preg_match('@
                    ^/               # comment started like /*/
                    \\s*
                    (\\S[\\s\\S]+?)  # has at least some non-ws content
                    \\s*
                    /\\*             # ends like /*/ or /**/
                @x', $m, $n)) {
                $this->_inHack = false;
                return "/*/{$n[1]}/**/";
            }
        }
        if (substr($m, -1) === '\\') {
            $this->_inHack = true;
            return '/*\\*/';
        }
        if ($m !== '' && $m[0] === '/') {
            $this->_inHack = true;
            return '/*/*/';
        }
        if ($this->_inHack) {
            $this->_inHack = false;
            return '/**/';
        }
        return $hasSurroundingWs ? ' ' : '';
    }

    protected function _fontFamilyCB($m) {
        $pieces = preg_split('/(\'[^\']+\'|"[^"]+")/', $m[1], null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = 'font-family:';
        while (null !== ($piece = array_shift($pieces))) {
            if ($piece[0] !== '"' && $piece[0] !== "'") {
                $piece = preg_replace('/\\s+/', ' ', $piece);
                $piece = preg_replace('/\\s?,\\s?/', ',', $piece);
            }
            $out.= $piece;
        }
        return $out . $m[2];
    }
}

class BW_Minify_HTML
{
    protected $_jsCleanComments = true;

    public static function minify($html, $options = array()) {
        $min = new self($html, $options);
        return $min->process();
    }

    public function __construct($html, $options = array()) {
        $this->_html = str_replace("\r\n", "\n", trim($html));
        if (isset($options['xhtml'])) {
            $this->_isXhtml = (bool)$options['xhtml'];
        }
        if (isset($options['cssMinifier'])) {
            $this->_cssMinifier = $options['cssMinifier'];
        }
        if (isset($options['jsMinifier'])) {
            $this->_jsMinifier = $options['jsMinifier'];
        }
        if (isset($options['jsCleanComments'])) {
            $this->_jsCleanComments = (bool)$options['jsCleanComments'];
        }
    }

    public function process() {
        if ($this->_isXhtml === null) {
            $this->_isXhtml = (false !== strpos($this->_html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        }

        $this->_replacementHash = 'MINIFYHTML' . md5($_SERVER['REQUEST_TIME']);
        $this->_placeholders = array();

        $this->_html = preg_replace_callback('/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i', array($this, '_removeScriptCB'), $this->_html);

        $this->_html = preg_replace_callback('/\\s*<style(\\b[^>]*>)([\\s\\S]*?)<\\/style>\\s*/i', array($this, '_removeStyleCB'), $this->_html);

        $this->_html = preg_replace_callback('/<!--([\\s\\S]*?)-->/', array($this, '_commentCB'), $this->_html);

        $this->_html = preg_replace_callback('/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i', array($this, '_removePreCB'), $this->_html);

        $this->_html = preg_replace_callback('/\\s*<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i', array($this, '_removeTextareaCB'), $this->_html);

        $this->_html = preg_replace('/^\\s+|\\s+$/m', '', $this->_html);

        $this->_html = preg_replace('/\\s+(<\\/?(?:area|base(?:font)?|blockquote|body' . '|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form' . '|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta' . '|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)' . '|ul)\\b[^>]*>)/i', '$1', $this->_html);

        $this->_html = preg_replace('/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</', '>$1$2$3<', $this->_html);

        $this->_html = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i', "$1 $2", $this->_html);

        $this->_html = str_replace(array_keys($this->_placeholders), array_values($this->_placeholders), $this->_html);
        $this->_html = str_replace(array_keys($this->_placeholders), array_values($this->_placeholders), $this->_html);
        return $this->_html;
    }

    protected function _commentCB($m) {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<![')) ? $m[0] : '';
    }

    protected function _reservePlace($content) {
        $placeholder = '%' . $this->_replacementHash . count($this->_placeholders) . '%';
        $this->_placeholders[$placeholder] = $content;
        return $placeholder;
    }

    protected $_isXhtml = null;
    protected $_replacementHash = null;
    protected $_placeholders = array();
    protected $_cssMinifier = null;
    protected $_jsMinifier = null;

    protected function _removePreCB($m) {
        return $this->_reservePlace("<pre{$m[1]}");
    }

    protected function _removeTextareaCB($m) {
        return $this->_reservePlace("<textarea{$m[1]}");
    }

    protected function _removeStyleCB($m) {
        $openStyle = "<style{$m[1]}";
        $css = $m[2];
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);

        $css = $this->_removeCdata($css);

        $minifier = $this->_cssMinifier ? $this->_cssMinifier : 'trim';
        $css = call_user_func($minifier, $css);

        return $this->_reservePlace($this->_needsCdata($css) ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>" : "{$openStyle}{$css}</style>");
    }

    protected function _removeScriptCB($m) {
        $openScript = "<script{$m[2]}";
        $js = $m[3];

        $ws1 = ($m[1] === '') ? '' : ' ';
        $ws2 = ($m[4] === '') ? '' : ' ';

        if ($this->_jsCleanComments) {
            $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);
        }

        $js = $this->_removeCdata($js);

        $minifier = $this->_jsMinifier ? $this->_jsMinifier : 'trim';
        $js = call_user_func($minifier, $js);

        return $this->_reservePlace($this->_needsCdata($js) ? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}" : "{$ws1}{$openScript}{$js}</script>{$ws2}");
    }

    protected function _removeCdata($str) {
        return (false !== strpos($str, '<![CDATA[')) ? str_replace(array('<![CDATA[', ']]>'), '', $str) : $str;
    }

    protected function _needsCdata($str) {
        return ($this->_isXhtml && preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
}

class BW_Minify_Output
{
    public function __construct(){
        if (!is_admin()) {
            add_action('init', function(){
                if (defined('DOING_AJAX') && DOING_AJAX){
                    // do nothing
                }else{
                    ob_start(array($this, 'minify_html'));
                }
            });
        }
    }

    public function minify_html($buffer) {
        $html_options = array();
        $html_options['cssMinifier'] = array($this, 'minify_inline_css');
        $html_options['jsMinifier'] = array($this, 'minify_inline_js');
        $html_options = apply_filters('bw_minify_html_options', $html_options);
        $buffer = BW_Minify_HTML::minify($buffer, $html_options);
        return $buffer;
    }


    public function minify_inline_css($css) {
        return BW_Minify_CSS_Compressor::process($css);
    }

    public function minify_inline_js($js) {
        return BW_JSMin::minify($js);
    }
}

new BW_Minify_Output;
