<?php
/*
    function __ftp($files=''){
        $error = array();
        $ftp_server = 'IPADDRESS';
        $user_name = 'USER';
        $user_pass = 'PASS';
        $port = 21;
        $timeout = 180; //3 minutes
        $passv = true;

        $initial_path = '/FOLDER';

        $ftp = new App_FTP($ftp_server, $user_name, $user_pass, $port, $timeout);
        $ftp->passive = $passv;
        $ftp->ssl = false;

        if(!$ftp->connect()) $error[] = $ftp->error;

        if($initial_path){
            if(!$ftp->cd($initial_path)) $error[] = $ftp->error;
        }
        if(!empty($files)) {
            if(is_array($files)){
                foreach($files as $file){
                    if(!$ftp->put($file, basename($file))) $error[] = $ftp->error;
                }
            } else {
                if(!$ftp->put($files, basename($files))) $error[] = $ftp->error;
            }
        }
        if($error){
            return array(
                'success' => false,
                'message' => implode('<br>', $error),
            );
        } else {
            return array(
                'success' => true,
                'message' => 'Files Transfered',
            );
        }
    }
*/
final class App_FTP{

    private $_host;
    private $_port = 21;
    private $_pwd;
    private $_stream;
    private $_timeout = 90;
    private $_user;
    public $error;
    public $passive = false;
    public $ssl = false;
    public $system_type;

    public function __construct($host = null, $user = null, $password = null, $port = 21, $timeout = 90){
        $this->_host    = $host;
        $this->_user    = $user;
        $this->_pwd     = $password;
        $this->_port    = (int) $port;
        $this->_timeout = (int) $timeout;
    }

    public function __destruct(){
        $this->close();
    }

    public function cd($directory = null){
        if (ftp_chdir($this->_stream, $directory)) {
            return true;
        } else {
            $this->error = "Failed to change directory to \"{$directory}\"";
            return false;
        }
    }

    public function chmod($permissions = 0, $remote_file = null){
        if (ftp_chmod($this->_stream, $permissions, $remote_file)) {
            return true;
        } else {
            $this->error = "Failed to set file permissions for \"{$remote_file}\"";
            return false;
        }
    }

    public function close(){
        if ($this->_stream) {
            ftp_close($this->_stream);
            $this->_stream = false;
        }
    }

    public function connect(){
        if (!$this->ssl) {
            if (!$this->_stream = ftp_connect($this->_host, $this->_port, $this->_timeout)) {
                $this->error = "Failed to connect to {$this->_host}";
                return false;
            }
        } elseif (function_exists("ftp_ssl_connect")) {
            if (!$this->_stream = ftp_ssl_connect($this->_host, $this->_port, $this->_timeout)) {
                $this->error = "Failed to connect to {$this->_host} (SSL connection)";
                return false;
            }
        } else {
            $this->error = "Failed to connect to {$this->_host} (invalid connection type)";
            return false;
        }

        if (ftp_login($this->_stream, $this->_user, $this->_pwd)) {
            ftp_pasv($this->_stream, (bool) $this->passive);

            $this->system_type = ftp_systype($this->_stream);

            return true;
        } else {
            $this->error = "Failed to connect to {$this->_host} (login failed)";
            return false;
        }
    }

    public function delete($remote_file = null){
        if (ftp_delete($this->_stream, $remote_file)) {
            return true;
        } else {
            $this->error = "Failed to delete file \"{$remote_file}\"";
            return false;
        }
    }

    public function get($remote_file = null, $local_file = null, $mode = FTP_ASCII){
        if (ftp_get($this->_stream, $local_file, $remote_file, $mode)) {
            return true;
        } else {
            $this->error = "Failed to download file \"{$remote_file}\"";
            return false;
        }
    }

    public function ls($directory = null){
        $list = array();
        if ($list = ftp_nlist($this->_stream, $directory)) {
            return $list;
        } else {
            $this->error = "Failed to get directory list";
            return array();
        }
    }

    public function mkdir($directory = null){
        if (ftp_mkdir($this->_stream, $directory)) {
            return true;
        } else {
            $this->error = "Failed to create directory \"{$directory}\"";
            return false;
        }
    }

    public function put($local_file = null, $remote_file = null, $mode = FTP_ASCII){
        if (ftp_put($this->_stream, $remote_file, $local_file, $mode)) {
            return true;
        } else {
            $this->error = "Failed to upload file \"{$local_file}\"";
            return false;
        }
    }

    public function pwd(){
        return ftp_pwd($this->_stream);
    }

    public function rename($old_name = null, $new_name = null){
        if (ftp_rename($this->_stream, $old_name, $new_name)) {
            return true;
        } else {
            $this->error = "Failed to rename file \"{$old_name}\"";
            return false;
        }
    }

    public function rmdir($directory = null){
        if (ftp_rmdir($this->_stream, $directory)) {
            return true;
        } else {
            $this->error = "Failed to remove directory \"{$directory}\"";
            return false;
        }
    }

    public function filesize($file = null) {
        if($res = ftp_size($this->_stream, $file)) {
            return $res;
        } else {
            $this->error = "Failed to get filesize \"{$file}\"";
            return false;
        }
    }

    public function isdir($directory = null){
        $actual_directory = $this->pwd();
        $result = $this->cd($directory);
        $this->cd($actual_directory);
        return $result;
    }

    public function deltree($path = null){
        if($this->isdir($path)){
            $content = $this->ls($path.'/');
            foreach ($content as $file)
                $this->deltree($path.$file);

            try{
                $this->rmdir($path);
            }
            catch (Exception $e) {}
        } else {
            try{
                $this->delete($path);
            }
            catch (Exception $e) {}
        }
    }

    public function fileexist($path){
        return ($this->isdir($path)) ? true : array_key_exists(0, $this->ls($path));
    }
}
