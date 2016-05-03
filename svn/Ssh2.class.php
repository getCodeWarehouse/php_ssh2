<?php
class Ssh2 
{
    protected $ip;
    protected $user;
    protected $psd;
    protected $port;
    protected $connection;
    protected $sftp;
    protected $prefixPath;
    protected $publicKey;
    protected $privateKey;
    protected $logPath;
    
    public function __construct( $ary = array() )
    {
        $this->ip           = isset($ary['ip']) ? $ary['ip'] : '113.107.188.123';
        $this->user         = isset($ary['user']) ? $ary['user'] : 'root';
        $this->psd          = isset($ary['psd']) ? $ary['psd'] : 'a1984wfx';
        $this->port         = isset($ary['port']) ? $ary['port'] : '22';
        $this->prefixPath   = isset($ary['prefixPath']) ? $ary['prefixPath'] : '/opt/dbtx/test_S1/html/Frt/';
        $this->publicKey    = isset($ary['publicKey']) ? $ary['publicKey'] : '';
        $this->privateKey   = isset($ary['privateKey']) ? $ary['privateKey'] : '';
        $this->logPath      = isset($ary['logPath']) ? $ary['logPath'] : '/tmp/';
        
        $this->connect();
        $this->checkKey();
        $this->sftp();
    }
    
    private function connect()
    {
        $this->connection = ssh2_connect($this->ip, $this->port, array('hostkey'=>'ssh-rsa'));
        
        if(!$this->connection) 
        {
            $this->error('Connect failed');
        }
    }
    
    private function checkKey()
    {
        $ret = ssh2_auth_pubkey_file($this->connection, $this->user,$this->publicKey,$this->privateKey,$this->psd);
        
        if (!$ret)
        {
            $this->error('Public Key Authentication Failed');
        }    
    }
    
    private function sftp()
    {
        $this->sftp = ssh2_sftp($this->connection);
        
        if(!$this->sftp)
        {
            $this->error('sftp failed');
        }
    }
    
    public function handle( $ary )
    {
        if(empty( $ary ) || !is_array( $ary ))
        {
            $this->error('svn co failed');
        }
        
        $str = array();
        $path = array('nums'=>0);
        
        foreach($ary as $v)
        {
            $tmp = str_replace(' ', '', str_replace(UPLOAD_FILE, '', $v));
            preg_match('/(\S?)(.*)/is', $tmp,$str);
            if(empty($str[0])) continue;
            $path['file'][UPLOAD_FILE . $str[2]][$str[1]] = $str[2];
            $path['nums'] ++;
        }
        
        return $this->updateFile( $path );
    }
    
    private function updateFile( $ary = array() )
    {
        if(empty($ary['file']))
        {
            $this->error('Path cant null');
        }
        
        $status = array(0);
        foreach($ary['file'] as $key => $val)
        {
            foreach($val as $k1 => $v1)
            {
                switch ($k1)
                {
                    case 'U':
                    case 'A':
                        if($this->sendFile($key, $v1))
                        {
                            $status['fileStr'][] = 'Send ' . $this->ip . ':' . $this->prefixPath . $v1;
                            $status[0] ++;
                        }
                        else 
                        {
                            $this->write("Send the file failed : {$this->prefixPath}{$v1}");
                        }
                        break;
                        
                    case 'D':
                        if($this->delFile($v1))
                        {
                            $status['fileStr'][] = 'Del ' . $this->ip . ':' . $this->prefixPath . $v1;
                            $status[0] ++;
                        }
                        else 
                        {
                            $this->write("Del the file failed : {$this->prefixPath}{$v1}");
                        }
                        break;
                        
                    default:
                        break;
                }
            }
        }
        
        $this->write(implode("\r\n", $status['fileStr']), 'operRecords');
        if($status[0] == $ary['nums'])
        {
            // $name=iconv("UTF-8","gb2312", '维护文档');
            $this->write(implode("\r\n", $status['fileStr']), 'Doc', 1);
        }
    }
    
    private function sendFile( $localFile, $remoteFile )
    {
        if(is_file( $localFile ))
        {
            return ssh2_scp_send($this->connection,$localFile ,$this->prefixPath . $remoteFile);
        }
        else 
        {
            return ssh2_sftp_mkdir($this->sftp,$this->prefixPath . $remoteFile);
        }
    }
    
    private function delFile( $remoteFile )
    {
        if(preg_match('\S+\.\S+', $remoteFile))
        {
            return ssh2_sftp_unlink($this->sftp,$this->prefixPath . $remoteFile);
        }
        else
        {
            return ssh2_exec($this->connection,'rm -rf ' . $this->prefixPath . $remoteFile);
        }
    }
    
    private function error( $error )
    {
        isset($error) ? $error : 'error';
        
        try 
        {
            throw new Exception( $error );
        }
        catch (Exception $e) 
        {
            $this->write( $e->getMessage() );
            exit;
        }
    }
    
    private function write( $msg, $file = '', $isDoc = 0,$isAdd = 1)
    {
        $time = date( 'Ymd', time());
        $nowTime = date( 'Y-m-d H:i:s', time());
        
        if( empty($file) )
        {
            $file = $this->logPath . 'ssh2_error_' . $time . '.log';
        }
        else
        {
            $file = $this->logPath . $file . '_' . $time . '.log';
        }
        
        if ( 1 == $isAdd )
        {
            if( 1 == $isDoc )
            {
                return file_put_contents( $file,$msg . PHP_EOL, FILE_APPEND );
            }
            else 
            {
                return file_put_contents( $file,"[{$nowTime}] : {$msg}" . PHP_EOL, FILE_APPEND );
            }
        }
        else
        {
            if( 1 == $isDoc )
            {
                return file_put_contents( $file,$msg . PHP_EOL);
            }
            else 
            {
                return file_put_contents( $file,"[{$nowTime}] : {$msg}" . PHP_EOL );
            }
        }
    }
}