<?php
/*
// http://wkhtmltopdf.org/usage/wkhtmltopdf.txt

$bin = '/usr/local/bin/';

$pdf = new wkhtmltopdf(array(
    'binary' => $bin.'wkhtmltopdf',
    'no-outline',
    'encoding' => 'UTF-8',
    'margin-top'    => 0,
    'margin-right'  => 0,
    'margin-bottom' => 0,
    'margin-left'   => 0,
    'disable-smart-shrinking',
));

$pdf->addPage('/path/to/page.html');
$pdf->addPage('<html>....</html>');
$pdf->addPage('http://www.example.com');

// Add a cover (same sources as above are possible)
$pdf->addCover('/path/to/mycover.html');

// Add a Table of contents
$pdf->addToc();

// Save the PDF
$pdf->saveAs('/path/to/report.pdf');

// ... or send to client for inline display
$pdf->send();

// ... or send to client as file download
$pdf->send('report.pdf');

// ... or you can get the raw pdf as a string
$content = $pdf->toString();

// if (!$pdf->send()) {
//     echo 'Could not create PDF: '.$pdf->getError();
// }
*/

class wkhtmltopdf_BaseCommand {
    public $escapeArgs = true;
    public $escapeCommand = false;
    public $useExec = false;
    public $captureStdErr = true;
    public $procCwd;
    public $procEnv;
    public $procOptions;
    public $locale;
    protected $_command;
    protected $_args = array();
    protected $_execCommand;
    protected $_stdOut = '';
    protected $_stdErr = '';
    protected $_exitCode;
    protected $_error = '';
    protected $_executed = false;

    public function __construct($options = null){
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->setCommand($options);
        }
    }

    public function setOptions($options){
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                $method = 'set' . ucfirst($key);
                if (method_exists($this, $method)) {
                    call_user_func(array($this, $method), $value);
                } else {
                    throw new \Exception("Unknown configuration option '$key'");
                }
            }
        }
        return $this;
    }

    public function setCommand($command){
        if ($this->escapeCommand) {
            $command = escapeshellcmd($command);
        }
        if ($this->getIsWindows()) {
            $chdrive = (isset($command[1]) && $command[1] === ':') ? $command[0] . ': && ' : '';
            $command = sprintf($chdrive . 'cd %s && %s', escapeshellarg(dirname($command)), basename($command));
        }
        $this->_command = $command;
        return $this;
    }

    public function getCommand(){
        return $this->_command;
    }

    public function getExecCommand(){
        if ($this->_execCommand === null) {
            $command = $this->getCommand();
            if (!$command) {
                $this->_error = 'Could not locate any executable command';
                return false;
            }
            $args               = $this->getArgs();
            $this->_execCommand = $args ? $command . ' ' . $args : $command;
        }
        return $this->_execCommand;
    }

    public function setArgs($args){
        $this->_args = array($args);
        return $this;
    }

    public function getArgs(){
        return implode(' ', $this->_args);
    }

    public function addArg($key, $value = null, $escape = null){
        $doEscape  = $escape !== null ? $escape : $this->escapeArgs;
        $useLocale = $doEscape && $this->locale !== null;

        if ($useLocale) {
            $locale = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, $this->locale);
        }
        if ($value === null) {
            $this->_args[] = $escape ? escapeshellarg($key) : $key;
        } else {
            $separator = substr($key, -1) === '=' ? '' : ' ';
            if (is_array($value)) {
                $params = array();
                foreach ($value as $v) {
                    $params[] = $doEscape ? escapeshellarg($v) : $v;
                }
                $this->_args[] = $key . $separator . implode(' ', $params);
            } else {
                $this->_args[] = $key . $separator . ($doEscape ? escapeshellarg($value) : $value);
            }
        }
        if ($useLocale) {
            setlocale(LC_CTYPE, $locale);
        }

        return $this;
    }

    public function getOutput($trim = true){
        return $trim ? trim($this->_stdOut) : $this->_stdOut;
    }

    public function getError($trim = true){
        return $trim ? trim($this->_error) : $this->_error;
    }

    public function getStdErr($trim = true){
        return $trim ? trim($this->_stdErr) : $this->_stdErr;
    }

    public function getExitCode(){
        return $this->_exitCode;
    }

    public function getExecuted(){
        return $this->_executed;
    }

    public function execute(){
        $command = $this->getExecCommand();

        if (!$command) {
            return false;
        }

        if ($this->useExec) {
            $execCommand = $this->captureStdErr ? "$command 2>&1" : $command;
            exec($execCommand, $output, $this->_exitCode);
            $this->_stdOut = implode("\n", $output);
            if ($this->_exitCode !== 0) {
                $this->_stdErr = $this->_stdOut;
                $this->_error  = empty($this->_stdErr) ? 'Command failed' : $this->_stdErr;
                return false;
            }
        } else {
            $descriptors = array(
                1 => array('pipe', 'w'),
                2 => array('pipe', $this->getIsWindows() ? 'a' : 'w'),
            );
            $process = proc_open($command, $descriptors, $pipes, $this->procCwd, $this->procEnv, $this->procOptions);

            if (is_resource($process)) {

                $this->_stdErr = stream_get_contents($pipes[2]);
                $this->_stdOut = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                $this->_exitCode = proc_close($process);

                if ($this->_exitCode !== 0) {
                    $this->_error = $this->_stdErr ? $this->_stdErr : "Failed without error message: $command";
                    return false;
                }
            } else {
                $this->_error = "Could not run command $command";
                return false;
            }
        }
        $this->_executed = true;
        return true;
    }

    public function getIsWindows(){
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }

    public function __toString(){
        return (string) $this->getExecCommand();
    }
}

class wkhtmltopdf_Command extends wkhtmltopdf_BaseCommand {
    public $enableXvfb = false;
    public $xvfbRunBinary = 'xvfb-run';
    public $xvfbRunOptions = '-a --server-args="-screen 0, 1024x768x24"';

    public function addArgs($args){
        if (isset($args['input'])) {
            $this->addArg((string) $args['input']);
            unset($args['input']);
        }
        if (isset($args['inputArg'])) {
            $this->addArg((string) $args['inputArg'], null, true);
            unset($args['inputArg']);
        }
        foreach ($args as $key => $val) {
            if (is_numeric($key)) {
                $this->addArg("--$val");
            } elseif (is_array($val)) {
                foreach ($val as $vkey => $vval) {
                    if (is_int($vkey)) {
                        $this->addArg("--$key", $vval);
                    } else {
                        $this->addArg("--$key", array($vkey, $vval));
                    }
                }
            } else {
                $this->addArg("--$key", $val);
            }
        }
    }

    public function getExecCommand(){
        $command = parent::getExecCommand();
        if ($this->enableXvfb) {
            return $this->xvfbRunBinary . ' ' . $this->xvfbRunOptions . ' ' . $command;
        }
        return $command;
    }
}

class wkhtmltopdf_File{
    public $delete = true;
    protected $_fileName;

    public function __construct($content, $suffix = null, $prefix = null, $directory = null){
        if ($directory === null) {
            $directory = self::getTempDir();
        }
        if ($prefix === null) {
            $prefix = 'php_tmpfile_';
        }
        $this->_fileName = tempnam($directory, $prefix);
        if ($suffix !== null) {
            $newName = $this->_fileName . $suffix;
            rename($this->_fileName, $newName);
            $this->_fileName = $newName;
        }
        file_put_contents($this->_fileName, $content);
    }

    public function __destruct(){
        if ($this->delete) {
            unlink($this->_fileName);
        }
    }

    public function send($name = null, $contentType, $inline = false){
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: ' . $contentType);
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($this->_fileName));

        if ($name !== null || $inline) {
            $disposition = $inline ? 'inline' : 'attachment';
            header("Content-Disposition: $disposition; filename=\"$name\"");
        }

        readfile($this->_fileName);
    }

    public function saveAs($name){
        return copy($this->_fileName, $name);
    }

    public function getFileName(){
        return $this->_fileName;
    }

    public static function getTempDir(){
        if (function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();
        } elseif (($tmp = getenv('TMP')) || ($tmp = getenv('TEMP')) || ($tmp = getenv('TMPDIR'))) {
            return realpath($tmp);
        } else {
            return '/tmp';
        }
    }

    public function __toString(){
        return $this->_fileName;
    }
}

class wkhtmltopdf {
    const TYPE_HTML = 'html';
    const TYPE_XML  = 'xml';
    const REGEX_HTML = '/<(?:!doctype )?html/i';
    const REGEX_XML = '/<\??xml/i';
    const TMP_PREFIX = 'tmp_wkhtmlto_pdf_';
    public $binary = 'wkhtmltopdf';
    public $commandOptions = array();
    public $tmpDir;
    public $ignoreWarnings = false;
    public $version9 = false;
    protected $_isCreated = false;
    protected $_options = array();
    protected $_objects = array();
    protected $_tmpPdfFile;
    protected $_tmpFiles = array();
    protected $_command;
    protected $_error = '';

    public function __construct($options = null){
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->addPage($options);
        }
    }

    public function addPage($input, $options = array(), $type = null){
        $options             = $this->processOptions($options);
        $options['inputArg'] = $this->processInput($input, $type);
        $this->_objects[]    = $options;
        return $this;
    }

    public function addCover($input, $options = array(), $type = null){
        $options['input']    = ($this->version9 ? '--' : '') . 'cover';
        $options['inputArg'] = $this->processInput($input, $type);
        $this->_objects[]    = $options;
        return $this;
    }

    public function addToc($options = array()){
        $options['input'] = ($this->version9 ? '--' : '') . "toc";
        $this->_objects[] = $options;
        return $this;
    }

    public function saveAs($filename){
        if (!$this->_isCreated && !$this->createPdf()) {
            return false;
        }
        if (!$this->_tmpPdfFile->saveAs($filename)) {
            $this->_error = "Could not save PDF as '$filename'";
            return false;
        }
        return true;
    }

    public function send($filename = null, $inline = false){
        if (!$this->_isCreated && !$this->createPdf()) {
            return false;
        }
        $this->_tmpPdfFile->send($filename, 'application/pdf', $inline);
        return true;
    }

    public function toString(){
        if (!$this->_isCreated && !$this->createPdf()) {
            return false;
        }
        return file_get_contents($this->_tmpPdfFile->getFileName());
    }

    public function setOptions($options = array()){
        $options = $this->processOptions($options);
        foreach ($options as $key => $val) {
            if (is_int($key)) {
                $this->_options[] = $val;
            } elseif ($key[0] !== '_' && property_exists($this, $key)) {
                $this->$key = $val;
            } else {
                $this->_options[$key] = $val;
            }
        }
        return $this;
    }

    public function getCommand(){
        if ($this->_command === null) {
            $options = $this->commandOptions;
            if (!isset($options['command'])) {
                $options['command'] = $this->binary;
            }
            $this->_command = new wkhtmltopdf_Command($options);
        }
        return $this->_command;
    }

    public function getError(){
        return $this->_error;
    }

    public function getPdfFilename(){
        if ($this->_tmpPdfFile === null) {
            $this->_tmpPdfFile = new wkhtmltopdf_File('', '.pdf', self::TMP_PREFIX, $this->tmpDir);
        }
        return $this->_tmpPdfFile->getFileName();
    }

    protected function createPdf(){
        if ($this->_isCreated) {
            return false;
        }
        $command  = $this->getCommand();
        $fileName = $this->getPdfFilename();

        $command->addArgs($this->_options);
        foreach ($this->_objects as $object) {
            $command->addArgs($object);
        }
        $command->addArg($fileName, null, true);if (!$command->execute()) {
            $this->_error = $command->getError();
            if (!(file_exists($fileName) && filesize($fileName) !== 0 && $this->ignoreWarnings)) {
                return false;
            }
        }
        $this->_isCreated = true;
        return true;
    }

    protected function processInput($input, $type = null){
        if ($type === self::TYPE_HTML || $type === null && preg_match(self::REGEX_HTML, $input)) {
            return $this->_tmpFiles[] = new wkhtmltopdf_File($input, '.html', self::TMP_PREFIX, $this->tmpDir);
        } elseif ($type === self::TYPE_XML || preg_match(self::REGEX_XML, $input)) {
            return $this->_tmpFiles[] = new wkhtmltopdf_File($input, '.xml', self::TMP_PREFIX, $this->tmpDir);
        } else {
            return $input;
        }
    }

    protected function processOptions($options = array()){
        foreach ($options as $key => $val) {
            if (is_string($val) && preg_match('/^(header|footer)-html$/', $key)) {
                defined('PHP_MAXPATHLEN') || define('PHP_MAXPATHLEN', 255);
                $isFile = (strlen($val) <= PHP_MAXPATHLEN) ? is_file($val) : false;
                if (!($isFile || preg_match('/^(https?:)?\/\//i', $val) || $val === strip_tags($val))) {
                    $options[$key] = new wkhtmltopdf_File($val, '.html', self::TMP_PREFIX, $this->tmpDir);
                }
            }
        }
        return $options;
    }
}
