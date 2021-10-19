<?php

namespace App\HttpController\Service\SFTP;

use App\HttpController\Service\ServiceBase;

class SFTPService extends ServiceBase
{
    private $conn;//ssh2连接资源
    private $sftp;//sftp连接资源

    public function connect($config)
    {
        if ($config['useKey']) {
            $this->conn = ssh2_connect($config['host'], $config['port'], ['hostkey' => 'ssh-rsa']);
            $rs = ssh2_auth_pubkey_file($this->conn, $config['username'], $config['public_key'], $config['private_key'], $config['passphrase']);
        } else {
            $this->conn = ssh2_connect($config['host'], $config['port']);
            $rs = ssh2_auth_password($this->conn, $config['username'], $config['password']);
        }
        $this->sftp = ssh2_sftp($this->conn);

        return $rs;
    }

    public function disconnect()
    {
        try {
            @ssh2_disconnect($this->sftp);
        } catch (\Throwable $e) {
            $this->conn = null;
            $this->sftp = null;
        }
        return true;
    }

    public function download($local, $remote)
    {
        //local未指定文件，则使用原文件名
        if (is_dir($local)) {
            $temp = explode('/', $remote);
            $local = $local . end($temp);
        }
        $handle = fopen("ssh2.sftp://" . intval($this->sftp) . $remote, 'rb');
        $output = fopen($local, 'wb');
        if (!$handle || !$output) {
            return false;
        }
        while (!feof($handle)) {
            fwrite($output, fread($handle, 2048));
        }

        fclose($output);
        fclose($handle);
        return $local;
    }

}
